<?php
require_once 'config.php';
requireLogin();

$activePage = 'combustivel';
$conn = getDbConnection();
$motoristaAtualId = getCurrentMotoristaId($conn);
$isMotorista = isMotorista();
$csrf = create_csrf_token();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Pedido inválido. Atualize a página e tente novamente.';
    } else {
        $veiculoId = (int) ($_POST['veiculo_id'] ?? 0);
        $motoristaId = $isMotorista ? (int) ($motoristaAtualId ?? 0) : (int) ($_POST['motorista_id'] ?? 0);
        $data = trim($_POST['data_abastecimento'] ?? '');
        $litros = (float) ($_POST['litros'] ?? 0);
        $custo = (float) ($_POST['custo_total'] ?? 0);
        $odometro = trim($_POST['odometro_km'] ?? '');
        $posto = trim($_POST['posto'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($isMotorista && $motoristaAtualId === null) {
            $error = 'A sua conta ainda não está associada a um motorista. Contacte o administrador.';
        } elseif ($veiculoId <= 0 || $data === '' || $litros <= 0 || $custo < 0) {
            $error = 'Preencha a viatura, data, litros e custo corretamente.';
        } else {
            if ($isMotorista) {
                $stmt = $conn->prepare("SELECT v.id
                    FROM veiculos v
                    LEFT JOIN motoristas m ON m.viatura_atual_id = v.id AND m.id = ?
                    LEFT JOIN cargas c ON c.viatura_id = v.id AND c.motorista_id = ?
                    WHERE v.id = ? AND (m.id IS NOT NULL OR c.id IS NOT NULL)
                    LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('iii', $motoristaId, $motoristaId, $veiculoId);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows === 0) {
                        $error = 'A viatura selecionada não está associada ao seu perfil.';
                    }
                    $stmt->close();
                }
            }
        }

        if ($error === '') {
            $motoristaDb = $motoristaId > 0 ? $motoristaId : null;
            $odometroDb = $odometro !== '' ? (int) $odometro : null;
            $stmt = $conn->prepare('INSERT INTO abastecimentos (veiculo_id, motorista_id, data_abastecimento, litros, custo_total, odometro_km, posto, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('iisddiss', $veiculoId, $motoristaDb, $data, $litros, $custo, $odometroDb, $posto, $observacoes);
                if ($stmt->execute()) {
                    if ($odometroDb !== null) {
                        $update = $conn->prepare('UPDATE veiculos SET km_total = GREATEST(km_total, ?) WHERE id = ?');
                        if ($update) {
                            $update->bind_param('ii', $odometroDb, $veiculoId);
                            $update->execute();
                            $update->close();
                        }
                    }
                    $success = 'Abastecimento registado com sucesso.';
                } else {
                    $error = 'Não foi possível guardar o abastecimento.';
                }
                $stmt->close();
            }
        }
    }
}

