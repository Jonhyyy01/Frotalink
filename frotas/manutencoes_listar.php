<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'manutencoes';
$conn = getDbConnection();
$csrf = create_csrf_token();
$pendingMessage = '';
$pendingError = '';

function normalizeMoneyInput(string $value): ?string {
    $normalized = str_replace(',', '.', trim($value));
    if ($normalized === '' || !is_numeric($normalized) || (float) $normalized < 0) {
        return null;
    }
    return number_format((float) $normalized, 2, '.', '');
}

function pendingPriorityToHistoryPriority(string $criticidade): string {
    $map = [
        'baixo' => 'Baixa',
        'medio' => 'Média',
        'alto' => 'Alta',
        'critico' => 'Crítica',
    ];
    return $map[strtolower($criticidade)] ?? 'Média';
}

function pendingTypeToHistoryType(string $tipo): string {
    return stripos($tipo, 'inspe') !== false ? 'Inspeção' : 'Manutenção Preventiva';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pendingId = (int) ($_POST['pending_id'] ?? 0);

    if ($pendingId > 0 && verify_csrf_token($_POST['csrf_token'] ?? '')) {
        if ($action === 'complete_pending') {
            $inspectionCost = normalizeMoneyInput($_POST['inspection_cost'] ?? '');
            if ($inspectionCost === null) {
                $pendingError = 'Indique um valor válido para concluir a inspeção.';
            } else {
                $pendingStmt = $conn->prepare("SELECT id, veiculo_id, tipo, criticidade, descricao, data_agendada FROM manutencoes WHERE id = ? AND status = 'pendente'");
                if ($pendingStmt) {
                    $pendingStmt->bind_param('i', $pendingId);
                    $pendingStmt->execute();
                    $pendingRecord = $pendingStmt->get_result()->fetch_assoc();
                    $pendingStmt->close();
                } else {
                    $pendingRecord = null;
                }

                if (!$pendingRecord) {
                    $pendingError = 'Manutenção pendente não encontrada.';
                } else {
                    $conn->begin_transaction();
                    $stmt = $conn->prepare("UPDATE manutencoes SET status = 'concluida' WHERE id = ?");
                    if (!$stmt) {
                        $conn->rollback();
                        $pendingError = 'Não foi possível concluir a manutenção.';
                    } else {
                        $stmt->bind_param('i', $pendingId);
                        $updated = $stmt->execute();
                        $stmt->close();

                        if (!$updated) {
                            $conn->rollback();
                            $pendingError = 'Não foi possível concluir a manutenção.';
                        }
                    }

                    if (!$pendingError) {
                        $tipoHistorico = pendingTypeToHistoryType($pendingRecord['tipo']);
                        $prioridadeHistorico = pendingPriorityToHistoryPriority($pendingRecord['criticidade']);
                        $statusHistorico = 'Concluído';
                        $acoesRealizadas = 'Concluído a partir das manutenções pendentes.';
                        $custoPecas = '0.00';
                        $urlRelatorio = '';

                        $historyStmt = $conn->prepare('INSERT INTO historico_manutencoes_inspecoes (equipamento_id, tecnico_id, tipo_acao, status, prioridade, data_agendada, data_fim, descricao_problema, acoes_realizadas, custo_pecas, custo_mao_de_obra, custo_total, url_relatorio_pdf) VALUES (?, NULL, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)');
                        if ($historyStmt) {
                            $historyStmt->bind_param('issssssddds', $pendingRecord['veiculo_id'], $tipoHistorico, $statusHistorico, $prioridadeHistorico, $pendingRecord['data_agendada'], $pendingRecord['descricao'], $acoesRealizadas, $custoPecas, $inspectionCost, $inspectionCost, $urlRelatorio);
                            if ($historyStmt->execute()) {
                                $conn->commit();
                                $pendingMessage = 'Manutenção marcada como concluída e valor registado.';
                            } else {
                                $conn->rollback();
                                $pendingError = 'Não foi possível registar o valor no histórico.';
                            }
                            $historyStmt->close();
                        } else {
                            $conn->rollback();
                            $pendingError = 'Não foi possível preparar o registo no histórico.';
                        }
                    }
                }
            }
        } elseif ($action === 'delete_pending') {
            $stmt = $conn->prepare('DELETE FROM manutencoes WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $pendingId);
                $stmt->execute();
                $pendingMessage = 'Manutenção pendente apagada.';
                $stmt->close();
            }
        }
    } elseif ($action === 'complete_pending' || $action === 'delete_pending') {
        $pendingError = 'Pedido inválido. Tente novamente.';
    }
}

