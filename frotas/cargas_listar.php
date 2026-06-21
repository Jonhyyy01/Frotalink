<?php
require_once 'config.php';
requireLogin();

$activePage = 'cargas';
$conn = getDbConnection();
$success = '';
$error = '';
$motoristaId = getCurrentMotoristaId($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isMotorista()) {
    $action = $_POST['action'] ?? '';
    $cargaId = (int) ($_POST['carga_id'] ?? 0);

    if ($action === 'mark_in_transit' && $cargaId > 0 && $motoristaId !== null) {
        $stmt = $conn->prepare("UPDATE cargas SET estado_carga = 'Em Trânsito', data_hora_recolha_real = NOW() WHERE id = ? AND motorista_id = ? AND estado_carga = 'Pendente'");
        if ($stmt) {
            $stmt->bind_param('ii', $cargaId, $motoristaId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $success = 'Carga marcada como em trânsito com sucesso.';
            } else {
                $error = 'Não foi possível marcar esta carga como em trânsito.';
            }
            $stmt->close();
        }
    } elseif ($action === 'mark_delivered' && $cargaId > 0 && $motoristaId !== null) {
        $stmt = $conn->prepare("UPDATE cargas SET estado_carga = 'Entregue', data_hora_entrega_real = NOW() WHERE id = ? AND motorista_id = ? AND estado_carga = 'Em Trânsito'");
        if ($stmt) {
            $stmt->bind_param('ii', $cargaId, $motoristaId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $success = 'Carga marcada como entregue com sucesso.';
            } else {
                $error = 'Não foi possível marcar esta carga como entregue.';
            }
            $stmt->close();
        }
    }
}

$baseQuery = "SELECT c.id, c.codigo_rastreio, c.estado_carga, c.tipo_carga, c.peso_kg, c.quantidade_paletes, c.local_recolha, c.local_entrega, c.data_hora_recolha_prevista, c.data_hora_entrega_prevista, c.valor_transporte, c.pago, c.created_at, v.matricula AS veiculo_matricula, m.nome_completo AS motorista_nome, cl.nome AS cliente_nome, cl.nif_nipc AS cliente_nif FROM cargas c LEFT JOIN veiculos v ON c.viatura_id = v.id LEFT JOIN motoristas m ON c.motorista_id = m.id LEFT JOIN clientes cl ON c.cliente_id = cl.id";

if (isMotorista()) {
    if ($motoristaId === null) {
        $result = false;
    } else {
        $stmt = $conn->prepare($baseQuery . " WHERE c.motorista_id = ? AND c.estado_carga IN ('Pendente', 'Em Trânsito') ORDER BY c.created_at DESC");
        $stmt->bind_param('i', $motoristaId);
        $stmt->execute();
        $result = $stmt->get_result();
    }
} else {
    $result = $conn->query($baseQuery . ' ORDER BY c.created_at DESC');
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargas - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Gestão de Cargas</span>
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
                    <h2>Cargas</h2>
                    <?php if (canManageOperations()): ?>
                        <a class="button" href="cargas_criar.php">Nova Carga</a>
                    <?php endif; ?>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert success-alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Estado</th>
                                    <th>Tipo</th>
                                    <th>Peso (kg)</th>
                                    <th>Paletes</th>
                                    <th>Cliente</th>
                                    <th>NIF</th>
                                    <th>Valor</th>
                                    <th>Pagamento</th>
                                    <th>Viatura</th>
                                    <th>Motorista</th>
                                    <th>Recolha prevista</th>
                                    <th>Entrega prevista</th>
                                    <th>Criado em</th>
                                    <?php if (canManageOperations() || isMotorista()): ?>
                                        <th>Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['codigo_rastreio']); ?></td>
                                        <td><?php echo htmlspecialchars($row['estado_carga']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo_carga']); ?></td>
                                        <td><?php echo htmlspecialchars($row['peso_kg']); ?></td>
                                        <td><?php echo htmlspecialchars($row['quantidade_paletes']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cliente_nome'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['cliente_nif'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars(number_format((float) $row['valor_transporte'], 2, ',', '.')); ?> EUR</td>
                                        <td><?php echo (int) $row['pago'] === 1 ? 'Pago' : 'Por pagar'; ?></td>
                                        <td><?php echo htmlspecialchars($row['veiculo_matricula'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['motorista_nome'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['data_hora_recolha_prevista']); ?></td>
                                        <td><?php echo htmlspecialchars($row['data_hora_entrega_prevista']); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <?php if (canManageOperations()): ?>
                                            <td>
                                                <a class="action-link" href="cargas_editar.php?id=<?php echo urlencode($row['id']); ?>">Editar</a>
                                                <a class="action-link danger" href="cargas_apagar.php?id=<?php echo urlencode($row['id']); ?>">Apagar</a>
                                            </td>
                                        <?php elseif (isMotorista()): ?>
                                            <td>
                                                <?php if ($row['estado_carga'] === 'Pendente'): ?>
                                                    <form method="post" action="cargas_listar.php" onsubmit="return confirm('Confirmar que esta carga está em trânsito?');">
                                                        <input type="hidden" name="action" value="mark_in_transit">
                                                        <input type="hidden" name="carga_id" value="<?php echo (int) $row['id']; ?>">
                                                        <button type="submit" class="button">Em trânsito</button>
                                                    </form>
                                                <?php elseif ($row['estado_carga'] === 'Em Trânsito'): ?>
                                                    <form method="post" action="cargas_listar.php" onsubmit="return confirm('Confirmar que esta carga foi entregue?');">
                                                        <input type="hidden" name="action" value="mark_delivered">
                                                        <input type="hidden" name="carga_id" value="<?php echo (int) $row['id']; ?>">
                                                        <button type="submit" class="button">Entregue</button>
                                                    </form>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p><?php echo isMotorista() ? 'Não existem cargas atribuídas a este motorista.' : 'Nenhuma carga encontrada.'; ?></p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
