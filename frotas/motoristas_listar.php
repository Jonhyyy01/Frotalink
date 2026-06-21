<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'motoristas';
$conn = getDbConnection();
$result = $conn->query("SELECT m.id, m.nome_completo, m.telefone, m.email, m.nif, m.estado, m.disponibilidade, m.viatura_atual_id, v.matricula AS veiculo_matricula, m.created_at FROM motoristas m LEFT JOIN veiculos v ON m.viatura_atual_id = v.id ORDER BY m.created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoristas - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Gestão de Motoristas</span>
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
                    <h2>Motoristas</h2>
                    <a class="button" href="motoristas_criar.php">Novo Motorista</a>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Telefone</th>
                                    <th>Email</th>
                                    <th>NIF</th>
                                    <th>Estado</th>
                                    <th>Disponibilidade</th>
                                    <th>Viatura</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nif']); ?></td>
                                        <td><?php echo htmlspecialchars($row['estado']); ?></td>
                                        <td><?php echo htmlspecialchars($row['disponibilidade']); ?></td>
                                        <td><?php echo htmlspecialchars($row['veiculo_matricula'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td>
                                            <a class="action-link" href="motoristas_editar.php?id=<?php echo urlencode($row['id']); ?>">Editar</a>
                                            <a class="action-link danger" href="motoristas_apagar.php?id=<?php echo urlencode($row['id']); ?>">Apagar</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhum motorista encontrado.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
