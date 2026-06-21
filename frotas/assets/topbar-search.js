(() => {
    const suggestions = [
        { label: 'Viaturas', url: 'viaturas_listar.php' },
        { label: 'Motoristas', url: 'motoristas_listar.php' },
        { label: 'Clientes', url: 'clientes_listar.php' },
        { label: 'Cargas pendentes', url: 'cargas_listar.php' },
        { label: 'Cargas em trânsito', url: 'cargas_listar.php' },
        { label: 'Avarias abertas', url: 'avarias_listar.php' },
        { label: 'Inspeções', url: 'manutencoes_listar.php' },
        { label: 'Manutenções', url: 'manutencoes_listar.php' },
        { label: 'Combustível', url: 'combustivel_listar.php' },
        { label: 'Relatórios', url: 'relatorios.php' },
    ];

    function buildSuggestionButton(form, input, item) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'search-suggestion';
        button.textContent = item.label;
        button.addEventListener('mousedown', (event) => {
            event.preventDefault();
            window.location.href = item.url;
        });
        return button;
    }

    document.querySelectorAll('.topbar-search').forEach((form) => {
        const input = form.querySelector('input[type="search"]');
        if (!input) {
            return;
        }

        const menu = document.createElement('div');
        menu.className = 'search-suggestions';
        menu.setAttribute('role', 'listbox');
        const title = document.createElement('div');
        title.className = 'search-suggestions-title';
        title.textContent = 'Sugestões rápidas';
        menu.appendChild(title);
        const renderSuggestions = () => {
            const term = input.value.trim().toLowerCase();
            menu.querySelectorAll('.search-suggestion').forEach((node) => node.remove());
            suggestions
                .filter((item) => !term || item.label.toLowerCase().includes(term))
                .forEach((item) => menu.appendChild(buildSuggestionButton(form, input, item)));
        };

        renderSuggestions();
        form.appendChild(menu);

        const open = () => {
            renderSuggestions();
            if (document.activeElement === input) {
                form.classList.add('is-open');
            }
        };
        const close = () => form.classList.remove('is-open');

        input.addEventListener('focus', open);
        input.addEventListener('click', open);
        input.addEventListener('input', open);
        input.addEventListener('blur', () => window.setTimeout(close, 120));
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
                input.blur();
            }
        });

        document.addEventListener('mousedown', (event) => {
            if (!form.contains(event.target)) {
                close();
            }
        });
    });
})();
