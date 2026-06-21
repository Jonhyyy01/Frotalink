<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'calendario';
$conn = getDbConnection();

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$start = new DateTime($month . '-01');
$end = clone $start;
$end->modify('last day of this month');
$prevMonth = (clone $start)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $start)->modify('+1 month')->format('Y-m');
$startSql = $start->format('Y-m-d');
$endSql = $end->format('Y-m-d');

$events = [];
function addCalendarEvent(array &$events, string $date, string $type, string $title, string $meta, string $url): void {
    if ($date === '') {
        return;
    }
    $key = substr($date, 0, 10);
    if (!isset($events[$key])) {
        $events[$key] = [];
    }
    $events[$key][] = ['type' => $type, 'title' => $title, 'meta' => $meta, 'url' => $url];
}

$stmt = $conn->prepare("SELECT id, codigo_rastreio, estado_carga, local_recolha, local_entrega, data_hora_recolha_prevista, data_hora_entrega_prevista FROM cargas WHERE (DATE(data_hora_recolha_prevista) BETWEEN ? AND ?) OR (DATE(data_hora_entrega_prevista) BETWEEN ? AND ?)");
if ($stmt) {
    $stmt->bind_param('ssss', $startSql, $endSql, $startSql, $endSql);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        addCalendarEvent($events, (string) $row['data_hora_recolha_prevista'], 'Carga', 'Recolha ' . ($row['codigo_rastreio'] ?: '#' . $row['id']), (string) $row['local_recolha'], 'cargas_editar.php?id=' . urlencode($row['id']));
        addCalendarEvent($events, (string) $row['data_hora_entrega_prevista'], 'Entrega', 'Entrega ' . ($row['codigo_rastreio'] ?: '#' . $row['id']), (string) $row['local_entrega'], 'cargas_editar.php?id=' . urlencode($row['id']));
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT m.id, m.tipo, m.criticidade, m.data_agendada, v.matricula FROM manutencoes m LEFT JOIN veiculos v ON m.veiculo_id = v.id WHERE m.status = 'pendente' AND m.data_agendada BETWEEN ? AND ?");
if ($stmt) {
    $stmt->bind_param('ss', $startSql, $endSql);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        addCalendarEvent($events, (string) $row['data_agendada'], 'Manutenção', (string) $row['tipo'], trim(($row['matricula'] ?: '-') . ' / ' . $row['criticidade']), 'manutencoes_listar.php');
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT id, titulo, prioridade, criado_em FROM avarias_problemas WHERE status NOT IN ('Resolvido', 'Fechado') AND DATE(criado_em) BETWEEN ? AND ?");
if ($stmt) {
    $stmt->bind_param('ss', $startSql, $endSql);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        addCalendarEvent($events, (string) $row['criado_em'], 'Avaria', (string) $row['titulo'], (string) $row['prioridade'], 'avarias_listar.php');
    }
    $stmt->close();
}

$firstWeekday = (int) $start->format('N');
$daysInMonth = (int) $end->format('j');
$flatEvents = [];
foreach ($events as $date => $items) {
    foreach ($items as $item) {
        $flatEvents[] = ['date' => $date] + $item;
    }
}
usort($flatEvents, fn($a, $b) => strcmp($a['date'], $b['date']));
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand"><button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button><div><p class="brand-title">Frotalink</p><span class="brand-subtitle">Calendário Operacional</span></div></div>
        <form class="topbar-search" method="get" action="pesquisa.php"><input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar"></form>
        <div class="topbar-actions"><div class="topbar-stats"><span>Olá, <?php echo htmlspecialchars($_SESSION['user']); ?></span><span class="status-pill status-active">Online</span></div><a class="button secondary" href="logout.php">Sair</a></div>
    </header>
    <div class="page-layout">
        <?php include 'sidebar.php'; ?>
        <main class="dashboard-content">
            <section class="widget">
                <div class="widget-header">
                    <div><h2><?php echo htmlspecialchars($start->format('m/Y')); ?></h2><p class="section-subtitle">Cargas, entregas, manutenções e avarias abertas.</p></div>
                    <div class="button-row"><a class="button secondary" href="calendario.php?month=<?php echo urlencode($prevMonth); ?>">Anterior</a><a class="button secondary" href="calendario.php">Hoje</a><a class="button secondary" href="calendario.php?month=<?php echo urlencode($nextMonth); ?>">Seguinte</a></div>
                </div>
                <div class="calendar-grid calendar-head"><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sab</span><span>Dom</span></div>
                <div class="calendar-grid">
                    <?php for ($blank = 1; $blank < $firstWeekday; $blank++): ?><div class="calendar-day muted-day"></div><?php endfor; ?>
                    <?php for ($day = 1; $day <= $daysInMonth; $day++): $dateKey = $start->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT); ?>
                        <div class="calendar-day">
                            <strong><?php echo $day; ?></strong>
                            <?php foreach (array_slice($events[$dateKey] ?? [], 0, 3) as $event): ?>
                                <a class="calendar-event event-<?php echo strtolower($event['type']); ?>" href="<?php echo htmlspecialchars($event['url']); ?>"><span><?php echo htmlspecialchars($event['type']); ?></span><?php echo htmlspecialchars($event['title']); ?></a>
                            <?php endforeach; ?>
                            <?php if (count($events[$dateKey] ?? []) > 3): ?><small>+<?php echo count($events[$dateKey]) - 3; ?> eventos</small><?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </section>

            <section class="widget">
                <div class="widget-header"><h2>Agenda do mês</h2></div>
                <?php if ($flatEvents): ?>
                    <ul class="alert-list">
                        <?php foreach ($flatEvents as $event): ?><li><strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($event['date'])) . ' - ' . $event['type']); ?></strong><span class="table-muted"><?php echo htmlspecialchars($event['title'] . ' | ' . $event['meta']); ?></span></li><?php endforeach; ?>
                    </ul>
                <?php else: ?><p class="empty-state">Sem eventos neste mês.</p><?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
