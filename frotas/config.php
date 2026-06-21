<?php
// Configuração inicial do projeto
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}


define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gestao_frotas');
define('DB_CHARSET', 'utf8mb4');

function getDbConnection() {
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (mysqli_sql_exception $e) {
        die('Erro ao conectar ao banco de dados. Verifique se o MySQL está ligado no XAMPP e se a base de dados "' . htmlspecialchars(DB_NAME) . '" existe.');
    }

    if ($mysqli->connect_errno) {
        die('Erro ao conectar ao banco de dados. Verifique se o MySQL está ligado no XAMPP e se a base de dados "' . htmlspecialchars(DB_NAME) . '" existe.');
    }
    if (! $mysqli->set_charset(DB_CHARSET)) {
        die('Erro ao configurar charset do banco de dados: ' . htmlspecialchars($mysqli->error));
    }
    return $mysqli;
}

function geocodeAddress(string $address, string $postalCode, string $locality, string $country): array {
    $queryParts = array_filter([$address, $postalCode, $locality, $country]);
    if (count($queryParts) === 0) {
        return [null, null];
    }

    $query = implode(', ', $queryParts);
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $query,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 0,
    ]);

    $response = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Frotalink/1.0');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: Frotalink/1.0\r\nAccept-Language: pt\r\n",
                'timeout' => 10,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
    }

    if (! $response) {
        return [null, null];
    }

    $data = json_decode($response, true);
    if (is_array($data) && isset($data[0]['lat'], $data[0]['lon'])) {
        return [(float) $data[0]['lat'], (float) $data[0]['lon']];
    }

    return [null, null];
}

