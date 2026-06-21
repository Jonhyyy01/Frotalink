<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'cargas';

$error = '';
$codigo_rastreio = '';
$estado_carga = 'Pendente';
$descricao = '';
$tipo_carga = '';
$peso_kg = '';
$volume_m3 = '';
$quantidade_paletes = 0;

$local_recolha = '';
$data_hora_recolha_prevista = '';
$data_hora_recolha_real = '';
$local_entrega = '';
$data_hora_entrega_prevista = '';
$data_hora_entrega_real = '';

$cliente_id = '';
$valor_transporte = 0;
$pago = 0;

$viatura_id = '';
$motorista_id = '';

function optionalIntId($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    $id = (int) $value;
    return $id > 0 ? $id : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_rastreio = trim($_POST['codigo_rastreio'] ?? '');
    $estado_carga = $_POST['estado_carga'] ?? 'Pendente';
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo_carga = trim($_POST['tipo_carga'] ?? '');
    $peso_kg = trim($_POST['peso_kg'] ?? '0') ?: 0;
    $volume_m3 = trim($_POST['volume_m3'] ?? '0') ?: 0;
    $quantidade_paletes = (int) ($_POST['quantidade_paletes'] ?? 0);

    $local_recolha = trim($_POST['local_recolha'] ?? '');
    $data_hora_recolha_prevista = trim($_POST['data_hora_recolha_prevista'] ?? '') ?: null;
    $data_hora_recolha_real = trim($_POST['data_hora_recolha_real'] ?? '') ?: null;
    $local_entrega = trim($_POST['local_entrega'] ?? '');
    $data_hora_entrega_prevista = trim($_POST['data_hora_entrega_prevista'] ?? '') ?: null;
    $data_hora_entrega_real = trim($_POST['data_hora_entrega_real'] ?? '') ?: null;

    $cliente_id = optionalIntId($_POST['cliente_id'] ?? null);
    $valor_transporte = trim($_POST['valor_transporte'] ?? '0') ?: 0;
    $pago = isset($_POST['pago']) ? 1 : 0;

    $viatura_id = optionalIntId($_POST['viatura_id'] ?? null);
    $motorista_id = optionalIntId($_POST['motorista_id'] ?? null);

    if ($codigo_rastreio === '') {
        $error = 'Por favor, insira um código de rastreio.';
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare('INSERT INTO cargas (codigo_rastreio, estado_carga, descricao, tipo_carga, peso_kg, volume_m3, quantidade_paletes, local_recolha, data_hora_recolha_prevista, data_hora_recolha_real, local_entrega, data_hora_entrega_prevista, data_hora_entrega_real, cliente_id, valor_transporte, pago, viatura_id, motorista_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('ssssddissssssidiii', $codigo_rastreio, $estado_carga, $descricao, $tipo_carga, $peso_kg, $volume_m3, $quantidade_paletes, $local_recolha, $data_hora_recolha_prevista, $data_hora_recolha_real, $local_entrega, $data_hora_entrega_prevista, $data_hora_entrega_real, $cliente_id, $valor_transporte, $pago, $viatura_id, $motorista_id);
            if ($stmt->execute()) {
                header('Location: cargas_listar.php');
                exit;
            }
            $error = 'Erro ao criar carga.';
            $stmt->close();
        } else {
            $error = 'Erro na preparação da query.';
        }
    }
}

