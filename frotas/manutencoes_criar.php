<?php
require_once 'config.php';
requireOperationsAccess();
$activePage = 'manutencoes';

$error = '';

$equipamento_id = '';
$tecnico_id = '';
$tipo_acao = 'Inspeção';
$status = 'Agendado';
$prioridade = 'Média';

$data_agendada = '';
$data_inicio = '';
$data_fim = '';
$proxima_revisao = '';

$descricao_problema = '';
$acoes_realizadas = '';
$resultado_inspecao = '';
$leitura_odometro_horas = '';

$custo_pecas = '0.00';
$custo_mao_de_obra = '0.00';
$custo_total = '0.00';
$url_relatorio_pdf = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipamento_id = $_POST['equipamento_id'] ?? null;
    $tecnico_id = $_POST['tecnico_id'] ?? null;
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
    $custo_total = number_format((float)$custo_pecas + (float)$custo_mao_de_obra, 2, '.', '');
    $url_relatorio_pdf = trim($_POST['url_relatorio_pdf'] ?? '');

    if ($equipamento_id === '' || $equipamento_id === null) {
        $error = 'Selecione o equipamento (veículo).';
    } else {
        $conn = getDbConnection();

        // Garantir existência da tabela
        $createSql = "CREATE TABLE IF NOT EXISTS historico_manutencoes_inspecoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equipamento_id INT DEFAULT NULL,
            tecnico_id INT DEFAULT NULL,
            tipo_acao ENUM('Inspeção','Manutenção Preventiva','Manutenção Corretiva','Preditiva') NOT NULL DEFAULT 'Inspeção',
            status ENUM('Agendado','Em Andamento','Concluído','Cancelado') NOT NULL DEFAULT 'Agendado',
            prioridade ENUM('Baixa','Média','Alta','Crítica') NOT NULL DEFAULT 'Média',
            data_agendada DATE DEFAULT NULL,
            data_inicio DATETIME DEFAULT NULL,
            data_fim DATETIME DEFAULT NULL,
            proxima_revisao DATE DEFAULT NULL,
            descricao_problema TEXT DEFAULT NULL,
            acoes_realizadas TEXT DEFAULT NULL,
            resultado_inspecao ENUM('Aprovado','Aprovado com Restrições','Reprovado') DEFAULT NULL,
            leitura_odometro_horas DECIMAL(12,2) DEFAULT NULL,
            custo_pecas DECIMAL(10,2) DEFAULT 0.00,
            custo_mao_de_obra DECIMAL(10,2) DEFAULT 0.00,
            custo_total DECIMAL(10,2) DEFAULT 0.00,
            url_relatorio_pdf VARCHAR(255) DEFAULT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (equipamento_id) REFERENCES veiculos(id) ON DELETE SET NULL,
            FOREIGN KEY (tecnico_id) REFERENCES utilizadores(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$conn->query($createSql)) {
            $error = 'Erro ao garantir estrutura da base de dados: ' . htmlspecialchars($conn->error);
        } else {
            $stmt = $conn->prepare('INSERT INTO historico_manutencoes_inspecoes (equipamento_id, tecnico_id, tipo_acao, status, prioridade, data_agendada, data_inicio, data_fim, proxima_revisao, descricao_problema, acoes_realizadas, resultado_inspecao, leitura_odometro_horas, custo_pecas, custo_mao_de_obra, custo_total, url_relatorio_pdf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('iissssssssssdidds', $equipamento_id, $tecnico_id, $tipo_acao, $status, $prioridade, $data_agendada, $data_inicio, $data_fim, $proxima_revisao, $descricao_problema, $acoes_realizadas, $resultado_inspecao, $leitura_odometro_horas, $custo_pecas, $custo_mao_de_obra, $custo_total, $url_relatorio_pdf);
                if ($stmt->execute()) {
                    header('Location: manutencoes_listar.php');
                    exit;
                }
                $error = 'Erro ao gravar registo.';
                $stmt->close();
            } else {
                $error = 'Erro na preparação da query: ' . htmlspecialchars($conn->error);
            }
        }
    }
}

