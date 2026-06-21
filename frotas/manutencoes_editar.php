<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'manutencoes';
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: manutencoes_listar.php');
    exit;
}

$conn = getDbConnection();
$stmt = $conn->prepare('SELECT * FROM historico_manutencoes_inspecoes WHERE id = ?');
if (!$stmt) {
    die('Erro na base de dados: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$record = $res->fetch_assoc();
$stmt->close();

if (!$record) {
    header('Location: manutencoes_listar.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF inválido.';
    } else {
        $equipamento_id = trim($_POST['equipamento_id'] ?? '');
        $tecnico_id = trim($_POST['tecnico_id'] ?? '');
        $tipo_acao = trim($_POST['tipo_acao'] ?? 'Inspeção');
        $status = trim($_POST['status'] ?? 'Agendado');
        $prioridade = trim($_POST['prioridade'] ?? 'Média');

        $data_agendada = trim($_POST['data_agendada'] ?? '') ?: null;
        $data_inicio = trim($_POST['data_inicio'] ?? '') ?: null;
        $data_fim = trim($_POST['data_fim'] ?? '') ?: null;
        $proxima_revisao = trim($_POST['proxima_revisao'] ?? '') ?: null;

        $descricao_problema = trim($_POST['descricao_problema'] ?? '');
        $acoes_realizadas = trim($_POST['acoes_realizadas'] ?? '');
        $resultado_inspecao = trim($_POST['resultado_inspecao'] ?? '');
        $leitura_odometro_horas = trim($_POST['leitura_odometro_horas'] ?? '') ?: null;

        $custo_pecas = trim($_POST['custo_pecas'] ?? '0') ?: '0.00';
        $custo_mao_de_obra = trim($_POST['custo_mao_de_obra'] ?? '0') ?: '0.00';
        $custo_total = number_format((float) $custo_pecas + (float) $custo_mao_de_obra, 2, '.', '');
        $url_relatorio_pdf = trim($_POST['url_relatorio_pdf'] ?? '');

        if ($equipamento_id === '' || !ctype_digit($equipamento_id)) {
            $error = 'Selecione uma viatura válida.';
        } else {
            $equipamento_id = (int) $equipamento_id;
            $tecnico_id = $tecnico_id !== '' && ctype_digit($tecnico_id) ? (int) $tecnico_id : null;

            $uStmt = $conn->prepare('UPDATE historico_manutencoes_inspecoes SET equipamento_id=?, tecnico_id=?, tipo_acao=?, status=?, prioridade=?, data_agendada=?, data_inicio=?, data_fim=?, proxima_revisao=?, descricao_problema=?, acoes_realizadas=?, resultado_inspecao=?, leitura_odometro_horas=?, custo_pecas=?, custo_mao_de_obra=?, custo_total=?, url_relatorio_pdf=? WHERE id=?');
            if ($uStmt) {
                $uStmt->bind_param('iissssssssssdiddsi', $equipamento_id, $tecnico_id, $tipo_acao, $status, $prioridade, $data_agendada, $data_inicio, $data_fim, $proxima_revisao, $descricao_problema, $acoes_realizadas, $resultado_inspecao, $leitura_odometro_horas, $custo_pecas, $custo_mao_de_obra, $custo_total, $url_relatorio_pdf, $id);
                if ($uStmt->execute()) {
                    header('Location: manutencoes_listar.php');
                    exit;
                }
                $error = 'Erro ao atualizar o registo.';
                $uStmt->close();
            } else {
                $error = 'Erro na preparação da query: ' . htmlspecialchars($conn->error);
            }
        }
    }
}

$stmt = $conn->prepare('SELECT * FROM historico_manutencoes_inspecoes WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

$veiculosRes = $conn->query('SELECT id, matricula, modelo FROM veiculos ORDER BY id DESC');
$usersRes = $conn->query('SELECT id, nome FROM utilizadores ORDER BY id DESC');

function datetimeLocalValue($value) {
    if (!$value) {
        return '';
    }
    return htmlspecialchars(str_replace(' ', 'T', substr($value, 0, 16)));
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Inspeção - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Editar Inspeção / Manutenção</span>
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
                    <div>
                        <h2>Editar registo</h2>
                        <p class="section-subtitle">Atualize os dados técnicos, datas, custos e resultado da inspeção.</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="manutencoes_editar.php?id=<?php echo urlencode($id); ?>" class="form-grid">
                    <?php echo csrf_input_field(); ?>

                    <div class="form-field">
                        <label for="equipamento_id">Equipamento / Viatura</label>
                        <select id="equipamento_id" name="equipamento_id" required>
                            <option value="">Selecionar viatura</option>
                            <?php if ($veiculosRes): while ($v = $veiculosRes->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($v['id']); ?>" <?php echo (int) $record['equipamento_id'] === (int) $v['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v['matricula'] . ' - ' . $v['modelo']); ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="tecnico_id">Técnico / Utilizador</label>
                        <select id="tecnico_id" name="tecnico_id">
                            <option value="">Nenhum</option>
                            <?php if ($usersRes): while ($u = $usersRes->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($u['id']); ?>" <?php echo (int) ($record['tecnico_id'] ?? 0) === (int) $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['nome']); ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="tipo_acao">Tipo de ação</label>
                        <select id="tipo_acao" name="tipo_acao">
                            <?php foreach (['Inspeção', 'Manutenção Preventiva', 'Manutenção Corretiva', 'Preditiva'] as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo $record['tipo_acao'] === $tipo ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <?php foreach (['Agendado', 'Em Andamento', 'Concluído', 'Cancelado'] as $estado): ?>
                                <option value="<?php echo htmlspecialchars($estado); ?>" <?php echo $record['status'] === $estado ? 'selected' : ''; ?>><?php echo htmlspecialchars($estado); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="prioridade">Prioridade</label>
                        <select id="prioridade" name="prioridade">
                            <?php foreach (['Baixa', 'Média', 'Alta', 'Crítica'] as $prioridade): ?>
                                <option value="<?php echo htmlspecialchars($prioridade); ?>" <?php echo $record['prioridade'] === $prioridade ? 'selected' : ''; ?>><?php echo htmlspecialchars($prioridade); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="data_agendada">Data agendada</label>
                        <input type="date" id="data_agendada" name="data_agendada" value="<?php echo htmlspecialchars($record['data_agendada'] ?? ''); ?>">
                    </div>

                    <div class="form-field">
                        <label for="data_inicio">Data / hora início</label>
                        <input type="datetime-local" id="data_inicio" name="data_inicio" value="<?php echo datetimeLocalValue($record['data_inicio'] ?? ''); ?>">
                    </div>

                    <div class="form-field">
                        <label for="data_fim">Data / hora fim</label>
                        <input type="datetime-local" id="data_fim" name="data_fim" value="<?php echo datetimeLocalValue($record['data_fim'] ?? ''); ?>">
                    </div>

                    <div class="form-field">
                        <label for="proxima_revisao">Próxima revisão</label>
                        <input type="date" id="proxima_revisao" name="proxima_revisao" value="<?php echo htmlspecialchars($record['proxima_revisao'] ?? ''); ?>">
                    </div>

                    <div class="form-field">
                        <label for="resultado_inspecao">Resultado da inspeção</label>
                        <select id="resultado_inspecao" name="resultado_inspecao">
                            <option value="">Não aplicável</option>
                            <?php foreach (['Aprovado', 'Aprovado com Restrições', 'Reprovado'] as $resultado): ?>
                                <option value="<?php echo htmlspecialchars($resultado); ?>" <?php echo $record['resultado_inspecao'] === $resultado ? 'selected' : ''; ?>><?php echo htmlspecialchars($resultado); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="leitura_odometro_horas">Leitura (km / horas)</label>
                        <input type="number" step="0.01" id="leitura_odometro_horas" name="leitura_odometro_horas" value="<?php echo htmlspecialchars($record['leitura_odometro_horas'] ?? ''); ?>">
                    </div>

                    <div class="form-field">
                        <label for="custo_pecas">Custo peças (€)</label>
                        <input type="number" step="0.01" id="custo_pecas" name="custo_pecas" value="<?php echo htmlspecialchars($record['custo_pecas'] ?? '0.00'); ?>">
                    </div>

                    <div class="form-field">
                        <label for="custo_mao_de_obra">Custo mão de obra (€)</label>
                        <input type="number" step="0.01" id="custo_mao_de_obra" name="custo_mao_de_obra" value="<?php echo htmlspecialchars($record['custo_mao_de_obra'] ?? '0.00'); ?>">
                    </div>

                    <div class="form-field form-field-full">
                        <label for="descricao_problema">Descrição do problema</label>
                        <textarea id="descricao_problema" name="descricao_problema"><?php echo htmlspecialchars($record['descricao_problema'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-field form-field-full">
                        <label for="acoes_realizadas">Ações realizadas</label>
                        <textarea id="acoes_realizadas" name="acoes_realizadas"><?php echo htmlspecialchars($record['acoes_realizadas'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-field form-field-full">
                        <label for="url_relatorio_pdf">URL relatório / fotos</label>
                        <input type="url" id="url_relatorio_pdf" name="url_relatorio_pdf" value="<?php echo htmlspecialchars($record['url_relatorio_pdf'] ?? ''); ?>">
                    </div>

                    <div class="form-summary form-field-full">
                        <span>Total estimado</span>
                        <strong id="custo_total_view"><?php echo htmlspecialchars(number_format((float) ($record['custo_total'] ?? 0), 2, ',', '.')); ?> €</strong>
                    </div>

                    <div class="form-actions form-field-full">
                        <button type="submit" class="button">Guardar Alterações</button>
                        <a class="button secondary" href="manutencoes_listar.php">Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
        const pecas = document.getElementById('custo_pecas');
        const mao = document.getElementById('custo_mao_de_obra');
        const total = document.getElementById('custo_total_view');

        function calcTotal() {
            const value = (parseFloat(pecas.value) || 0) + (parseFloat(mao.value) || 0);
            total.textContent = value.toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
        }

        pecas.addEventListener('input', calcTotal);
        mao.addEventListener('input', calcTotal);
    </script>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
