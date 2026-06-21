<?php
require_once 'config.php';
requireLogin();

$activePage = 'avarias';
$conn = getDbConnection();

$error = '';
$success = '';
$titulo = '';
$descricao = '';
$prioridade = 'Média';
$viatura_id = '';
$carga_id = '';

$motoristaId = getCurrentMotoristaId($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete' && canManageOperations()) {
        $avariaId = (int) ($_POST['avaria_id'] ?? 0);

        if ($avariaId <= 0) {
            $error = 'Pedido de eliminação inválido.';
        } else {
            $stmt = $conn->prepare('DELETE FROM avarias_problemas WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $avariaId);
                if ($stmt->execute()) {
                    $success = 'Avaria/problema eliminado com sucesso.';
                } else {
                    $error = 'Erro ao eliminar a avaria/problema.';
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'update' && canManageOperations()) {
        $avariaId = (int) ($_POST['avaria_id'] ?? 0);
        $status = $_POST['status'] ?? 'Aberto';
        $resposta = trim($_POST['resposta_gestor'] ?? '');
        $allowedStatus = ['Aberto', 'Em análise', 'Resolvido', 'Fechado'];

        if ($avariaId <= 0 || !in_array($status, $allowedStatus, true)) {
            $error = 'Pedido de atualização inválido.';
        } else {
            $resolverId = in_array($status, ['Resolvido', 'Fechado'], true) ? currentUserId() : null;
            $stmt = $conn->prepare('UPDATE avarias_problemas SET status = ?, resposta_gestor = ?, resolvido_por_id = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('ssii', $status, $resposta, $resolverId, $avariaId);
                if ($stmt->execute()) {
                    $success = 'Avaria/problema atualizado com sucesso.';
                } else {
                    $error = 'Erro ao atualizar a avaria/problema.';
                }
                $stmt->close();
            }
        }
    } else {
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $prioridade = $_POST['prioridade'] ?? 'Média';
        $viatura_id = trim($_POST['viatura_id'] ?? '');
        $carga_id = trim($_POST['carga_id'] ?? '');
        $allowedPriorities = ['Baixa', 'Média', 'Alta', 'Crítica'];

        if ($titulo === '' || $descricao === '') {
            $error = 'Preencha o título e a descrição.';
        } elseif (!in_array($prioridade, $allowedPriorities, true)) {
            $error = 'Prioridade inválida.';
        } else {
            $viaturaValue = $viatura_id === '' ? null : (int) $viatura_id;
            $cargaValue = $carga_id === '' ? null : (int) $carga_id;

            if (isMotorista() && $cargaValue !== null) {
                $stmt = $conn->prepare('SELECT id FROM cargas WHERE id = ? AND motorista_id = ? LIMIT 1');
                $stmt->bind_param('ii', $cargaValue, $motoristaId);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows === 0) {
                    $error = 'A carga selecionada não pertence a este motorista.';
                }
                $stmt->close();
            }

            if ($error === '') {
                $reporterId = currentUserId();
                $stmt = $conn->prepare('INSERT INTO avarias_problemas (titulo, descricao, prioridade, viatura_id, carga_id, reportado_por_id) VALUES (?, ?, ?, ?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sssiii', $titulo, $descricao, $prioridade, $viaturaValue, $cargaValue, $reporterId);
                    if ($stmt->execute()) {
                        $success = 'Avaria/problema reportado com sucesso.';
                        $titulo = '';
                        $descricao = '';
                        $prioridade = 'Média';
                        $viatura_id = '';
                        $carga_id = '';
                    } else {
                        $error = 'Erro ao reportar a avaria/problema.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

if (isMotorista() && $motoristaId !== null) {
    $stmt = $conn->prepare('SELECT v.id, v.matricula FROM veiculos v INNER JOIN motoristas m ON m.viatura_atual_id = v.id WHERE m.id = ? ORDER BY v.matricula ASC');
    $stmt->bind_param('i', $motoristaId);
    $stmt->execute();
    $veiculosRes = $stmt->get_result();

    $stmt = $conn->prepare("SELECT id, codigo_rastreio FROM cargas WHERE motorista_id = ? AND estado_carga IN ('Pendente', 'Em Trânsito') ORDER BY created_at DESC");
    $stmt->bind_param('i', $motoristaId);
    $stmt->execute();
    $cargasRes = $stmt->get_result();
} elseif (isMotorista()) {
    $veiculosRes = false;
    $cargasRes = false;
} else {
    $veiculosRes = $conn->query('SELECT id, matricula FROM veiculos ORDER BY matricula ASC');
    $cargasRes = $conn->query('SELECT id, codigo_rastreio FROM cargas ORDER BY created_at DESC');
}

$listQuery = "SELECT a.id, a.titulo, a.descricao, a.prioridade, a.status, a.resposta_gestor, a.criado_em, a.atualizado_em, u.nome AS reportado_por, r.nome AS resolvido_por, v.matricula, c.codigo_rastreio FROM avarias_problemas a LEFT JOIN utilizadores u ON a.reportado_por_id = u.id LEFT JOIN utilizadores r ON a.resolvido_por_id = r.id LEFT JOIN veiculos v ON a.viatura_id = v.id LEFT JOIN cargas c ON a.carga_id = c.id";
if (isMotorista()) {
    $stmt = $conn->prepare($listQuery . ' WHERE a.reportado_por_id = ? ORDER BY a.criado_em DESC');
    $userId = currentUserId();
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $avariasRes = $stmt->get_result();
} else {
    $avariasRes = $conn->query($listQuery . ' ORDER BY a.criado_em DESC');
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avarias / Problemas - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Avarias / Problemas</span>
            </div>
        </div>
        <form class="topbar-search" method="get" action="pesquisa.php"><input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar"></form>
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
            <section class="widget">
                <div class="widget-header">
                    <h2>Reportar avaria ou problema</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert success-alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="post" action="avarias_listar.php" class="form-grid">
                    <input type="hidden" name="action" value="create">

                    <div class="form-field">
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($titulo); ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="prioridade">Prioridade</label>
                        <select id="prioridade" name="prioridade">
                            <?php foreach (['Baixa', 'Média', 'Alta', 'Crítica'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $prioridade === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="viatura_id">Viatura</label>
                        <select id="viatura_id" name="viatura_id">
                            <option value="">-- Sem viatura --</option>
                            <?php if ($veiculosRes): while ($v = $veiculosRes->fetch_assoc()): ?>
                                <option value="<?php echo (int) $v['id']; ?>" <?php echo (string) $viatura_id === (string) $v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['matricula']); ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="carga_id">Carga relacionada</label>
                        <select id="carga_id" name="carga_id">
                            <option value="">-- Sem carga --</option>
                            <?php if ($cargasRes): while ($c = $cargasRes->fetch_assoc()): ?>
                                <option value="<?php echo (int) $c['id']; ?>" <?php echo (string) $carga_id === (string) $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['codigo_rastreio'] ?: ('Carga #' . $c['id'])); ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="form-field form-field-full">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($descricao); ?></textarea>
                    </div>

                    <div class="form-actions form-field-full">
                        <button type="submit" class="button">Reportar</button>
                    </div>
                </form>
            </section>

            <section class="widget">
                <div class="widget-header">
                    <h2><?php echo isMotorista() ? 'Os meus problemas reportados' : 'Avarias e problemas reportados'; ?></h2>
                </div>

                <?php if ($avariasRes && $avariasRes->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Prioridade</th>
                                    <th>Status</th>
                                    <th>Viatura</th>
                                    <th>Carga</th>
                                    <th>Reportado por</th>
                                    <th>Criado em</th>
                                    <th>Resposta</th>
                                    <?php if (canManageOperations()): ?>
                                        <th>Resolver</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $avariasRes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['titulo']); ?></strong><br>
                                            <span class="table-muted"><?php echo htmlspecialchars($row['descricao']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['prioridade']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td><?php echo htmlspecialchars($row['matricula'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['codigo_rastreio'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['reportado_por'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['criado_em']); ?></td>
                                        <td><?php echo htmlspecialchars($row['resposta_gestor'] ?: '-'); ?></td>
                                        <?php if (canManageOperations()): ?>
                                            <td>
                                                <form method="post" action="avarias_listar.php" class="table-form">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="avaria_id" value="<?php echo (int) $row['id']; ?>">
                                                    <select name="status">
                                                        <?php foreach (['Aberto', 'Em análise', 'Resolvido', 'Fechado'] as $statusOption): ?>
                                                            <option value="<?php echo htmlspecialchars($statusOption); ?>" <?php echo $row['status'] === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <textarea name="resposta_gestor" placeholder="Resposta do gestor"><?php echo htmlspecialchars($row['resposta_gestor'] ?? ''); ?></textarea>
                                                    <button type="submit" class="button">Guardar</button>
                                                </form>
                                                <form method="post" action="avarias_listar.php" class="table-form delete-form" onsubmit="return confirm('Tem a certeza que pretende eliminar esta avaria/problema?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="avaria_id" value="<?php echo (int) $row['id']; ?>">
                                                    <button type="submit" class="button danger">Apagar</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhuma avaria ou problema registado.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
