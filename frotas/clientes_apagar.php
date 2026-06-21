<?php
require_once 'config.php';
requireOperationsAccess();

$activePage = 'clientes';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: clientes_listar.php');
    exit;
}

$conn = getDbConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $stmt = $conn->prepare('DELETE FROM clientes WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: clientes_listar.php');
    exit;
}

$stmt = $conn->prepare('SELECT nome, nif_nipc FROM clientes WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($nome, $nif_nipc);
if (! $stmt->fetch()) {
    $stmt->close();
    header('Location: clientes_listar.php');
    exit;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apagar Cliente - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Apagar Cliente</span>
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
                    <h2>Apagar Cliente</h2>
                </div>

                <p>Tem a certeza que pretende apagar o cliente <strong><?php echo htmlspecialchars($nome); ?></strong> (<?php echo htmlspecialchars($nif_nipc); ?>)?</p>

                <form method="post" action="clientes_apagar.php?id=<?php echo urlencode($id); ?>">
                    <button type="submit" name="confirm" value="yes" class="button danger">Sim, apagar</button>
                    <a href="clientes_listar.php" class="button secondary">Cancelar</a>
                </form>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