$search = trim($_GET['q'] ?? '');
$filterVeiculo = trim($_GET['veiculo'] ?? '');
$filterTipo = trim($_GET['tipo'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$veiculos = [];
$veiculosRes = $conn->query('SELECT id, modelo, matricula FROM veiculos ORDER BY id DESC');
if ($veiculosRes) {
    while ($row = $veiculosRes->fetch_assoc()) {
        $veiculos[] = $row;
    }
}

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = '(m.descricao_problema LIKE ? OR m.acoes_realizadas LIKE ? OR m.url_relatorio_pdf LIKE ?)';
    $searchLike = '%' . $search . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= 'sss';
}

if ($filterVeiculo !== '' && ctype_digit($filterVeiculo)) {
    $where[] = 'm.equipamento_id = ?';
    $params[] = (int) $filterVeiculo;
    $types .= 'i';
}

if ($filterTipo !== '') {
    $where[] = 'm.tipo_acao = ?';
    $params[] = $filterTipo;
    $types .= 's';
}

if ($filterStatus !== '') {
    $where[] = 'm.status = ?';
    $params[] = $filterStatus;
    $types .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$records = [];
$query = "SELECT m.*, CONCAT(v.modelo, ' (', v.matricula, ')') AS veiculo_label, u.nome AS tecnico_nome
    FROM historico_manutencoes_inspecoes m
    LEFT JOIN veiculos v ON m.equipamento_id = v.id
    LEFT JOIN utilizadores u ON m.tecnico_id = u.id
    $whereSql
    ORDER BY m.id DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
}

$pendingMaintenances = [];
$pendingQuery = "SELECT m.id, m.tipo, m.status, m.criticidade, m.descricao, m.data_agendada, CONCAT(v.modelo, ' (', v.matricula, ')') AS veiculo_label
    FROM manutencoes m
    LEFT JOIN veiculos v ON m.veiculo_id = v.id
    WHERE m.status = 'pendente'
    ORDER BY FIELD(m.criticidade, 'critico', 'alto', 'medio', 'baixo'), m.data_agendada ASC";
$pendingRes = $conn->query($pendingQuery);
if ($pendingRes) {
    while ($row = $pendingRes->fetch_assoc()) {
        $pendingMaintenances[] = $row;
    }
}

$totalFiltrado = count($records);
$totalGeral = 0;
$custoTotal = 0.0;
$concluidas = 0;

$totRes = $conn->query("SELECT COUNT(*) AS cnt, IFNULL(SUM(custo_total), 0) AS total_custo, SUM(IF(status = 'Concluído' OR status = 'Concluído', 1, 0)) AS concluidas FROM historico_manutencoes_inspecoes");
if ($totRes) {
    $totRow = $totRes->fetch_assoc();
    $totalGeral = (int) ($totRow['cnt'] ?? 0);
    $custoTotal = (float) ($totRow['total_custo'] ?? 0);
    $concluidas = (int) ($totRow['concluidas'] ?? 0);
}

$tipos = ['Inspeção', 'Manutenção Preventiva', 'Manutenção Corretiva', 'Preditiva'];
$statusOptions = ['Agendado', 'Em Andamento', 'Concluído', 'Cancelado'];

function maintenanceBadgeClass(string $value): string {
    $normalized = strtolower($value);
    if (strpos($normalized, 'conclu') !== false || strpos($normalized, 'aprovado') !== false) {
        return 'badge-success';
    }
    if (strpos($normalized, 'cancel') !== false || strpos($normalized, 'reprovado') !== false) {
        return 'badge-danger';
    }
    if (strpos($normalized, 'andamento') !== false || strpos($normalized, 'alta') !== false || strpos($normalized, 'crit') !== false) {
        return 'badge-warning';
    }
    return 'badge-neutral';
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspeções / Manutenções - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Inspeções / Manutenções</span>
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
            <section class="dashboard-cards compact-cards">
                <article class="metric-card">
                    <span class="metric-title">Registos filtrados</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($totalFiltrado); ?></strong>
                    <span class="metric-caption">Resultado da pesquisa atual</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">Total geral</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($totalGeral); ?></strong>
                    <span class="metric-caption">Inspeções e manutenções</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">Concluídas</span>
                    <strong class="metric-value"><?php echo htmlspecialchars($concluidas); ?></strong>
                    <span class="metric-caption">Registos finalizados</span>
                </article>
                <article class="metric-card">
                    <span class="metric-title">Custo total</span>
                    <strong class="metric-value"><?php echo htmlspecialchars(number_format($custoTotal, 2, ',', '.')); ?> €</strong>
                    <span class="metric-caption">Valor estimado acumulado</span>
                </article>
            </section>

            <section class="widget">
                <div class="widget-header">
                    <div>
                        <h2>Manutenções pendentes</h2>
                        <p class="section-subtitle">Estes são os alertas que aparecem no painel principal.</p>
                    </div>
                </div>

                <?php if ($pendingError): ?>
                    <div class="alert"><?php echo htmlspecialchars($pendingError); ?></div>
                <?php endif; ?>
                <?php if ($pendingMessage): ?>
                    <div class="alert success-alert"><?php echo htmlspecialchars($pendingMessage); ?></div>
                <?php endif; ?>

                <?php if ($pendingMaintenances): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Viatura</th>
                                    <th>Tipo</th>
                                    <th>Criticidade</th>
                                    <th>Data agendada</th>
                                    <th>Descrição</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingMaintenances as $pending): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pending['id']); ?></td>
                                        <td><?php echo htmlspecialchars($pending['veiculo_label'] ?: 'Sem viatura'); ?></td>
                                        <td><?php echo htmlspecialchars($pending['tipo']); ?></td>
                                        <td><span class="badge <?php echo maintenanceBadgeClass($pending['criticidade']); ?>"><?php echo htmlspecialchars($pending['criticidade']); ?></span></td>
                                        <td><?php echo htmlspecialchars($pending['data_agendada'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($pending['descricao'] ?: '-'); ?></td>
                                        <td class="table-actions">
                                            <form method="post" action="manutencoes_listar.php" class="inline-form">
                                                <?php echo csrf_input_field(); ?>
                                                <input type="hidden" name="action" value="complete_pending">
                                                <input type="hidden" name="pending_id" value="<?php echo (int) $pending['id']; ?>">
                                                <input type="hidden" name="inspection_cost" value="">
                                                <button type="submit" class="button">Concluir</button>
                                            </form>
                                            <form method="post" action="manutencoes_listar.php" class="inline-form" onsubmit="return confirm('Apagar esta manutenção pendente?');">
                                                <?php echo csrf_input_field(); ?>
                                                <input type="hidden" name="action" value="delete_pending">
                                                <input type="hidden" name="pending_id" value="<?php echo (int) $pending['id']; ?>">
                                                <button type="submit" class="button danger">Apagar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Nenhuma manutenção pendente.</p>
                <?php endif; ?>
            </section>

            <section class="widget">
                <div class="widget-header">
                    <div>
                        <h2>Histórico de inspeções</h2>
                        <p class="section-subtitle">Acompanhe estados, custos, técnicos e próximos trabalhos.</p>
                    </div>
                    <a class="button" href="manutencoes_criar.php">Novo Registo</a>
                </div>

                <form method="get" action="manutencoes_listar.php" class="filter-form">
                    <div class="filter-row">
                        <label for="q">Pesquisar</label>
                        <input type="search" id="q" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Problema, ação ou relatório">
                    </div>
                    <div class="filter-row">
                        <label for="veiculo">Viatura</label>
                        <select id="veiculo" name="veiculo">
                            <option value="">Todas</option>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <option value="<?php echo htmlspecialchars($veiculo['id']); ?>" <?php echo $filterVeiculo === (string) $veiculo['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($veiculo['matricula'] . ' - ' . $veiculo['modelo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-row">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo $filterTipo === $tipo ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-row">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>
                            <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?php echo htmlspecialchars($statusOption); ?>" <?php echo $filterStatus === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="button">Filtrar</button>
                        <a class="button secondary" href="manutencoes_listar.php">Limpar</a>
                    </div>
                </form>

                <?php if ($records): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Viatura</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Prioridade</th>
                                    <th>Agendada</th>
                                    <th>Técnico</th>
                                    <th>Custo</th>
                                    <th>Próxima revisão</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['veiculo_label'] ?: 'Sem viatura'); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo_acao']); ?></td>
                                        <td><span class="badge <?php echo maintenanceBadgeClass($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td><span class="badge <?php echo maintenanceBadgeClass($row['prioridade']); ?>"><?php echo htmlspecialchars($row['prioridade']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['data_agendada'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['tecnico_nome'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars(number_format((float) $row['custo_total'], 2, ',', '.')); ?> €</td>
                                        <td><?php echo htmlspecialchars($row['proxima_revisao'] ?: '-'); ?></td>
                                        <td class="table-actions">
                                            <a class="action-link" href="manutencoes_editar.php?id=<?php echo urlencode($row['id']); ?>">Editar</a>
                                            <a class="action-link danger" href="manutencoes_apagar.php?id=<?php echo urlencode($row['id']); ?>&csrf=<?php echo urlencode($csrf); ?>">Apagar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Nenhum registo encontrado.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script>
        document.querySelectorAll('form.inline-form input[name="action"][value="complete_pending"]').forEach((actionInput) => {
            actionInput.form.addEventListener('submit', (event) => {
                const costInput = actionInput.form.querySelector('input[name="inspection_cost"]');
                const value = prompt('Valor da inspeção (€):');

                if (value === null) {
                    event.preventDefault();
                    return;
                }

                const normalized = value.trim().replace(',', '.');
                const amount = Number(normalized);
                if (!normalized || Number.isNaN(amount) || amount < 0) {
                    event.preventDefault();
                    alert('Indique um valor válido para a inspeção.');
                    return;
                }

                costInput.value = normalized;
            });
        });
    </script>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
