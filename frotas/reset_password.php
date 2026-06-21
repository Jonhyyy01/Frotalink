<?php
require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

$conn = getDbConnection();
$conn->query(
    'CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(128) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

if ($token === '') {
    $error = 'Token inválido.';
} else {
    $stmt = $conn->prepare('SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($email, $expiresAt);
        if ($stmt->fetch()) {
            $stmt->close();
            $now = new DateTime();
            if (new DateTime($expiresAt) < $now) {
                $error = 'O link expirou.';
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $password = trim($_POST['password'] ?? '');
                    $confirm = trim($_POST['confirm'] ?? '');
                    if ($password === '' || $password !== $confirm) {
                        $error = 'Passwords inválidas ou não coincidem.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE utilizadores SET password_hash = ? WHERE email = ?');
                        if ($stmt) {
                            $stmt->bind_param('ss', $hash, $email);
                            if ($stmt->execute()) {
                                $stmt->close();
                                $stmt = $conn->prepare('DELETE FROM password_resets WHERE token = ?');
                                if ($stmt) { $stmt->bind_param('s', $token); $stmt->execute(); $stmt->close(); }
                                $success = 'Password alterada com sucesso. Pode agora iniciar sessão.';
                            } else {
                                $error = 'Erro ao actualizar password.';
                            }
                        }
                    }
                }
            }
        } else {
            $stmt->close();
            $error = 'Token inválido.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Password - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="login-page">
    <main class="login-card">
        <h1>Redefinir Password</h1>
        <?php if ($error): ?><div class="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert" style="background:#e6ffed;color:#064e3b"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <?php if (!$success && !$error): ?>
        <form method="post" action="reset_password.php?token=<?php echo urlencode($token); ?>">
            <label for="password">Nova Password</label>
            <input type="password" id="password" name="password" required>
            <label for="confirm">Confirmar Password</label>
            <input type="password" id="confirm" name="confirm" required>
            <button type="submit">Definir Nova Password</button>
        </form>
        <?php endif; ?>

        <p class="hint">Voltar ao <a href="login.php">login</a>.</p>
    </main>
</body>
</html>
