<?php
require_once 'config.php';

$error = '';
$message = '';

// ensure password_resets table exists
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor insira um email válido.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM utilizadores WHERE email = ? AND status = "ativo" LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $error = 'Não existe uma conta ativa com esse email.';
                $stmt->close();
            } else {
                $stmt->close();
                $token = bin2hex(random_bytes(16));
                $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
                $stmt = $conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sss', $email, $token, $expires);
                    if ($stmt->execute()) {
                        $resetLink = siteUrl('reset_password.php') . '?token=' . urlencode($token);
                        $message = 'Link de redefinição (copie e cole no navegador): <br><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a>';
                    } else {
                        $error = 'Erro ao gerar link de redefinição.';
                    }
                    $stmt->close();
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
    <title>Recuperar Password - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="login-page">
    <main class="login-card">
        <h1>Recuperar Password</h1>
        <?php if ($error): ?><div class="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($message): ?><div class="alert" style="background:#e6ffed;color:#064e3b"><?php echo $message; ?></div><?php endif; ?>
        <form method="post" action="forgot_password.php">
            <label for="email">Endereço de Email</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Gerar Link de Redefinição</button>
        </form>
        <p class="hint">Voltar ao <a href="login.php">login</a>.</p>
    </main>
</body>
</html>
