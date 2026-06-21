<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'clientes';

$error = '';
$tipo_cliente = 'Física';
$nome = '';
$responsavel_contacto = '';
$nif_nipc = '';
$morada_fiscal = '';
$codigo_postal = '';
$localidade = '';
$pais = 'Portugal';
$telefone = '';
$email = '';
$website = '';
$limite_credito = 0.00;
$prazo_pagamento_dias = 30;
$estado_cliente = 'Ativo';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_cliente = $_POST['tipo_cliente'] ?? 'Física';
    $nome = trim($_POST['nome'] ?? '');
    $responsavel_contacto = trim($_POST['responsavel_contacto'] ?? '');
    $nif_nipc = trim($_POST['nif_nipc'] ?? '');
    $morada_fiscal = trim($_POST['morada_fiscal'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $localidade = trim($_POST['localidade'] ?? '');
    $pais = trim($_POST['pais'] ?? 'Portugal');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $limite_credito = trim($_POST['limite_credito'] ?? '0') ?: 0;
    $prazo_pagamento_dias = (int) ($_POST['prazo_pagamento_dias'] ?? 30);
    $estado_cliente = $_POST['estado_cliente'] ?? 'Ativo';

    if ($nome === '' || $nif_nipc === '') {
        $error = 'Por favor, preencha pelo menos o nome e NIF/NIPC.';
    } else {
        $conn = getDbConnection();
        list($lat, $lon) = geocodeAddress($morada_fiscal, $codigo_postal, $localidade, $pais);

        $stmt = $conn->prepare('INSERT INTO clientes (tipo_cliente, nome, responsavel_contacto, nif_nipc, morada_fiscal, codigo_postal, localidade, pais, telefone, email, website, limite_credito, prazo_pagamento_dias, estado_cliente, lat, lon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssssssssssdisdd', $tipo_cliente, $nome, $responsavel_contacto, $nif_nipc, $morada_fiscal, $codigo_postal, $localidade, $pais, $telefone, $email, $website, $limite_credito, $prazo_pagamento_dias, $estado_cliente, $lat, $lon);
            if ($stmt->execute()) {
                header('Location: clientes_listar.php');
                exit;
            }
            $error = 'Erro ao criar cliente. Verifique se o NIF/NIPC é único.';
            $stmt->close();
        } else {
            $error = 'Erro na preparação da query.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Cliente - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Novo Cliente</span>
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
                    <h2>Criar Cliente</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="clientes_criar.php">
                    <label for="tipo_cliente">Tipo de Cliente</label>
                    <select name="tipo_cliente" id="tipo_cliente">
                        <option value="Física" <?php echo $tipo_cliente === 'Física' ? 'selected' : ''; ?>>Física</option>
                        <option value="Jurídica" <?php echo $tipo_cliente === 'Jurídica' ? 'selected' : ''; ?>>Jurídica</option>
                    </select>

                    <label for="nome">Nome / Empresa</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>

                    <label for="responsavel_contacto">Responsável de Contacto</label>
                    <input type="text" id="responsavel_contacto" name="responsavel_contacto" value="<?php echo htmlspecialchars($responsavel_contacto); ?>">

                    <label for="nif_nipc">NIF / NIPC</label>
                    <input type="text" id="nif_nipc" name="nif_nipc" value="<?php echo htmlspecialchars($nif_nipc); ?>" required>

                    <label for="morada_fiscal">Morada Fiscal</label>
                    <input type="text" id="morada_fiscal" name="morada_fiscal" value="<?php echo htmlspecialchars($morada_fiscal); ?>">

                    <label for="codigo_postal">Código Postal</label>
                    <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo htmlspecialchars($codigo_postal); ?>">

                    <label for="localidade">Localidade</label>
                    <input type="text" id="localidade" name="localidade" value="<?php echo htmlspecialchars($localidade); ?>">

                    <label for="pais">País</label>
                    <input type="text" id="pais" name="pais" value="<?php echo htmlspecialchars($pais); ?>">

                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">

                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" value="<?php echo htmlspecialchars($website); ?>">

                    <label for="limite_credito">Limite de Crédito</label>
                    <input type="number" step="0.01" id="limite_credito" name="limite_credito" value="<?php echo htmlspecialchars($limite_credito); ?>">

                    <label for="prazo_pagamento_dias">Prazo de Pagamento (dias)</label>
                    <input type="number" id="prazo_pagamento_dias" name="prazo_pagamento_dias" value="<?php echo htmlspecialchars($prazo_pagamento_dias); ?>">

                    <label for="estado_cliente">Estado do Cliente</label>
                    <select name="estado_cliente" id="estado_cliente">
                        <option value="Ativo" <?php echo $estado_cliente === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="Bloqueado" <?php echo $estado_cliente === 'Bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                        <option value="Inativo" <?php echo $estado_cliente === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>

                    <button type="submit" class="button">Criar Cliente</button>
                </form>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
