<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'clientes';
$conn = getDbConnection();

$search = trim($_GET['search'] ?? '');
$tipo_cliente = $_GET['tipo_cliente'] ?? '';
$estado_cliente = $_GET['estado_cliente'] ?? '';
$pais = trim($_GET['pais'] ?? '');
$min_limite_credito = trim($_GET['min_limite_credito'] ?? '');
$max_limite_credito = trim($_GET['max_limite_credito'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $escapedSearch = $conn->real_escape_string('%' . $search . '%');
    $where[] = "(nome LIKE '{$escapedSearch}' OR nif_nipc LIKE '{$escapedSearch}' OR responsavel_contacto LIKE '{$escapedSearch}')";
}
if ($tipo_cliente !== '' && in_array($tipo_cliente, ['Física', 'Jurídica'], true)) {
    $where[] = "tipo_cliente = '" . $conn->real_escape_string($tipo_cliente) . "'";
}
if ($estado_cliente !== '' && in_array($estado_cliente, ['Ativo', 'Bloqueado', 'Inativo'], true)) {
    $where[] = "estado_cliente = '" . $conn->real_escape_string($estado_cliente) . "'";
}
if ($pais !== '') {
    $where[] = "pais = '" . $conn->real_escape_string($pais) . "'";
}
if ($min_limite_credito !== '' && is_numeric($min_limite_credito)) {
    $where[] = "limite_credito >= " . $conn->real_escape_string($min_limite_credito);
}
if ($max_limite_credito !== '' && is_numeric($max_limite_credito)) {
    $where[] = "limite_credito <= " . $conn->real_escape_string($max_limite_credito);
}

$query = "SELECT id, tipo_cliente, nome, responsavel_contacto, nif_nipc, email, telefone, pais, limite_credito, estado_cliente, created_at FROM clientes";
if (!empty($where)) {
    $query .= ' WHERE ' . implode(' AND ', $where);
}
$query .= ' ORDER BY created_at DESC';
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Gestão de Clientes</span>
            </div>
        </div>
        <form class="topbar-search" method="get" action="pesquisa.php"><input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar"></form>
        <div class="topbar-actions">
            <div class="topbar-stats">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
                <span class="status-pill status-active">Online</span>
            </div>
            <a class="button secondary" href="logout.php">Sair</a>
        </div>
    </header>

    <div class="page-layout">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-content">
            <section class="widget">
                <div class="widget-header">
                    <h2>Clientes</h2>
                    <a class="button" href="clientes_criar.php">Novo Cliente</a>
                </div>

                <form method="get" action="clientes_listar.php" class="filter-form">
                    <div class="filter-row">
                        <label for="search">Pesquisar</label>
                        <input type="search" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome, NIF ou contacto">
                    </div>
                    <div class="filter-row">
                        <label for="tipo_cliente">Tipo de Cliente</label>
                        <select id="tipo_cliente" name="tipo_cliente">
                            <option value="">Todos</option>
                            <option value="Física" <?php echo $tipo_cliente === 'Física' ? 'selected' : ''; ?>>Física</option>
                            <option value="Jurídica" <?php echo $tipo_cliente === 'Jurídica' ? 'selected' : ''; ?>>Jurídica</option>
                        </select>
                    </div>
                    <div class="filter-row">
                        <label for="estado_cliente">Estado</label>
                        <select id="estado_cliente" name="estado_cliente">
                            <option value="">Todos</option>
                            <option value="Ativo" <?php echo $estado_cliente === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="Bloqueado" <?php echo $estado_cliente === 'Bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                            <option value="Inativo" <?php echo $estado_cliente === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="filter-row">
                        <label for="pais">País</label>
                        <input type="text" id="pais" name="pais" value="<?php echo htmlspecialchars($pais); ?>" placeholder="Portugal, Espanha...">
                    </div>
                    <div class="filter-row">
                        <label for="min_limite_credito">Limite Crédito Mín.</label>
                        <input type="number" step="0.01" id="min_limite_credito" name="min_limite_credito" value="<?php echo htmlspecialchars($min_limite_credito); ?>">
                    </div>
                    <div class="filter-row">
                        <label for="max_limite_credito">Limite Crédito Máx.</label>
                        <input type="number" step="0.01" id="max_limite_credito" name="max_limite_credito" value="<?php echo htmlspecialchars($max_limite_credito); ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="button">Filtrar</button>
                        <a class="button secondary" href="clientes_listar.php">Limpar</a>
                    </div>
                </form>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Nome / Empresa</th>
                                    <th>Responsável</th>
                                    <th>NIF/NIPC</th>
                                    <th>Telefone</th>
                                    <th>Email</th>
                                    <th>País</th>
                                    <th>Limite Crédito</th>
                                    <th>Estado</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo_cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($row['responsavel_contacto']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nif_nipc']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['pais']); ?></td>
                                        <td><?php echo number_format((float) $row['limite_credito'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($row['estado_cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td>
                                            <a class="action-link" href="clientes_editar.php?id=<?php echo urlencode($row['id']); ?>">Editar</a>
                                            <a class="action-link danger" href="clientes_apagar.php?id=<?php echo urlencode($row['id']); ?>">Apagar</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhum cliente encontrado.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
