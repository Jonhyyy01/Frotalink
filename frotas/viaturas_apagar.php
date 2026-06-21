<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'viaturas';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: viaturas_listar.php');
    exit;
}

$conn = getDbConnection();
$stmt = $conn->prepare('SELECT matricula, modelo FROM veiculos WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($matricula, $modelo);
if (! $stmt->fetch()) {
    $stmt->close();
    header('Location: viaturas_listar.php');
    exit;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('DELETE FROM veiculos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: viaturas_listar.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apagar Viatura - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Apagar Viatura</span>
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
                    <h2>Confirmar Exclusão</h2>
                </div>
                <p>Tem certeza que deseja apagar a viatura:</p>
                <p><strong><?php echo htmlspecialchars($matricula); ?></strong> - <?php echo htmlspecialchars($modelo); ?></p>
                <form method="post" action="viaturas_apagar.php?id=<?php echo urlencode($id); ?>">
                    <button type="submit" class="button danger">Sim, apagar</button>
                    <a class="button secondary" href="viaturas_listar.php">Cancelar</a>
                </form>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
