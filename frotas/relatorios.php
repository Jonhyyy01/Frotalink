<?php
require_once 'config.php';
requireOperationsAccess();

$conn = getDbConnection();
$activePage = 'relatorios';

function outputCsv(string $filename, array $headers, mysqli_result $result): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ';');
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, array_values($row), ';');
    }
    fclose($out);
    exit;
}

$export = $_GET['export'] ?? '';
if ($export === 'cargas') {
    $res = $conn->query("SELECT c.codigo_rastreio, c.estado_carga, c.tipo_carga, c.peso_kg, c.local_recolha, c.local_entrega, c.data_hora_entrega_prevista, cl.nome AS cliente, v.matricula AS viatura, m.nome_completo AS motorista, c.valor_transporte FROM cargas c LEFT JOIN clientes cl ON c.cliente_id = cl.id LEFT JOIN veiculos v ON c.viatura_id = v.id LEFT JOIN motoristas m ON c.motorista_id = m.id ORDER BY c.created_at DESC");
    outputCsv('relatorio_cargas.csv', ['codigo', 'estado', 'tipo', 'peso_kg', 'recolha', 'entrega', 'entrega_prevista', 'cliente', 'viatura', 'motorista', 'valor'], $res);
}
if ($export === 'manutencoes') {
    $res = $conn->query("SELECT h.tipo_acao, h.status, h.prioridade, h.data_agendada, h.data_inicio, h.data_fim, v.matricula AS viatura, u.nome AS tecnico, h.custo_total FROM historico_manutencoes_inspecoes h LEFT JOIN veiculos v ON h.equipamento_id = v.id LEFT JOIN utilizadores u ON h.tecnico_id = u.id ORDER BY h.id DESC");
    outputCsv('relatorio_manutencoes.csv', ['tipo', 'status', 'prioridade', 'data_agendada', 'data_inicio', 'data_fim', 'viatura', 'técnico', 'custo_total'], $res);
}
if ($export === 'combustivel') {
    $res = $conn->query("SELECT a.data_abastecimento, v.matricula, v.modelo, m.nome_completo AS motorista, a.litros, a.custo_total, a.odometro_km, a.posto FROM abastecimentos a JOIN veiculos v ON a.veiculo_id = v.id LEFT JOIN motoristas m ON a.motorista_id = m.id ORDER BY a.data_abastecimento DESC");
    outputCsv('relatorio_combustivel.csv', ['data', 'matricula', 'modelo', 'motorista', 'litros', 'custo_total', 'odómetro', 'posto'], $res);
}
if ($export === 'viaturas') {
    $res = $conn->query("SELECT matricula, modelo, status, km_total, consumo_medio, updated_at FROM veiculos ORDER BY matricula ASC");
    outputCsv('relatorio_viaturas.csv', ['matricula', 'modelo', 'status', 'km_total', 'consumo_medio', 'atualizado_em'], $res);
}

$cards = [
    ['title' => 'Cargas', 'text' => 'Estado, cliente, viatura, motorista, datas e valor de transporte.', 'url' => 'relatorios.php?export=cargas'],
    ['title' => 'Manutenções', 'text' => 'Histórico técnico, prioridades, datas e custos.', 'url' => 'relatorios.php?export=manutencoes'],
    ['title' => 'Combustível', 'text' => 'Abastecimentos, litros, custos, odómetro e posto.', 'url' => 'relatorios.php?export=combustivel'],
    ['title' => 'Viaturas', 'text' => 'Frota, estado operacional, quilometragem e consumo médio.', 'url' => 'relatorios.php?export=viaturas'],
];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand"><button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button><div><p class="brand-title">Frotalink</p><span class="brand-subtitle">Relatórios e Exportações</span></div></div>
        <form class="topbar-search" method="get" action="pesquisa.php"><input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar"></form>
        <div class="topbar-actions"><div class="topbar-stats"><span>Olá, <?php echo htmlspecialchars($_SESSION['user']); ?></span><span class="status-pill status-active">Online</span></div><a class="button secondary" href="logout.php">Sair</a></div>
    </header>
    <div class="page-layout">
        <?php include 'sidebar.php'; ?>
        <main class="dashboard-content">
            <section class="widget">
                <div class="widget-header"><div><h2>Relatórios</h2><p class="section-subtitle">Exportações CSV prontas para abrir no Excel.</p></div></div>
                <div class="report-grid">
                    <?php foreach ($cards as $card): ?>
                        <article class="report-card">
                            <strong><?php echo htmlspecialchars($card['title']); ?></strong>
                            <p><?php echo htmlspecialchars($card['text']); ?></p>
                            <a class="button" href="<?php echo htmlspecialchars($card['url']); ?>">Exportar CSV</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
