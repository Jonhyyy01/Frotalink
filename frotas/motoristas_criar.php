<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'motoristas';

$error = '';
$nome_completo = '';
 $data_nascimento = '';
 $telefone = '';
 $email = '';
 $nif = '';

$numero_carta_conducao = '';
$validade_carta = '';
 $categoria_carta = '';
 $numero_cam_cqm = '';
 $validade_cam = '';

$numero_mecanografico = '';
 $data_admissao = '';
 $tipo_contrato = '';

$estado = 'Ativo';
$disponibilidade = 'Disponível';
$viatura_atual_id = '';
$user_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $data_nascimento = trim($_POST['data_nascimento'] ?? '') ?: null;
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nif = trim($_POST['nif'] ?? '');

    $numero_carta_conducao = trim($_POST['numero_carta_conducao'] ?? '');
    $validade_carta = trim($_POST['validade_carta'] ?? '') ?: null;
    $categoria_carta = trim($_POST['categoria_carta'] ?? '');
    $numero_cam_cqm = trim($_POST['numero_cam_cqm'] ?? '');
    $validade_cam = trim($_POST['validade_cam'] ?? '') ?: null;

    $numero_mecanografico = trim($_POST['numero_mecanografico'] ?? '');
    $data_admissao = trim($_POST['data_admissao'] ?? '') ?: null;
    $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');

    $estado = $_POST['estado'] ?? 'Ativo';
    $disponibilidade = $_POST['disponibilidade'] ?? 'Disponível';
    $viatura_atual_id = $_POST['viatura_atual_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;

    if ($nome_completo === '') {
        $error = 'Por favor, insira o nome completo.';
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare('INSERT INTO motoristas (nome_completo, data_nascimento, telefone, email, nif, numero_carta_conducao, validade_carta, categoria_carta, numero_cam_cqm, validade_cam, numero_mecanografico, data_admissao, tipo_contrato, estado, disponibilidade, viatura_atual_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssssssssssssssii', $nome_completo, $data_nascimento, $telefone, $email, $nif, $numero_carta_conducao, $validade_carta, $categoria_carta, $numero_cam_cqm, $validade_cam, $numero_mecanografico, $data_admissao, $tipo_contrato, $estado, $disponibilidade, $viatura_atual_id, $user_id);
            if ($stmt->execute()) {
                header('Location: motoristas_listar.php');
                exit;
            }
            $error = 'Erro ao criar motorista.';
            $stmt->close();
        } else {
            $error = 'Erro na preparação da query.';
        }
    }
}

$conn = getDbConnection();
$veiculosRes = $conn->query('SELECT id, matricula FROM veiculos ORDER BY id DESC');
$usersRes = $conn->query("SELECT id, nome FROM utilizadores WHERE nivel_acesso = 'motorista' ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Motorista - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Novo Motorista</span>
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
                    <h2>Criar Motorista</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="motoristas_criar.php">
                    <label for="nome_completo">Nome Completo</label>
                    <input type="text" id="nome_completo" name="nome_completo" value="<?php echo htmlspecialchars($nome_completo); ?>" required>

                    <label for="data_nascimento">Data de Nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($data_nascimento); ?>">

                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">

                    <label for="nif">NIF</label>
                    <input type="text" id="nif" name="nif" value="<?php echo htmlspecialchars($nif); ?>">

                    <h3>Documentação</h3>
                    <label for="numero_carta_conducao">Número da Carta</label>
                    <input type="text" id="numero_carta_conducao" name="numero_carta_conducao" value="<?php echo htmlspecialchars($numero_carta_conducao); ?>">

                    <label for="validade_carta">Validade da Carta</label>
                    <input type="date" id="validade_carta" name="validade_carta" value="<?php echo htmlspecialchars($validade_carta); ?>">

                    <label for="categoria_carta">Categoria da Carta</label>
                    <select id="categoria_carta" name="categoria_carta">
                        <?php $categorias = ['AM','A1','A2','A','B','B1','B+E','C','C+E','D','D+E']; ?>
                        <option value="">-- Selecionar --</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria; ?>" <?php echo $categoria_carta === $categoria ? 'selected' : ''; ?>><?php echo $categoria; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="numero_cam_cqm">Número CAM/CQM</label>
                    <input type="text" id="numero_cam_cqm" name="numero_cam_cqm" value="<?php echo htmlspecialchars($numero_cam_cqm); ?>">

                    <label for="validade_cam">Validade CAM</label>
                    <input type="date" id="validade_cam" name="validade_cam" value="<?php echo htmlspecialchars($validade_cam); ?>">

                    <h3>Contratual</h3>
                    <label for="numero_mecanografico">Número Mecanográfico</label>
                    <input type="text" id="numero_mecanografico" name="numero_mecanografico" value="<?php echo htmlspecialchars($numero_mecanografico); ?>">

                    <label for="data_admissao">Data de Admissão</label>
                    <input type="date" id="data_admissao" name="data_admissao" value="<?php echo htmlspecialchars($data_admissao); ?>">

                    <label for="tipo_contrato">Tipo de Contrato</label>
                    <select id="tipo_contrato" name="tipo_contrato">
                        <?php $contratos = [
                            'Contrato de trabalho sem termo',
                            'Contrato de trabalho a termo certo',
                            'Contrato de trabalho a termo incerto',
                            'Contrato de prestação de serviços',
                            'Contrato de trabalho temporário',
                            'Contrato de trabalho em regime de teletrabalho',
                            'Contrato de trabalho de muito curta duração'
                        ]; ?>
                        <option value="">-- Selecionar --</option>
                        <?php foreach ($contratos as $contrato): ?>
                            <option value="<?php echo htmlspecialchars($contrato); ?>" <?php echo $tipo_contrato === $contrato ? 'selected' : ''; ?>><?php echo htmlspecialchars($contrato); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="Ativo" <?php echo $estado === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="De Férias" <?php echo $estado === 'De Férias' ? 'selected' : ''; ?>>De Férias</option>
                        <option value="Baixa Médica" <?php echo $estado === 'Baixa Médica' ? 'selected' : ''; ?>>Baixa Médica</option>
                        <option value="Inativo" <?php echo $estado === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>

                    <label for="disponibilidade">Disponibilidade</label>
                    <select id="disponibilidade" name="disponibilidade">
                        <option value="Disponível" <?php echo $disponibilidade === 'Disponível' ? 'selected' : ''; ?>>Disponível</option>
                        <option value="Em Viagem" <?php echo $disponibilidade === 'Em Viagem' ? 'selected' : ''; ?>>Em Viagem</option>
                    </select>

                    <label for="viatura_atual_id">Viatura Atual</label>
                    <select id="viatura_atual_id" name="viatura_atual_id">
                        <option value="">-- Nenhuma --</option>
                        <?php if ($veiculosRes): while ($v = $veiculosRes->fetch_assoc()): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $viatura_atual_id == $v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['matricula']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>

                    <label for="user_id">Usuário (ligação ao sistema)</label>
                    <select id="user_id" name="user_id">
                        <option value="">-- Nenhum --</option>
                        <?php if ($usersRes): while ($u = $usersRes->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nome']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>

                    <button type="submit" class="button">Criar Motorista</button>
                </form>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
