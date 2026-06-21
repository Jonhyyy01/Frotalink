<?php
require_once 'config.php';
requireUserManagementAccess();

$activePage = 'utilizadores';
$conn = getDbConnection();
$result = $conn->query('SELECT id, nome, email, nivel_acesso, status, created_at FROM utilizadores ORDER BY created_at DESC');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizadores - Frotalink</title>
    <link rel="stylesheet" href="layout.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand">
            <button type="button" class="brand-logo" onclick="window.location.href='index.php'" aria-label="Página principal"><img src="assets/logo.svg" alt="Frotalink"></button>
            <div>
                <p class="brand-title">Frotalink</p>
                <span class="brand-subtitle">Gestão de Utilizadores</span>
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
                    <h2>Utilizadores</h2>
                    <a class="button" href="users_criar.php">Novo Utilizador</a>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Nível</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nivel_acesso']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td>
                                            <a class="action-link" href="users_editar.php?id=<?php echo urlencode($row['id']); ?>">Editar</a>
                                            <?php if ($row['nivel_acesso'] !== 'admin'): ?>
                                                <a class="action-link danger" href="users_apagar.php?id=<?php echo urlencode($row['id']); ?>">Apagar</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhum utilizador encontrado.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="assets/topbar-search.js"></script>
</body>
</html>