$conn = getDbConnection();
$veiculosRes = $conn->query('SELECT id, matricula FROM veiculos ORDER BY id DESC');
$motoristasRes = $conn->query('SELECT id, nome_completo FROM motoristas ORDER BY id DESC');
$clientesRes = $conn->query('SELECT id, nome FROM clientes ORDER BY nome ASC');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Carga - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Nova Carga</span>
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
                    <h2>Criar Carga</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="cargas_criar.php">
                    <label for="codigo_rastreio">Código de Rastreio / Guia</label>
                    <input type="text" id="codigo_rastreio" name="codigo_rastreio" value="<?php echo htmlspecialchars($codigo_rastreio); ?>" required>

                    <label for="estado_carga">Estado</label>
                    <select id="estado_carga" name="estado_carga">
                        <option value="Pendente" <?php echo $estado_carga === 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="Em Trânsito" <?php echo $estado_carga === 'Em Trânsito' ? 'selected' : ''; ?>>Em Trânsito</option>
                        <option value="Entregue" <?php echo $estado_carga === 'Entregue' ? 'selected' : ''; ?>>Entregue</option>
                        <option value="Cancelada" <?php echo $estado_carga === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>

                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($descricao); ?></textarea>

                    <label for="tipo_carga">Tipo de Carga</label>
                    <input type="text" id="tipo_carga" name="tipo_carga" value="<?php echo htmlspecialchars($tipo_carga); ?>">

                    <label for="peso_kg">Peso (kg)</label>
                    <input type="number" step="0.01" id="peso_kg" name="peso_kg" value="<?php echo htmlspecialchars($peso_kg); ?>">

                    <label for="volume_m3">Volume (m³)</label>
                    <input type="number" step="0.001" id="volume_m3" name="volume_m3" value="<?php echo htmlspecialchars($volume_m3); ?>">

                    <label for="quantidade_paletes">Quantidade de Paletes</label>
                    <input type="number" id="quantidade_paletes" name="quantidade_paletes" value="<?php echo htmlspecialchars($quantidade_paletes); ?>">

                    <h3>Rota</h3>
                    <label for="local_recolha">Local de Recolha</label>
                    <input type="text" id="local_recolha" name="local_recolha" value="<?php echo htmlspecialchars($local_recolha); ?>">

                    <label for="data_hora_recolha_prevista">Data/Hora Recolha Prevista</label>
                    <input type="datetime-local" id="data_hora_recolha_prevista" name="data_hora_recolha_prevista" value="<?php echo htmlspecialchars($data_hora_recolha_prevista); ?>">

                    <label for="local_entrega">Local de Entrega</label>
                    <input type="text" id="local_entrega" name="local_entrega" value="<?php echo htmlspecialchars($local_entrega); ?>">

                    <label for="data_hora_entrega_prevista">Data/Hora Entrega Prevista</label>
                    <input type="datetime-local" id="data_hora_entrega_prevista" name="data_hora_entrega_prevista" value="<?php echo htmlspecialchars($data_hora_entrega_prevista); ?>">

                    <h3>Financeiro</h3>
                    <label for="cliente_id">Cliente</label>
                    <select id="cliente_id" name="cliente_id">
                        <option value="">-- Nenhum --</option>
                        <?php if ($clientesRes): while ($cl = $clientesRes->fetch_assoc()): ?>
                            <option value="<?php echo $cl['id']; ?>" <?php echo $cliente_id == $cl['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cl['nome']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>

                    <label for="valor_transporte">Valor do Transporte</label>
                    <input type="number" step="0.01" id="valor_transporte" name="valor_transporte" value="<?php echo htmlspecialchars($valor_transporte); ?>">

                    <label><input type="checkbox" name="pago" <?php echo $pago ? 'checked' : ''; ?>> Pago</label>

                    <label for="viatura_id">Viatura</label>
                    <select id="viatura_id" name="viatura_id">
                        <option value="">-- Nenhuma --</option>
                        <?php if ($veiculosRes): while ($v = $veiculosRes->fetch_assoc()): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $viatura_id == $v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['matricula']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>

                    <label for="motorista_id">Motorista</label>
                    <select id="motorista_id" name="motorista_id">
                        <option value="">-- Nenhum --</option>
                        <?php if ($motoristasRes): while ($m = $motoristasRes->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $motorista_id == $m['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['nome_completo']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>

                    <button type="submit" class="button">Criar Carga</button>
                </form>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
