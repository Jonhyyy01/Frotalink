<aside class="sidebar">
    <nav>
        <?php if (canManageOperations()): ?>
            <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'inicio' ? 'active' : ''; ?>" href="index.php">Início</a>
            <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'calendario' ? 'active' : ''; ?>" href="calendario.php">Calendário</a>
            <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'viaturas' ? 'active' : ''; ?>" href="viaturas_listar.php">Viaturas</a>
            <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'motoristas' ? 'active' : ''; ?>" href="motoristas_listar.php">Motoristas</a>
        <?php endif; ?>

        <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'cargas' ? 'active' : ''; ?>" href="cargas_listar.php">Cargas</a>
        <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'combustivel' ? 'active' : ''; ?>" href="combustivel_listar.php">Combustível</a>

        <?php if (canManageOperations()): ?>
            <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'clientes' ? 'active' : ''; ?>" href="clientes_listar.php">Clientes</a>
            <?php if (canManageUsers()): ?>
                <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'utilizadores' ? 'active' : ''; ?>" href="users_listar.php">Utilizadores</a>
            <?php endif; ?>
            <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'manutencoes' ? 'active' : ''; ?>" href="manutencoes_listar.php">Inspeções / Manutenções</a>
            <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'relatorios' ? 'active' : ''; ?>" href="relatorios.php">Relatórios</a>
        <?php endif; ?>

        <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'avarias' ? 'active' : ''; ?>" href="avarias_listar.php">Avarias / Problemas</a>
        <a class="sidebar-link <?php echo isset($activePage) && $activePage === 'pesquisa' ? 'active' : ''; ?>" href="pesquisa.php">Pesquisa</a>
        <a class="sidebar-link" href="docs/Frotalink_Descricao_Projeto_Manual_Utilizador.pdf" download>Transferir Manual em PDF</a>
    </nav>
</aside>
