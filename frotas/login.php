<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email !== '' && $password !== '') {
        $conn = getDbConnection();
        $stmt = $conn->prepare('SELECT id, nome, password_hash, nivel_acesso, status FROM utilizadores WHERE email = ? LIMIT 1');

        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($userId, $name, $passwordHash, $nivelAcesso, $status);

            if ($stmt->fetch()) {
                $stmt->close();
                if ($status === 'ativo' && password_verify($password, $passwordHash)) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $userId;
                    $_SESSION['user'] = $name ?: $email;
                    $_SESSION['nivel_acesso'] = $nivelAcesso;
                    header('Location: ' . redirectAfterLogin());
                    exit;
                }
            } else {
                $stmt->close();
            }
        }
    }

    $error = 'Email ou palavra-passe inválidos.';
}

if (isLoggedIn()) {
    header('Location: ' . redirectAfterLogin());
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="login-page">
    <main class="login-card">
        <h1>Aceder</h1>
        <?php if ($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Palavra-passe</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Aceder</button>
        </form>

        <div class="login-actions">
            <a class="button secondary" href="register.php">Criar conta</a>
            <button id="show-forgot" class="button secondary">Esqueci a password</button>
        </div>

        <div id="forgot-section" class="auth-section" style="display:none;">
            <h2>Recuperar Password</h2>
            <form method="post" action="forgot_password.php">
                <label for="f_email">Endereço de Email</label>
                <input type="email" id="f_email" name="email" required>
                <button type="submit">Gerar Link de Redefinição</button>
            </form>
            <p class="hint">O link será mostrado após gerar (não enviamos emails nesta versão).</p>
        </div>

        <script>
            document.getElementById('show-forgot').addEventListener('click', function(){
                document.getElementById('forgot-section').style.display = 'block';
            });
        </script>
    </main>
</body>
</html>