$conn = getDbConnection();
$veiculosRes = $conn->query('SELECT id, matricula, modelo FROM veiculos ORDER BY id DESC');
$usersRes = $conn->query('SELECT id, nome FROM utilizadores ORDER BY id DESC');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Manutenção/Inspeção - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Registar Manutenção / Inspeção</span>
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
                    <h2>Novo registo</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="manutencoes_criar.php">
                    <label for="equipamento_id">Equipamento / Veículo</label>
                    <select id="equipamento_id" name="equipamento_id" required>
                        <option value="">-- Selecionar veículo --</option>
                        <?php if ($veiculosRes): while ($v = $veiculosRes->fetch_assoc()): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $equipamento_id == $v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['matricula'] . ' - ' . $v['modelo']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>

                    <label for="tecnico_id">Técnico / Utilizador</label>
                    <select id="tecnico_id" name="tecnico_id">
                        <option value="">-- Nenhum --</option>
                        <?php if ($usersRes): while ($u = $usersRes->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $tecnico_id == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nome']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>

                    <label for="tipo_acao">Tipo de Ação</label>
                    <select id="tipo_acao" name="tipo_acao">
                        <?php $tipos = ['Inspeção','Manutenção Preventiva','Manutenção Corretiva','Preditiva']; ?>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $tipo_acao === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php $statusOpts = ['Agendado','Em Andamento','Concluído','Cancelado']; ?>
                        <?php foreach ($statusOpts as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="prioridade">Prioridade</label>
                    <select id="prioridade" name="prioridade">
                        <?php $prios = ['Baixa','Média','Alta','Crítica']; ?>
                        <?php foreach ($prios as $p): ?>
                            <option value="<?php echo $p; ?>" <?php echo $prioridade === $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="data_agendada">Data Agendada</label>
                    <input type="date" id="data_agendada" name="data_agendada" value="<?php echo htmlspecialchars($data_agendada); ?>">

                    <label for="data_inicio">Data / Hora Início</label>
                    <input type="datetime-local" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">

                    <label for="data_fim">Data / Hora Fim</label>
                    <input type="datetime-local" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">

                    <label for="proxima_revisao">Próxima Revisão</label>
                    <input type="date" id="proxima_revisao" name="proxima_revisao" value="<?php echo htmlspecialchars($proxima_revisao); ?>">

                    <label for="descricao_problema">Descrição do Problema</label>
                    <textarea id="descricao_problema" name="descricao_problema"><?php echo htmlspecialchars($descricao_problema); ?></textarea>

                    <label for="acoes_realizadas">Ações Realizadas</label>
                    <textarea id="acoes_realizadas" name="acoes_realizadas"><?php echo htmlspecialchars($acoes_realizadas); ?></textarea>

                    <label for="resultado_inspecao">Resultado da Inspeção</label>
                    <select id="resultado_inspecao" name="resultado_inspecao">
                        <option value="">-- N/A --</option>
                        <?php $resOpts = ['Aprovado','Aprovado com Restrições','Reprovado']; foreach ($resOpts as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo $resultado_inspecao === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="leitura_odometro_horas">Leitura (km / horas)</label>
                    <input type="number" step="0.01" id="leitura_odometro_horas" name="leitura_odometro_horas" value="<?php echo htmlspecialchars($leitura_odometro_horas); ?>">

                    <label for="custo_pecas">Custo Peças (€)</label>
                    <input type="number" step="0.01" id="custo_pecas" name="custo_pecas" value="<?php echo htmlspecialchars($custo_pecas); ?>">

                    <label for="custo_mao_de_obra">Custo Mão-de-obra (€)</label>
                    <input type="number" step="0.01" id="custo_mao_de_obra" name="custo_mao_de_obra" value="<?php echo htmlspecialchars($custo_mao_de_obra); ?>">

                    <div class="form-summary">
                        <span>Custo total</span>
                        <strong id="custo_total_view"><?php echo htmlspecialchars(number_format((float) $custo_total, 2, ',', '.')); ?> €</strong>
                    </div>

                    <label for="url_relatorio_pdf">URL Relatório / Fotos</label>
                    <input type="url" id="url_relatorio_pdf" name="url_relatorio_pdf" value="<?php echo htmlspecialchars($url_relatorio_pdf); ?>">

                    <button type="submit" class="button">Criar Registo</button>
                </form>
            </section>
        </main>
    </div>
    <script>
        // Calcular custo_total no cliente para conveniência
        const pecas = document.getElementById('custo_pecas');
        const mao = document.getElementById('custo_mao_de_obra');
        const total = document.getElementById('custo_total_view');

        function calcTotal() {
            const p = parseFloat(pecas ? pecas.value : 0) || 0;
            const m = parseFloat(mao ? mao.value : 0) || 0;
            if (total) {
                total.textContent = (p + m).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
            }
        }
        if (pecas) pecas.addEventListener('input', calcTotal);
        if (mao) mao.addEventListener('input', calcTotal);
    </script>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