$veiculos = [];
if ($isMotorista) {
    if ($motoristaAtualId !== null) {
        $stmt = $conn->prepare("SELECT DISTINCT v.id, v.matricula, v.modelo
            FROM veiculos v
            LEFT JOIN motoristas m ON m.viatura_atual_id = v.id AND m.id = ?
            LEFT JOIN cargas c ON c.viatura_id = v.id AND c.motorista_id = ?
            WHERE m.id IS NOT NULL OR c.id IS NOT NULL
            ORDER BY v.matricula ASC");
        if ($stmt) {
            $stmt->bind_param('ii', $motoristaAtualId, $motoristaAtualId);
            $stmt->execute();
            $veiculosRes = $stmt->get_result();
        } else {
            $veiculosRes = false;
        }
    } else {
        $veiculosRes = false;
    }
} else {
    $veiculosRes = $conn->query('SELECT id, matricula, modelo FROM veiculos ORDER BY matricula ASC');
}
if ($veiculosRes) {
    while ($row = $veiculosRes->fetch_assoc()) {
        $veiculos[] = $row;
    }
}

$motoristas = [];
if (! $isMotorista) {
    $motoristasRes = $conn->query('SELECT id, nome_completo FROM motoristas ORDER BY nome_completo ASC');
    if ($motoristasRes) {
        while ($row = $motoristasRes->fetch_assoc()) {
            $motoristas[] = $row;
        }
    }
}

$records = [];
if ($isMotorista) {
    if ($motoristaAtualId !== null) {
        $stmt = $conn->prepare("SELECT a.*, v.matricula, v.modelo, m.nome_completo
            FROM abastecimentos a
            JOIN veiculos v ON a.veiculo_id = v.id
            LEFT JOIN motoristas m ON a.motorista_id = m.id
            WHERE a.motorista_id = ?
            ORDER BY a.data_abastecimento DESC, a.id DESC");
        if ($stmt) {
            $stmt->bind_param('i', $motoristaAtualId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = false;
        }
    } else {
        $result = false;
    }
} else {
    $result = $conn->query("SELECT a.*, v.matricula, v.modelo, m.nome_completo
        FROM abastecimentos a
        JOIN veiculos v ON a.veiculo_id = v.id
        LEFT JOIN motoristas m ON a.motorista_id = m.id
        ORDER BY a.data_abastecimento DESC, a.id DESC");
}
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

if ($isMotorista && $motoristaAtualId !== null) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(litros),0) AS litros, COALESCE(SUM(custo_total),0) AS custo FROM abastecimentos WHERE motorista_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $motoristaAtualId);
        $stmt->execute();
        $stats = $stmt->get_result();
    } else {
        $stats = false;
    }
} elseif ($isMotorista) {
    $stats = false;
} else {
    $stats = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(litros),0) AS litros, COALESCE(SUM(custo_total),0) AS custo FROM abastecimentos");
}
$statRow = $stats ? $stats->fetch_assoc() : ['total' => 0, 'litros' => 0, 'custo' => 0];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combustível - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand"><button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button><div><p class="brand-title">Frotalink</p><span class="brand-subtitle">Gestão de Combustível</span></div></div>
        <form class="topbar-search" method="get" action="pesquisa.php"><input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar"></form>
        <div class="topbar-actions">
            <div class="topbar-stats"><span>Olá, <?php echo htmlspecialchars($_SESSION['user']); ?></span><span class="status-pill status-active">Online</span></div><a class="button secondary" href="logout.php">Sair</a>
        </div>
    </header>
    <div class="page-layout">
        <?php include 'sidebar.php'; ?>
        <main class="dashboard-content">
            <section class="dashboard-cards compact-cards">
                <article class="metric-card"><span class="metric-title">Registos</span><strong class="metric-value"><?php echo htmlspecialchars($statRow['total']); ?></strong><span class="metric-caption">Abastecimentos registados</span></article>
                <article class="metric-card"><span class="metric-title">Litros</span><strong class="metric-value"><?php echo htmlspecialchars(number_format((float) $statRow['litros'], 0, ',', '.')); ?></strong><span class="metric-caption">Volume acumulado</span></article>
                <article class="metric-card"><span class="metric-title">Custo</span><strong class="metric-value"><?php echo htmlspecialchars(number_format((float) $statRow['custo'], 2, ',', '.')); ?> EUR</strong><span class="metric-caption">Total de combustível</span></article>
            </section>

            <section class="widget">
                <div class="widget-header"><h2>Novo abastecimento</h2></div>
                <?php if ($error): ?><div class="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert success-alert"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                <?php if ($isMotorista && $motoristaAtualId === null): ?><div class="alert">A sua conta de utilizador ainda não está associada a um registo de motorista.</div><?php endif; ?>
                <?php if ($isMotorista && $motoristaAtualId !== null && count($veiculos) === 0): ?><div class="alert">Não existe nenhuma viatura associada ao seu perfil ou às suas cargas.</div><?php endif; ?>
                <form method="post" action="combustivel_listar.php" class="form-grid">
                    <?php echo csrf_input_field(); ?>
                    <div class="form-field"><label for="veiculo_id">Viatura</label><select id="veiculo_id" name="veiculo_id" required><option value="">Selecionar</option><?php foreach ($veiculos as $v): ?><option value="<?php echo (int) $v['id']; ?>"><?php echo htmlspecialchars($v['matricula'] . ' - ' . $v['modelo']); ?></option><?php endforeach; ?></select></div>
                    <?php if (! $isMotorista): ?><div class="form-field"><label for="motorista_id">Motorista</label><select id="motorista_id" name="motorista_id"><option value="">Sem motorista</option><?php foreach ($motoristas as $m): ?><option value="<?php echo (int) $m['id']; ?>"><?php echo htmlspecialchars($m['nome_completo']); ?></option><?php endforeach; ?></select></div><?php endif; ?>
                    <div class="form-field"><label for="data_abastecimento">Data</label><input type="date" id="data_abastecimento" name="data_abastecimento" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="form-field"><label for="litros">Litros</label><input type="number" step="0.01" min="0" id="litros" name="litros" required></div>
                    <div class="form-field"><label for="custo_total">Custo total</label><input type="number" step="0.01" min="0" id="custo_total" name="custo_total" required></div>
                    <div class="form-field"><label for="odometro_km">Odómetro</label><input type="number" min="0" id="odometro_km" name="odometro_km"></div>
                    <div class="form-field"><label for="posto">Posto</label><input type="text" id="posto" name="posto"></div>
                    <div class="form-field form-field-full"><label for="observacoes">Observações</label><textarea id="observacoes" name="observacoes"></textarea></div>
                    <div class="form-actions"><button type="submit" class="button" <?php echo ($isMotorista && ($motoristaAtualId === null || count($veiculos) === 0)) ? 'disabled' : ''; ?>>Registar</button></div>
                </form>
            </section>

            <section class="widget">
                <div class="widget-header"><h2><?php echo $isMotorista ? 'Os meus abastecimentos' : 'Histórico de abastecimentos'; ?></h2><?php if (canManageOperations()): ?><a class="button secondary" href="relatorios.php?export=combustivel">Exportar CSV</a><?php endif; ?></div>
                <?php if ($records): ?>
                    <div class="table-responsive"><table><thead><tr><th>Data</th><th>Viatura</th><th>Motorista</th><th>Litros</th><th>Custo</th><th>EUR/L</th><th>Odómetro</th><th>Posto</th></tr></thead><tbody>
                    <?php foreach ($records as $row): ?>
                        <tr><td><?php echo htmlspecialchars($row['data_abastecimento']); ?></td><td><?php echo htmlspecialchars($row['matricula'] . ' - ' . $row['modelo']); ?></td><td><?php echo htmlspecialchars($row['nome_completo'] ?: '-'); ?></td><td><?php echo htmlspecialchars(number_format((float) $row['litros'], 2, ',', '.')); ?></td><td><?php echo htmlspecialchars(number_format((float) $row['custo_total'], 2, ',', '.')); ?> EUR</td><td><?php echo (float) $row['litros'] > 0 ? htmlspecialchars(number_format((float) $row['custo_total'] / (float) $row['litros'], 3, ',', '.')) : '-'; ?></td><td><?php echo htmlspecialchars($row['odometro_km'] ?: '-'); ?></td><td><?php echo htmlspecialchars($row['posto'] ?: '-'); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php else: ?><p class="empty-state"><?php echo $isMotorista ? 'Ainda não registou abastecimentos.' : 'Ainda não existem abastecimentos registados.'; ?></p><?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
