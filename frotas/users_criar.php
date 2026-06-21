<?php
require_once 'config.php';
requireUserManagementAccess();

$activePage = 'utilizadores';
$error = '';
$nome = '';
$email = '';
$nivel_acesso = 'motorista';
$status = 'ativo';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nivel_acesso = $_POST['nivel_acesso'] ?? 'motorista';
    $status = $_POST['status'] ?? 'ativo';

    if ($nome === '' || $email === '' || $password === '') {
        $error = 'Por favor, preencha o nome, email e palavra-passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } elseif (strlen($password) < 6) {
        $error = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare('SELECT id FROM utilizadores WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Já existe um utilizador com esse email.';
        } else {
            $stmt->close();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO utilizadores (nome, email, password_hash, nivel_acesso, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $nome, $email, $passwordHash, $nivel_acesso, $status);
            if ($stmt->execute()) {
                header('Location: users_listar.php');
                exit;
            }
            $error = 'Erro ao criar o utilizador. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Utilizador - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Novo Utilizador</span>
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
                    <h2>Criar Utilizador</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="users_criar.php">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

                    <label for="password">Palavra-passe</label>
                    <input type="password" id="password" name="password" required>

                    <label for="nivel_acesso">Nível de Acesso</label>
                    <select id="nivel_acesso" name="nivel_acesso">
                        <option value="admin" <?php echo $nivel_acesso === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="gestor" <?php echo $nivel_acesso === 'gestor' ? 'selected' : ''; ?>>Gestor</option>
                        <option value="motorista" <?php echo $nivel_acesso === 'motorista' ? 'selected' : ''; ?>>Motorista</option>
                    </select>

                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>

                    <button type="submit" class="button">Criar Utilizador</button>
                </form>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
