<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'viaturas';
$conn = getDbConnection();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT * FROM veiculos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vehicle) {
    header('Location: viaturas_listar.php');
    exit;
}

function fetchRows(mysqli $conn, string $sql, int $id): array {
    $rows = [];
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    }
    return $rows;
}

$cargas = fetchRows($conn, "SELECT c.codigo_rastreio, c.estado_carga, c.local_recolha, c.local_entrega, c.data_hora_entrega_prevista, m.nome_completo FROM cargas c LEFT JOIN motoristas m ON c.motorista_id = m.id WHERE c.viatura_id = ? ORDER BY c.created_at DESC LIMIT 8", $id);
$viagens = fetchRows($conn, 'SELECT data_viagem, distancia_km FROM viagens WHERE veiculo_id = ? ORDER BY data_viagem DESC LIMIT 8', $id);
$manutencoes = fetchRows($conn, "SELECT tipo, status, criticidade, descricao, data_agendada FROM manutencoes WHERE veiculo_id = ? ORDER BY data_agendada DESC LIMIT 8", $id);
$avarias = fetchRows($conn, "SELECT titulo, prioridade, status, criado_em FROM avarias_problemas WHERE viatura_id = ? ORDER BY criado_em DESC LIMIT 8", $id);
$combustivel = fetchRows($conn, "SELECT data_abastecimento, litros, custo_total, odometro_km, posto FROM abastecimentos WHERE veiculo_id = ? ORDER BY data_abastecimento DESC, id DESC LIMIT 8", $id);

$statsStmt = $conn->prepare("SELECT COALESCE(SUM(distancia_km),0) AS km_viagens FROM viagens WHERE veiculo_id = ?");
$statsStmt->bind_param('i', $id);
$statsStmt->execute();
$kmViagens = (int) ($statsStmt->get_result()->fetch_assoc()['km_viagens'] ?? 0);
$statsStmt->close();

$fuelStmt = $conn->prepare("SELECT COALESCE(SUM(litros),0) AS litros, COALESCE(SUM(custo_total),0) AS custo FROM abastecimentos WHERE veiculo_id = ?");
$fuelStmt->bind_param('i', $id);
$fuelStmt->execute();
$fuelStats = $fuelStmt->get_result()->fetch_assoc();
$fuelStmt->close();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico da Viatura - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand"><button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button><div><p class="brand-title">Frotalink</p><span class="brand-subtitle">Histórico da Viatura</span></div></div>
        <form class="topbar-search" method="get" action="pesquisa.php"><input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar"></form>
        <div class="topbar-actions"><div class="topbar-stats"><span>Olá, <?php echo htmlspecialchars($_SESSION['user']); ?></span><span class="status-pill status-active">Online</span></div><a class="button secondary" href="logout.php">Sair</a></div>
    </header>
    <div class="page-layout">
        <?php include 'sidebar.php'; ?>
        <main class="dashboard-content">
            <section class="widget">
                <div class="widget-header">
                    <div><h2><?php echo htmlspecialchars($vehicle['matricula'] . ' - ' . $vehicle['modelo']); ?></h2><p class="section-subtitle">Estado: <?php echo htmlspecialchars($vehicle['status']); ?> | Atualizado em <?php echo htmlspecialchars($vehicle['updated_at']); ?></p></div>
                    <a class="button" href="viaturas_editar.php?id=<?php echo urlencode($id); ?>">Editar viatura</a>
                </div>
            </section>
            <section class="dashboard-cards compact-cards">
                <article class="metric-card"><span class="metric-title">KM total</span><strong class="metric-value"><?php echo htmlspecialchars(number_format((int) $vehicle['km_total'], 0, ',', '.')); ?></strong><span class="metric-caption">Odómetro registado</span></article>
                <article class="metric-card"><span class="metric-title">KM viagens</span><strong class="metric-value"><?php echo htmlspecialchars(number_format($kmViagens, 0, ',', '.')); ?></strong><span class="metric-caption">Histórico operacional</span></article>
                <article class="metric-card"><span class="metric-title">Litros</span><strong class="metric-value"><?php echo htmlspecialchars(number_format((float) $fuelStats['litros'], 0, ',', '.')); ?></strong><span class="metric-caption">Combustível registado</span></article>
                <article class="metric-card"><span class="metric-title">Custo combustível</span><strong class="metric-value"><?php echo htmlspecialchars(number_format((float) $fuelStats['custo'], 2, ',', '.')); ?> EUR</strong><span class="metric-caption">Total acumulado</span></article>
            </section>

            <section class="dashboard-bottom">
                <article class="widget"><div class="widget-header"><h2>Cargas recentes</h2></div><?php if ($cargas): ?><ul class="alert-list"><?php foreach ($cargas as $row): ?><li><strong><?php echo htmlspecialchars(($row['codigo_rastreio'] ?: '-') . ' | ' . $row['estado_carga']); ?></strong><span class="table-muted"><?php echo htmlspecialchars($row['local_recolha'] . ' -> ' . $row['local_entrega']); ?></span></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state">Sem cargas registadas.</p><?php endif; ?></article>
                <article class="widget"><div class="widget-header"><h2>Combustível</h2><a class="button secondary" href="combustivel_listar.php">Adicionar</a></div><?php if ($combustivel): ?><ul class="alert-list"><?php foreach ($combustivel as $row): ?><li><strong><?php echo htmlspecialchars($row['data_abastecimento'] . ' | ' . number_format((float) $row['litros'], 2, ',', '.') . ' L'); ?></strong><span class="table-muted"><?php echo htmlspecialchars(number_format((float) $row['custo_total'], 2, ',', '.') . ' EUR | ' . ($row['posto'] ?: '-')); ?></span></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state">Sem abastecimentos.</p><?php endif; ?></article>
            </section>
            <section class="dashboard-bottom">
                <article class="widget"><div class="widget-header"><h2>Manutenções</h2></div><?php if ($manutencoes): ?><ul class="alert-list"><?php foreach ($manutencoes as $row): ?><li><strong><?php echo htmlspecialchars($row['data_agendada'] . ' | ' . $row['tipo']); ?></strong><span class="table-muted"><?php echo htmlspecialchars($row['status'] . ' / ' . $row['criticidade']); ?></span></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state">Sem manutenções.</p><?php endif; ?></article>
                <article class="widget"><div class="widget-header"><h2>Avarias</h2></div><?php if ($avarias): ?><ul class="alert-list"><?php foreach ($avarias as $row): ?><li><strong><?php echo htmlspecialchars($row['titulo']); ?></strong><span class="table-muted"><?php echo htmlspecialchars($row['status'] . ' / ' . $row['prioridade'] . ' | ' . $row['criado_em']); ?></span></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state">Sem avarias registadas.</p><?php endif; ?></article>
            </section>
            <section class="widget"><div class="widget-header"><h2>Viagens</h2></div><?php if ($viagens): ?><div class="table-responsive"><table><thead><tr><th>Data</th><th>Distancia</th></tr></thead><tbody><?php foreach ($viagens as $row): ?><tr><td><?php echo htmlspecialchars($row['data_viagem']); ?></td><td><?php echo htmlspecialchars(number_format((int) $row['distancia_km'], 0, ',', '.')); ?> km</td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="empty-state">Sem viagens registadas.</p><?php endif; ?></section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
