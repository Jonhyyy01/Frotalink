<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'viaturas';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: viaturas_listar.php');
    exit;
}

$conn = getDbConnection();
$stmt = $conn->prepare('SELECT matricula, modelo, status, km_total, consumo_medio, lat, lon FROM veiculos WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($matricula, $modelo, $status, $km_total, $consumo_medio, $lat, $lon);
if (! $stmt->fetch()) {
    $stmt->close();
    header('Location: viaturas_listar.php');
    exit;
}
$stmt->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = trim($_POST['matricula'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $status = $_POST['status'] ?? 'ativo';
    $km_total = $_POST['km_total'] ?? 0;
    $consumo_medio = $_POST['consumo_medio'] ?? 0.0;
    $lat = trim($_POST['lat'] ?? '');
    $lon = trim($_POST['lon'] ?? '');

    if ($matricula === '' || $modelo === '') {
        $error = 'Por favor, preencha a matrícula e o modelo da viatura.';
    } elseif (!in_array($status, ['ativo', 'em_manutencao', 'ocioso'], true)) {
        $error = 'Estado de viatura inválido.';
    } elseif (!is_numeric($km_total) || (int)$km_total < 0) {
        $error = 'O total de quilómetros deve ser um número válido e não negativo.';
    } elseif (!is_numeric($consumo_medio) || (float)$consumo_medio < 0) {
        $error = 'O consumo médio deve ser um número válido e não negativo.';
    } else {
        $km_total = (int)$km_total;
        $consumo_medio = number_format((float)$consumo_medio, 1, '.', '');
        $lat = $lat === '' ? null : $lat;
        $lon = $lon === '' ? null : $lon;

        $stmt = $conn->prepare('UPDATE veiculos SET matricula = ?, modelo = ?, status = ?, km_total = ?, consumo_medio = ?, lat = ?, lon = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('sssiddsi', $matricula, $modelo, $status, $km_total, $consumo_medio, $lat, $lon, $id);
            if ($stmt->execute()) {
                header('Location: viaturas_listar.php');
                exit;
            }
            $error = 'Erro ao atualizar a viatura. Tente novamente.';
            $stmt->close();
        } else {
            $error = 'Erro no pedido à base de dados.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Viatura - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Editar Viatura</span>
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
                    <h2>Editar Viatura</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="viaturas_editar.php?id=<?php echo urlencode($id); ?>">
                    <label for="matricula">Matrícula</label>
                    <input type="text" id="matricula" name="matricula" value="<?php echo htmlspecialchars($matricula); ?>" required>

                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($modelo); ?>" required>

                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="em_manutencao" <?php echo $status === 'em_manutencao' ? 'selected' : ''; ?>>Em manutenção</option>
                        <option value="ocioso" <?php echo $status === 'ocioso' ? 'selected' : ''; ?>>Ocioso</option>
                    </select>

                    <label for="km_total">Quilómetros Totais</label>
                    <input type="number" id="km_total" name="km_total" value="<?php echo htmlspecialchars($km_total); ?>" min="0">

                    <label for="consumo_medio">Consumo Médio (L/100km)</label>
                    <input type="number" step="0.1" id="consumo_medio" name="consumo_medio" value="<?php echo htmlspecialchars($consumo_medio); ?>" min="0">

                    <div class="map-group">
                        <label>Localização</label>
                        <div id="vehicle-map"></div>
                        <small class="form-note">Clique no mapa para definir a posição da viatura ou arraste o marcador.</small>
                    </div>

                    <label for="lat">Latitude</label>
                    <input type="text" id="lat" name="lat" value="<?php echo htmlspecialchars($lat); ?>" readonly>

                    <label for="lon">Longitude</label>
                    <input type="text" id="lon" name="lon" value="<?php echo htmlspecialchars($lon); ?>" readonly>

                    <button type="submit" class="button">Salvar Alterações</button>
                </form>
            </section>
        </main>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        const initialLat = <?php echo $lat !== '' ? json_encode((float) $lat) : '41.1579'; ?>;
        const initialLon = <?php echo $lon !== '' ? json_encode((float) $lon) : '-8.6291'; ?>;
        const map = L.map('vehicle-map').setView([initialLat, initialLon], <?php echo ($lat !== '' && $lon !== '') ? '14' : '6'; ?>);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = null;
        function updateLocation(lat, lon) {
            document.getElementById('lat').value = lat.toFixed(6);
            document.getElementById('lon').value = lon.toFixed(6);
        }
        function setMarker(lat, lon) {
            if (marker) {
                marker.setLatLng([lat, lon]);
            } else {
                marker = L.marker([lat, lon], { draggable: true }).addTo(map);
                marker.on('dragend', function (e) {
                    const pos = e.target.getLatLng();
                    updateLocation(pos.lat, pos.lng);
                });
            }
            marker.bindPopup('Posição da viatura').openPopup();
            updateLocation(lat, lon);
        }

        if (<?php echo ($lat !== '' && $lon !== '') ? 'true' : 'false'; ?>) {
            setMarker(initialLat, initialLon);
        }

        map.on('click', function (e) {
            const lat = e.latlng.lat;
            const lon = e.latlng.lng;
            setMarker(lat, lon);
        });
    </script>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
