<?php
require_once 'config.php';
requireLogin();

$activePage = 'pesquisa';
$conn = getDbConnection();
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$results = [];

function addSearchRows(mysqli $conn, array &$results, string $title, string $sql, string $types, array $params): void {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results[] = ['grupo' => $title] + $row;
    }
    $stmt->close();
}

if ($q !== '') {
    if (canManageOperations()) {
        addSearchRows($conn, $results, 'Viaturas', "SELECT matricula AS titulo, modelo AS detalhe, status AS estado, CONCAT('viaturas_historico.php?id=', id) AS url FROM veiculos WHERE matricula LIKE ? OR modelo LIKE ? LIMIT 8", 'ss', [$like, $like]);
        addSearchRows($conn, $results, 'Motoristas', "SELECT nome_completo AS titulo, email AS detalhe, disponibilidade AS estado, CONCAT('motoristas_editar.php?id=', id) AS url FROM motoristas WHERE nome_completo LIKE ? OR email LIKE ? OR telefone LIKE ? LIMIT 8", 'sss', [$like, $like, $like]);
        addSearchRows($conn, $results, 'Clientes', "SELECT nome AS titulo, nif_nipc AS detalhe, estado_cliente AS estado, CONCAT('clientes_editar.php?id=', id) AS url FROM clientes WHERE nome LIKE ? OR nif_nipc LIKE ? OR email LIKE ? LIMIT 8", 'sss', [$like, $like, $like]);
    }
    addSearchRows($conn, $results, 'Cargas', "SELECT COALESCE(codigo_rastreio, CONCAT('#', id)) AS titulo, CONCAT(local_recolha, ' -> ', local_entrega) AS detalhe, estado_carga AS estado, CONCAT('cargas_editar.php?id=', id) AS url FROM cargas WHERE codigo_rastreio LIKE ? OR local_recolha LIKE ? OR local_entrega LIKE ? OR descricao LIKE ? LIMIT 10", 'ssss', [$like, $like, $like, $like]);
    addSearchRows($conn, $results, 'Avarias', "SELECT titulo, descricao AS detalhe, status AS estado, 'avarias_listar.php' AS url FROM avarias_problemas WHERE titulo LIKE ? OR descricao LIKE ? LIMIT 8", 'ss', [$like, $like]);
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand"><button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button><div><p class="brand-title">Frotalink</p><span class="brand-subtitle">Pesquisa Global</span></div></div>
        <form class="topbar-search" method="get" action="pesquisa.php"><input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar"></form>
        <div class="topbar-actions"><div class="topbar-stats"><span>Olá, <?php echo htmlspecialchars($_SESSION['user']); ?></span><span class="status-pill status-active">Online</span></div><a class="button secondary" href="logout.php">Sair</a></div>
    </header>
    <div class="page-layout">
        <?php include 'sidebar.php'; ?>
        <main class="dashboard-content">
            <section class="widget">
                <div class="widget-header"><h2>Pesquisar</h2></div>
                <form method="get" action="pesquisa.php" class="filter-form global-search-form">
                    <div class="filter-row form-field-full"><label for="q">Termo de pesquisa</label><input type="search" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Matrícula, motorista, cliente, carga..."></div>
                    <div class="filter-actions"><button type="submit" class="button">Pesquisar</button><a class="button secondary" href="pesquisa.php">Limpar</a></div>
                </form>
            </section>
            <section class="widget">
                <div class="widget-header"><h2>Resultados</h2><span><?php echo count($results); ?> encontrados</span></div>
                <?php if ($q === ''): ?>
                    <p class="empty-state">Escreva algo para procurar em viaturas, motoristas, clientes, cargas e avarias.</p>
                <?php elseif ($results): ?>
                    <div class="search-results">
                        <?php foreach ($results as $row): ?>
                            <a class="search-result" href="<?php echo htmlspecialchars($row['url']); ?>">
                                <span class="badge badge-neutral"><?php echo htmlspecialchars($row['grupo']); ?></span>
                                <strong><?php echo htmlspecialchars($row['titulo'] ?? '-'); ?></strong>
                                <small><?php echo htmlspecialchars($row['detalhe'] ?? '-'); ?></small>
                                <span class="table-muted"><?php echo htmlspecialchars($row['estado'] ?? ''); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Sem resultados para "<?php echo htmlspecialchars($q); ?>".</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
