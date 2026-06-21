<?php
require_once 'config.php';

if (isLoggedIn() && canManageUsers()) {
    header('Location: users_criar.php');
    exit;
}

if (isLoggedIn()) {
    header('Location: ' . redirectAfterLogin());
    exit;
}

$error = '';
$success = '';
$accountType = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');
    $accountType = trim($_POST['account_type'] ?? 'admin');
    $allowedAccountTypes = ['admin', 'gestor', 'motorista'];

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } elseif ($password !== $confirm) {
        $error = 'As passwords não coincidem.';
    } elseif (!in_array($accountType, $allowedAccountTypes, true)) {
        $error = 'Selecione um tipo de conta válido.';
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare('SELECT id FROM utilizadores WHERE email = ?');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = 'Já existe uma conta com esse email.';
                $stmt->close();
            } else {
                $stmt->close();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $nivel = $accountType;
                $status = 'ativo';
                $stmt = $conn->prepare('INSERT INTO utilizadores (nome, email, password_hash, nivel_acesso, status) VALUES (?, ?, ?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sssss', $name, $email, $hash, $nivel, $status);
                    if ($stmt->execute()) {
                        $success = 'Conta criada com sucesso. Pode iniciar sessão.';
                    } else {
                        $error = 'Erro ao criar conta.';
                    }
                    $stmt->close();
                } else {
                    $error = 'Erro de base de dados.';
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
    <style>
        .register-card { max-width: 460px; margin: 24px auto; }
    </style>
</head>
<body class="login-page">
    <main class="login-card register-card">
        <h1>Criar Conta</h1>
        <?php if ($error): ?><div class="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert" style="background:#e6ffed;color:#064e3b"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <form method="post" action="register.php">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="account_type">Tipo de conta</label>
            <select id="account_type" name="account_type" required>
                <option value="admin" <?php echo $accountType === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                <option value="gestor" <?php echo $accountType === 'gestor' ? 'selected' : ''; ?>>Gestor</option>
                <option value="motorista" <?php echo $accountType === 'motorista' ? 'selected' : ''; ?>>Motorista</option>
            </select>

            <label for="password">Palavra-passe</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm">Confirmar palavra-passe</label>
            <input type="password" id="confirm" name="confirm" required>

            <button type="submit">Criar Conta</button>
        </form>
        <p class="hint">Já tem conta? <a href="login.php">Iniciar sessão</a></p>
    </main>
</body>
</html>
