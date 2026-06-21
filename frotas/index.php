<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'inicio';
$conn = getDbConnection();

function dashboardCount(mysqli $conn, string $sql): int {
    $result = $conn->query($sql);
    if (! $result) {
        return 0;
    }

    $row = $result->fetch_row();
    return (int) ($row[0] ?? 0);
}

function metricSeverityClass(int $value): string {
    if ($value >= 3) {
        return 'metric-state-danger';
    }
    if ($value === 2) {
        return 'metric-state-warning';
    }
    if ($value === 1) {
        return 'metric-state-success';
    }
    return '';
}

function metricSeverityStyle(int $value): string {
    if ($value >= 3) {
        return "border-color: rgba(239, 68, 68, 0.72); border-top-color: #ef4444; background: linear-gradient(135deg, rgba(239, 68, 68, 0.42), rgba(24, 19, 21, 0.96) 50%), #181315;";
    }
    if ($value === 2) {
        return "border-color: rgba(245, 158, 11, 0.68); border-top-color: #f59e0b; background: linear-gradient(135deg, rgba(245, 158, 11, 0.38), rgba(16, 27, 30, 0.94) 50%), #101b1e;";
    }
    if ($value === 1) {
        return "border-color: rgba(34, 197, 94, 0.62); border-top-color: #22c55e; background: linear-gradient(135deg, rgba(34, 197, 94, 0.34), rgba(16, 27, 30, 0.94) 50%), #101b1e;";
    }
    return '';
}

$today = date('Y-m-d');
$tripRangeStart = (new DateTime('-6 days'))->format('Y-m-d');

$vehiclePoints = [];
$stmt = $conn->prepare('SELECT matricula, modelo, status, lat, lon FROM veiculos WHERE lat IS NOT NULL AND lon IS NOT NULL');
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($matricula, $modelo, $status, $lat, $lon);
    while ($stmt->fetch()) {
        $vehiclePoints[] = [
            'matricula' => $matricula,
            'modelo' => $modelo,
            'status' => $status,
            'lat' => (float) $lat,
            'lon' => (float) $lon,
        ];
    }
    $stmt->close();
}

$clientPoints = [];
$stmt = $conn->prepare('SELECT nome, morada_fiscal, codigo_postal, localidade, pais, lat, lon FROM clientes WHERE lat IS NOT NULL AND lon IS NOT NULL');
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($clientName, $moradaFiscal, $codigoPostal, $localidadeCliente, $paisCliente, $clientLat, $clientLon);
    while ($stmt->fetch()) {
        $clientPoints[] = [
            'nome' => $clientName,
            'morada_fiscal' => $moradaFiscal,
            'codigo_postal' => $codigoPostal,
            'localidade' => $localidadeCliente,
            'pais' => $paisCliente,
            'lat' => (float) $clientLat,
            'lon' => (float) $clientLon,
        ];
    }
    $stmt->close();
}

$tripsToday = 0;
$distanceToday = 0;
$stmt = $conn->prepare('SELECT COUNT(*) AS total_trips, COALESCE(SUM(distancia_km), 0) AS total_distance FROM viagens WHERE data_viagem = ?');
if ($stmt) {
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $stmt->bind_result($tripsToday, $distanceToday);
    $stmt->fetch();
    $stmt->close();
}

