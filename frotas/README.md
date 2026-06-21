# Frotalink

Este projeto foi recriado do zero como um ponto de partida para um sistema de gestão de frota.

## Estrutura inicial

- `index.php` - página inicial que exige login
- `login.php` - formulário de autenticação
- `logout.php` - encerra a sessão do utilizador
- `config.php` - configurações iniciais e controlo de sessão
- `layout.css` - estilos básicos do projeto

## Instruções

1. Abra o projeto em um servidor local (XAMPP, WAMP, etc.).
2. Acesse `login.php` no navegador.
3. Use as credenciais de exemplo:
   - usuário: `admin`
   - senha: `admin`
4. Atualize `config.php` com a conexão ao banco de dados quando quiser usar dados reais.

## Próximos passos

- Criar páginas de CRUD para veículos, condutores, manutenções e cargas.
- Implementar gestão de utilizadores em `users_listar.php`, `users_criar.php`, `users_editar.php` e `users_apagar.php`.
- Adicionar validação de formulários e permissões de utilizador.