function initializeDatabaseSchema() {
    $conn = getDbConnection();

    $conn->query(
        'CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    if ($conn->errno) {
        die('Erro ao criar estrutura de banco: ' . htmlspecialchars($conn->error));
    }

    $stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    if ($stmt) {
        $defaultUser = 'admin';
        $stmt->bind_param('s', $defaultUser);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ((int) $count === 0) {
            $passwordHash = password_hash('admin', PASSWORD_DEFAULT);
            $name = 'Administrador';
            $stmt = $conn->prepare('INSERT INTO users (username, password_hash, name) VALUES (?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('sss', $defaultUser, $passwordHash, $name);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $conn->query(
        'CREATE TABLE IF NOT EXISTS utilizadores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            nivel_acesso ENUM(\'admin\',\'gestor\',\'motorista\') NOT NULL DEFAULT \'motorista\',
            status ENUM(\'ativo\',\'inativo\') NOT NULL DEFAULT \'ativo\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    if ($conn->errno) {
        die('Erro ao criar estrutura de banco: ' . htmlspecialchars($conn->error));
    }

    $stmt = $conn->prepare('SELECT COUNT(*) FROM utilizadores WHERE email = ?');
    if ($stmt) {
        $defaultEmail = 'admin@frota.local';
        $stmt->bind_param('s', $defaultEmail);
        $stmt->execute();
        $stmt->bind_result($utilCount);
        $stmt->fetch();
        $stmt->close();

        if ((int) $utilCount === 0) {
            $passwordHash = password_hash('admin', PASSWORD_DEFAULT);
            $name = 'Administrador';
            $stmt = $conn->prepare('INSERT INTO utilizadores (nome, email, password_hash, nivel_acesso, status) VALUES (?, ?, ?, ?, ?)');
            if ($stmt) {
                $nivel = 'admin';
                $status = 'ativo';
                $stmt->bind_param('sssss', $name, $defaultEmail, $passwordHash, $nivel, $status);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $conn->query(
        'CREATE TABLE IF NOT EXISTS veiculos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            matricula VARCHAR(20) NOT NULL UNIQUE,
            modelo VARCHAR(100) NOT NULL,
            status ENUM(\'ativo\',\'em_manutencao\',\'ocioso\') NOT NULL DEFAULT \'ativo\',
            km_total INT NOT NULL DEFAULT 0,
            consumo_medio DECIMAL(4,1) NOT NULL DEFAULT 0.0,
            lat DECIMAL(10,7) DEFAULT NULL,
            lon DECIMAL(10,7) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query(
        'CREATE TABLE IF NOT EXISTS viagens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            veiculo_id INT NOT NULL,
            data_viagem DATE NOT NULL,
            distancia_km INT NOT NULL,
            FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query(
        'CREATE TABLE IF NOT EXISTS manutencoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            veiculo_id INT NOT NULL,
            tipo VARCHAR(100) NOT NULL,
            status ENUM(\'pendente\',\'concluida\') NOT NULL DEFAULT \'pendente\',
            criticidade ENUM(\'critico\',\'alto\',\'medio\',\'baixo\') NOT NULL DEFAULT \'medio\',
            descricao TEXT,
            data_agendada DATE NOT NULL,
            FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query(
        'CREATE TABLE IF NOT EXISTS historico_manutencoes_inspecoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equipamento_id INT DEFAULT NULL,
            tecnico_id INT DEFAULT NULL,
            tipo_acao ENUM(\'Inspeção\',\'Manutenção Preventiva\',\'Manutenção Corretiva\',\'Preditiva\') NOT NULL DEFAULT \'Inspeção\',
            status ENUM(\'Agendado\',\'Em Andamento\',\'Concluído\',\'Cancelado\') NOT NULL DEFAULT \'Agendado\',
            prioridade ENUM(\'Baixa\',\'Média\',\'Alta\',\'Crítica\') NOT NULL DEFAULT \'Média\',
            data_agendada DATE DEFAULT NULL,
            data_inicio DATETIME DEFAULT NULL,
            data_fim DATETIME DEFAULT NULL,
            proxima_revisao DATE DEFAULT NULL,
            descricao_problema TEXT DEFAULT NULL,
            acoes_realizadas TEXT DEFAULT NULL,
            resultado_inspecao ENUM(\'Aprovado\',\'Aprovado com Restrições\',\'Reprovado\') DEFAULT NULL,
            leitura_odometro_horas DECIMAL(12,2) DEFAULT NULL,
            custo_pecas DECIMAL(10,2) DEFAULT 0.00,
            custo_mao_de_obra DECIMAL(10,2) DEFAULT 0.00,
            custo_total DECIMAL(10,2) DEFAULT 0.00,
            url_relatorio_pdf VARCHAR(255) DEFAULT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (equipamento_id) REFERENCES veiculos(id) ON DELETE SET NULL,
            FOREIGN KEY (tecnico_id) REFERENCES utilizadores(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query(
        'CREATE TABLE IF NOT EXISTS motoristas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_completo VARCHAR(255) NOT NULL,
            data_nascimento DATE DEFAULT NULL,
            telefone VARCHAR(50) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            nif VARCHAR(50) DEFAULT NULL,

            numero_carta_conducao VARCHAR(100) DEFAULT NULL,
            validade_carta DATE DEFAULT NULL,
            categoria_carta VARCHAR(50) DEFAULT NULL,
            numero_cam_cqm VARCHAR(100) DEFAULT NULL,
            validade_cam DATE DEFAULT NULL,

            numero_mecanografico VARCHAR(100) DEFAULT NULL,
            data_admissao DATE DEFAULT NULL,
            tipo_contrato VARCHAR(100) DEFAULT NULL,

            estado ENUM(\'Ativo\',\'De Férias\',\'Baixa Médica\',\'Inativo\') DEFAULT \'Ativo\',
            disponibilidade ENUM(\'Disponível\',\'Em Viagem\') DEFAULT \'Disponível\',

            viatura_atual_id INT DEFAULT NULL,
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (viatura_atual_id) REFERENCES veiculos(id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES utilizadores(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query(
        'CREATE TABLE IF NOT EXISTS clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo_cliente ENUM(\'Física\', \'Jurídica\') NOT NULL DEFAULT \'Física\',
            nome VARCHAR(255) NOT NULL,
            responsavel_contacto VARCHAR(255) DEFAULT NULL,
            nif_nipc VARCHAR(100) NOT NULL UNIQUE,
            morada_fiscal VARCHAR(255) DEFAULT NULL,
            codigo_postal VARCHAR(50) DEFAULT NULL,
            localidade VARCHAR(100) DEFAULT NULL,
            pais VARCHAR(100) DEFAULT NULL,
            telefone VARCHAR(50) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            limite_credito DECIMAL(12,2) DEFAULT 0,
            prazo_pagamento_dias INT DEFAULT 30,
            estado_cliente ENUM(\'Ativo\', \'Bloqueado\', \'Inativo\') NOT NULL DEFAULT \'Ativo\',
            lat DECIMAL(10,7) DEFAULT NULL,
            lon DECIMAL(10,7) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS lat DECIMAL(10,7) DEFAULT NULL, ADD COLUMN IF NOT EXISTS lon DECIMAL(10,7) DEFAULT NULL");

    $conn->query(
        'CREATE TABLE IF NOT EXISTS cargas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo_rastreio VARCHAR(100) DEFAULT NULL UNIQUE,
            estado_carga ENUM(\'Pendente\', \'Em Trânsito\', \'Entregue\', \'Cancelada\') NOT NULL DEFAULT \'Pendente\',
            descricao TEXT DEFAULT NULL,
            tipo_carga VARCHAR(100) DEFAULT NULL,
            peso_kg DECIMAL(12,3) DEFAULT 0,
            volume_m3 DECIMAL(12,3) DEFAULT 0,
            quantidade_paletes INT DEFAULT 0,

            local_recolha VARCHAR(255) DEFAULT NULL,
            data_hora_recolha_prevista DATETIME DEFAULT NULL,
            data_hora_recolha_real DATETIME DEFAULT NULL,
            local_entrega VARCHAR(255) DEFAULT NULL,
            data_hora_entrega_prevista DATETIME DEFAULT NULL,
            data_hora_entrega_real DATETIME DEFAULT NULL,

            cliente_id INT DEFAULT NULL,
            valor_transporte DECIMAL(12,2) DEFAULT 0,
            pago TINYINT(1) DEFAULT 0,

            viatura_id INT DEFAULT NULL,
            motorista_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (viatura_id) REFERENCES veiculos(id) ON DELETE SET NULL,
            FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query(
        'CREATE TABLE IF NOT EXISTS avarias_problemas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(180) NOT NULL,
            descricao TEXT NOT NULL,
            prioridade ENUM(\'Baixa\',\'Média\',\'Alta\',\'Crítica\') NOT NULL DEFAULT \'Média\',
            status ENUM(\'Aberto\',\'Em análise\',\'Resolvido\',\'Fechado\') NOT NULL DEFAULT \'Aberto\',
            viatura_id INT DEFAULT NULL,
            carga_id INT DEFAULT NULL,
            reportado_por_id INT DEFAULT NULL,
            resolvido_por_id INT DEFAULT NULL,
            resposta_gestor TEXT DEFAULT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (viatura_id) REFERENCES veiculos(id) ON DELETE SET NULL,
            FOREIGN KEY (carga_id) REFERENCES cargas(id) ON DELETE SET NULL,
            FOREIGN KEY (reportado_por_id) REFERENCES utilizadores(id) ON DELETE SET NULL,
            FOREIGN KEY (resolvido_por_id) REFERENCES utilizadores(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $conn->query(
        'CREATE TABLE IF NOT EXISTS abastecimentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            veiculo_id INT NOT NULL,
            motorista_id INT DEFAULT NULL,
            data_abastecimento DATE NOT NULL,
            litros DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            custo_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            odometro_km INT DEFAULT NULL,
            posto VARCHAR(180) DEFAULT NULL,
            observacoes TEXT DEFAULT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
            FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    if ($conn->errno) {
        die('Erro ao criar estrutura de banco: ' . htmlspecialchars($conn->error));
    }

    return $conn;
}

function isLoggedIn() {
    return !empty($_SESSION['user']) && !empty($_SESSION['user_id']) && !empty($_SESSION['nivel_acesso']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function currentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function currentUserRole(): string {
    return $_SESSION['nivel_acesso'] ?? '';
}

function isAdmin(): bool {
    return currentUserRole() === 'admin';
}

function isGestor(): bool {
    return currentUserRole() === 'gestor';
}

function isMotorista(): bool {
    return currentUserRole() === 'motorista';
}

function canManageOperations(): bool {
    return isAdmin() || isGestor();
}

function canManageUsers(): bool {
    return isAdmin();
}

function redirectAfterLogin(): string {
    return isMotorista() ? 'cargas_listar.php' : 'index.php';
}

function denyAccess() {
    header('Location: ' . redirectAfterLogin());
    exit;
}

function requireRole(array $roles) {
    requireLogin();
    if (!in_array(currentUserRole(), $roles, true)) {
        denyAccess();
    }
}

function requireOperationsAccess() {
    requireRole(['admin', 'gestor']);
}

function requireUserManagementAccess() {
    requireRole(['admin']);
}

function getCurrentMotoristaId(mysqli $conn): ?int {
    if (!isMotorista()) {
        return null;
    }

    $userId = currentUserId();
    $stmt = $conn->prepare('SELECT id FROM motoristas WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    if (! $stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($motoristaId);
    if ($stmt->fetch()) {
        $stmt->close();
        return (int) $motoristaId;
    }

    $stmt->close();
    return null;
}

function getBaseUrl() {
    return rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
}

function siteUrl($path = '') {
    return getBaseUrl() . '/' . ltrim($path, '/');
}

// CSRF helpers
function create_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input_field() {
    $t = htmlspecialchars(create_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

initializeDatabaseSchema();