$weekKm = dashboardCount($conn, "SELECT COALESCE(SUM(distancia_km), 0) FROM viagens WHERE data_viagem BETWEEN '{$conn->real_escape_string($tripRangeStart)}' AND '{$conn->real_escape_string($today)}'");
$totalVehicles = dashboardCount($conn, 'SELECT COUNT(*) FROM veiculos');
$activeVehicles = dashboardCount($conn, "SELECT COUNT(*) FROM veiculos WHERE status = 'ativo'");
$totalDrivers = dashboardCount($conn, 'SELECT COUNT(*) FROM motoristas');
$availableDrivers = dashboardCount($conn, "SELECT COUNT(*) FROM motoristas WHERE disponibilidade = 'Disponível' OR disponibilidade = 'Disponivel'");
$totalClients = dashboardCount($conn, 'SELECT COUNT(*) FROM clientes');
$activeLoads = dashboardCount($conn, "SELECT COUNT(*) FROM cargas WHERE estado_carga IN ('Pendente', 'Em Trânsito')");
$lateLoads = dashboardCount($conn, "SELECT COUNT(*) FROM cargas WHERE estado_carga IN ('Pendente', 'Em Trânsito') AND data_hora_entrega_prevista IS NOT NULL AND data_hora_entrega_prevista < NOW()");
$pendingMaintenances = dashboardCount($conn, "SELECT COUNT(*) FROM manutencoes WHERE status = 'pendente'");
$criticalMaintenances = dashboardCount($conn, "SELECT COUNT(*) FROM manutencoes WHERE status = 'pendente' AND criticidade = 'critico'");
$maintenanceDueSoon = dashboardCount($conn, "SELECT COUNT(*) FROM manutencoes WHERE status = 'pendente' AND data_agendada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$openIssues = dashboardCount($conn, "SELECT COUNT(*) FROM avarias_problemas WHERE status NOT IN ('Resolvido', 'Fechado')");
$criticalIssues = dashboardCount($conn, "SELECT COUNT(*) FROM avarias_problemas WHERE status NOT IN ('Resolvido', 'Fechado') AND prioridade = 'Crítica'");
$fuelMonthCost = dashboardCount($conn, "SELECT COALESCE(SUM(custo_total), 0) FROM abastecimentos WHERE data_abastecimento >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");

$alerts = [];
$alertResult = $conn->query("SELECT tipo, criticidade, descricao, data_agendada FROM manutencoes WHERE status = 'pendente' ORDER BY FIELD(criticidade, 'critico', 'alto', 'medio', 'baixo'), data_agendada ASC LIMIT 5");
if ($alertResult) {
    while ($alert = $alertResult->fetch_assoc()) {
        $alerts[] = $alert;
    }
}

$recentIssues = [];
$issueResult = $conn->query("SELECT a.titulo, a.prioridade, a.status, a.criado_em, v.matricula FROM avarias_problemas a LEFT JOIN veiculos v ON a.viatura_id = v.id ORDER BY a.criado_em DESC LIMIT 5");
if ($issueResult) {
    while ($issue = $issueResult->fetch_assoc()) {
        $recentIssues[] = $issue;
    }
}

$upcomingLoads = [];
$loadResult = $conn->query("SELECT c.codigo_rastreio, c.estado_carga, c.local_recolha, c.local_entrega, c.data_hora_entrega_prevista, m.nome_completo FROM cargas c LEFT JOIN motoristas m ON c.motorista_id = m.id WHERE c.estado_carga IN ('Pendente', 'Em Trânsito') ORDER BY c.data_hora_entrega_prevista ASC LIMIT 5");
if ($loadResult) {
    while ($load = $loadResult->fetch_assoc()) {
        $upcomingLoads[] = $load;
    }
}

$weekLabels = [];
$weekData = [];
$startDate = new DateTime('-6 days');
for ($i = 0; $i < 7; $i++) {
    $date = clone $startDate;
    $date->modify("+{$i} days");
    $key = $date->format('Y-m-d');
    $weekLabels[] = $date->format('d/m');
    $weekData[$key] = 0;
}

$stmt = $conn->prepare('SELECT data_viagem, COALESCE(SUM(distancia_km),0) AS total_km FROM viagens WHERE data_viagem BETWEEN ? AND ? GROUP BY data_viagem ORDER BY data_viagem ASC');
if ($stmt) {
    $stmt->bind_param('ss', $tripRangeStart, $today);
    $stmt->execute();
    $stmt->bind_result($date, $totalKm);
    while ($stmt->fetch()) {
        if (isset($weekData[$date])) {
            $weekData[$date] = (int) $totalKm;
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frotalink - Painel Principal</title>
    <link rel="stylesheet" href="layout.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Painel Principal</span>
            </div>
        </div>
        <form class="topbar-search" method="get" action="pesquisa.php">
                <input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar">
            </form>
        <div class="topbar-actions">
            <div class="topbar-stats">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
                <span class="status-pill status-active"><?php echo htmlspecialchars(currentUserRole()); ?></span>
            </div>
            <a class="button secondary" href="logout.php">Sair</a>
        </div>
    </header>

    <div class="page-layout">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-content">
            <section class="dashboard-cards compact-cards">
                <article class="metric-card">
                    <span class="metric-title">Viagens hoje</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($tripsToday); ?></strong>
                    <span class="metric-caption"><?php echo htmlspecialchars(number_format($distanceToday, 0, ',', '.')); ?> km acumulados hoje</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">KM últimos 7 dias</span>
                    <strong class="metric-value"><?php echo htmlspecialchars(number_format($weekKm, 0, ',', '.')); ?></strong>
                    <span class="metric-caption">Utilização total da frota</span>
                </article>
                <article class="metric-card <?php echo metricSeverityClass($lateLoads); ?>" style="<?php echo htmlspecialchars(metricSeverityStyle($lateLoads)); ?>">
                    <span class="metric-title">Cargas ativas</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($activeLoads); ?></strong>
                    <span class="metric-caption"><?php echo htmlspecialchars($lateLoads); ?> atrasadas</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">Frota operacional</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($activeVehicles . '/' . $totalVehicles); ?></strong>
                    <span class="metric-caption">Viaturas ativas</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">Motoristas</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($availableDrivers . '/' . $totalDrivers); ?></strong>
                    <span class="metric-caption">Disponíveis / total</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">Clientes</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($totalClients); ?></strong>
                    <span class="metric-caption">Registos comerciais</span>
                </article>
                <article class="metric-card <?php echo metricSeverityClass($pendingMaintenances); ?>" style="<?php echo htmlspecialchars(metricSeverityStyle($pendingMaintenances)); ?>">
                    <span class="metric-title">Manutenções pendentes</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($pendingMaintenances); ?></strong>
                    <span class="metric-caption"><?php echo htmlspecialchars($criticalMaintenances); ?> críticas | <?php echo htmlspecialchars($maintenanceDueSoon); ?> nos próximos 7 dias</span>
                </article>
                <article class="metric-card <?php echo metricSeverityClass($openIssues); ?>" style="<?php echo htmlspecialchars(metricSeverityStyle($openIssues)); ?>">
                    <span class="metric-title">Avarias abertas</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($openIssues); ?></strong>
                    <span class="metric-caption"><?php echo htmlspecialchars($criticalIssues); ?> críticas</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">Combustível mês</span>
                    <strong class="metric-value"><?php echo htmlspecialchars(number_format($fuelMonthCost, 0, ',', '.')); ?> EUR</strong>
                    <span class="metric-caption">Custo registado no mês atual</span>
                </article>
            </section>

            <section class="dashboard-widgets">
                <article class="widget widget-map">
                    <div class="widget-header">
                        <h2>Localização da frota e clientes</h2>
                        <span class="widget-tag">Atualizado agora</span>
                    </div>
                    <div id="fleet-map"></div>
                </article>
                <aside class="widget widget-summary">
                    <div class="widget-header">
                        <h2>Próximas cargas</h2>
                    </div>
                    <ul class="alert-list">
                        <?php if (count($upcomingLoads) === 0): ?>
                            <li>Nenhuma carga ativa.</li>
                        <?php else: ?>
                            <?php foreach ($upcomingLoads as $load): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($load['codigo_rastreio'] ?: 'Sem código'); ?></strong>
                                    <span class="table-muted"><?php echo htmlspecialchars(($load['local_recolha'] ?: '-') . ' → ' . ($load['local_entrega'] ?: '-')); ?></span>
                                    <span class="table-muted"><?php echo htmlspecialchars(($load['nome_completo'] ?: 'Sem motorista') . ' · ' . $load['estado_carga']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </aside>
            </section>

            <section class="dashboard-bottom">
                <article class="widget chart-card">
                    <div class="widget-header">
                        <h2>Utilização semanal (KM/dia)</h2>
                        <span>Últimos 7 dias</span>
                    </div>
                    <div class="chart-frame">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </article>
                <article class="widget list-card">
                    <div class="widget-header split">
                        <h2>Alertas e avarias recentes</h2>
                    </div>
                    <ul class="incident-list">
                        <?php foreach ($alerts as $alert): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($alert['criticidade']); ?></strong>
                                <span class="table-muted"><?php echo htmlspecialchars($alert['tipo'] . ' · ' . $alert['descricao']); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php foreach ($recentIssues as $issue): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($issue['prioridade']); ?></strong>
                                <span class="table-muted"><?php echo htmlspecialchars($issue['titulo'] . ' · ' . ($issue['matricula'] ?: 'Sem viatura') . ' · ' . $issue['status']); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($alerts) === 0 && count($recentIssues) === 0): ?>
                            <li>Nenhum incidente recente.</li>
                        <?php endif; ?>
                    </ul>
                </article>
            </section>
        </main>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const vehiclePoints = <?php echo json_encode($vehiclePoints, JSON_NUMERIC_CHECK); ?>;
        const clientPoints = <?php echo json_encode($clientPoints, JSON_NUMERIC_CHECK); ?>;
        const weekLabels = <?php echo json_encode($weekLabels); ?>;
        const weekValues = <?php echo json_encode(array_values($weekData)); ?>;

        const map = L.map('fleet-map', { scrollWheelZoom: false }).setView([41.1579, -8.6291], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const vehicleMarkers = vehiclePoints.map(point => {
            return L.marker([point.lat, point.lon])
                .addTo(map)
                .bindPopup(`<strong>${point.matricula}</strong><br>${point.modelo}<br>Estado: ${point.status}`);
        });

        const clientMarkers = clientPoints.map(point => {
            const address = [point.morada_fiscal, point.codigo_postal, point.localidade, point.pais].filter(Boolean).join(', ');
            return L.circleMarker([point.lat, point.lon], {
                radius: 7,
                color: '#0f766e',
                fillColor: '#2dd4bf',
                fillOpacity: 0.85
            })
                .addTo(map)
                .bindPopup(`<strong>${point.nome}</strong><br>${address}`);
        });

        const allMarkers = vehicleMarkers.concat(clientMarkers);
        if (allMarkers.length > 0) {
            const group = L.featureGroup(allMarkers);
            map.fitBounds(group.getBounds().pad(0.18));
        } else {
            L.popup({ closeButton: false, autoClose: false })
                .setLatLng([41.1579, -8.6291])
                .setContent('Nenhum local disponível.')
                .openOn(map);
        }

        new Chart(document.getElementById('weeklyChart'), {
            type: 'bar',
            data: {
                labels: weekLabels,
                datasets: [{
                    label: 'KM percorridos',
                    data: weekValues,
                    backgroundColor: '#0f766e',
                    borderRadius: 8,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 150,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#667781' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(102, 119, 129, 0.22)' },
                        ticks: { color: '#667781' }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
