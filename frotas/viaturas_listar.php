<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'viaturas';
$conn = getDbConnection();
$result = $conn->query('SELECT id, matricula, modelo, status, km_total, consumo_medio, lat, lon, updated_at FROM veiculos ORDER BY updated_at DESC');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viaturas - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Gestão de Viaturas</span>
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
                    <h2>Viaturas</h2>
                    <a class="button" href="viaturas_criar.php">Nova Viatura</a>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Matrícula</th>
                                    <th>Modelo</th>
                                    <th>Estado</th>
                                    <th>KM Total</th>
                                    <th>Consumo</th>
                                    <th>Localização</th>
                                    <th>Atualizado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['matricula']); ?></td>
                                        <td><?php echo htmlspecialchars($row['modelo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($row['km_total'], 0, ',', '.')); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($row['consumo_medio'], 1, ',', '.')); ?> L/100km</td>
                                        <td><?php echo $row['lat'] !== null && $row['lon'] !== null ? htmlspecialchars($row['lat'] . ', ' . $row['lon']) : 'Sem localização'; ?></td>
                                        <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                                        <td>
                                            <a class="action-link" href="viaturas_historico.php?id=<?php echo urlencode($row['id']); ?>">Histórico</a>
                                            <a class="action-link" href="viaturas_editar.php?id=<?php echo urlencode($row['id']); ?>">Editar</a>
                                            <a class="action-link danger" href="viaturas_apagar.php?id=<?php echo urlencode($row['id']); ?>">Apagar</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhuma viatura registada ainda.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
