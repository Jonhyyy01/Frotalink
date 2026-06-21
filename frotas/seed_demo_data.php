<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script so pode ser executado pela linha de comandos.');
}

require_once 'config.php';

$conn = getDbConnection();

function runStatement(mysqli $conn, string $sql, string $types = '', array $params = []): void {
    $stmt = $conn->prepare($sql);
    if (! $stmt) {
        throw new RuntimeException($conn->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    if (! $stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }

    $stmt->close();
}

function scalarValue(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $stmt = $conn->prepare($sql);
    if (! $stmt) {
        throw new RuntimeException($conn->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;
    $stmt->close();

    return $row[0] ?? null;
}

function ensureUser(mysqli $conn, string $nome, string $email, string $password, string $role): int {
    $id = scalarValue($conn, 'SELECT id FROM utilizadores WHERE email = ? LIMIT 1', 's', [$email]);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($id) {
        runStatement($conn, 'UPDATE utilizadores SET nome = ?, password_hash = ?, nivel_acesso = ?, status = "ativo" WHERE id = ? AND email <> "admin@frota.local"', 'sssi', [$nome, $hash, $role, $id]);
        return (int) $id;
    }

    runStatement($conn, 'INSERT INTO utilizadores (nome, email, password_hash, nivel_acesso, status) VALUES (?, ?, ?, ?, "ativo")', 'ssss', [$nome, $email, $hash, $role]);
    return (int) $conn->insert_id;
}

function ensureVehicle(mysqli $conn, string $matricula, string $modelo, string $status, int $km, float $consumo, float $lat, float $lon): int {
    $id = scalarValue($conn, 'SELECT id FROM veiculos WHERE matricula = ? LIMIT 1', 's', [$matricula]);

    if ($id) {
        runStatement($conn, 'UPDATE veiculos SET modelo = ?, status = ?, km_total = ?, consumo_medio = ?, lat = ?, lon = ? WHERE id = ?', 'ssidddi', [$modelo, $status, $km, $consumo, $lat, $lon, $id]);
        return (int) $id;
    }

    runStatement($conn, 'INSERT INTO veiculos (matricula, modelo, status, km_total, consumo_medio, lat, lon) VALUES (?, ?, ?, ?, ?, ?, ?)', 'sssiddd', [$matricula, $modelo, $status, $km, $consumo, $lat, $lon]);
    return (int) $conn->insert_id;
}

function ensureCliente(mysqli $conn, array $cliente): int {
    $id = scalarValue($conn, 'SELECT id FROM clientes WHERE nif_nipc = ? LIMIT 1', 's', [$cliente['nif']]);

    if ($id) {
        runStatement(
            $conn,
            'UPDATE clientes SET tipo_cliente = ?, nome = ?, responsavel_contacto = ?, morada_fiscal = ?, codigo_postal = ?, localidade = ?, pais = ?, telefone = ?, email = ?, limite_credito = ?, estado_cliente = ?, lat = ?, lon = ? WHERE id = ?',
            'sssssssssdsddi',
            [$cliente['tipo'], $cliente['nome'], $cliente['responsavel'], $cliente['morada'], $cliente['postal'], $cliente['localidade'], $cliente['pais'], $cliente['telefone'], $cliente['email'], $cliente['limite'], $cliente['estado'], $cliente['lat'], $cliente['lon'], $id]
        );
        return (int) $id;
    }

    runStatement(
        $conn,
        'INSERT INTO clientes (tipo_cliente, nome, responsavel_contacto, nif_nipc, morada_fiscal, codigo_postal, localidade, pais, telefone, email, limite_credito, estado_cliente, lat, lon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'ssssssssssdsdd',
        [$cliente['tipo'], $cliente['nome'], $cliente['responsavel'], $cliente['nif'], $cliente['morada'], $cliente['postal'], $cliente['localidade'], $cliente['pais'], $cliente['telefone'], $cliente['email'], $cliente['limite'], $cliente['estado'], $cliente['lat'], $cliente['lon']]
    );
    return (int) $conn->insert_id;
}

function ensureMotorista(mysqli $conn, array $motorista): int {
    $id = scalarValue($conn, 'SELECT id FROM motoristas WHERE numero_mecanografico = ? LIMIT 1', 's', [$motorista['numero']]);

    if ($id) {
        runStatement(
            $conn,
            'UPDATE motoristas SET nome_completo = ?, telefone = ?, email = ?, nif = ?, numero_carta_conducao = ?, validade_carta = ?, categoria_carta = ?, numero_cam_cqm = ?, validade_cam = ?, data_admissao = ?, tipo_contrato = ?, estado = ?, disponibilidade = ?, viatura_atual_id = ?, user_id = ? WHERE id = ?',
            'sssssssssssssiii',
            [$motorista['nome'], $motorista['telefone'], $motorista['email'], $motorista['nif'], $motorista['carta'], $motorista['validade_carta'], $motorista['categoria'], $motorista['cam'], $motorista['validade_cam'], $motorista['admissao'], $motorista['contrato'], $motorista['estado'], $motorista['disponibilidade'], $motorista['viatura_id'], $motorista['user_id'], $id]
        );
        return (int) $id;
    }

    runStatement(
        $conn,
        'INSERT INTO motoristas (nome_completo, telefone, email, nif, numero_carta_conducao, validade_carta, categoria_carta, numero_cam_cqm, validade_cam, numero_mecanografico, data_admissao, tipo_contrato, estado, disponibilidade, viatura_atual_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'ssssssssssssssii',
        [$motorista['nome'], $motorista['telefone'], $motorista['email'], $motorista['nif'], $motorista['carta'], $motorista['validade_carta'], $motorista['categoria'], $motorista['cam'], $motorista['validade_cam'], $motorista['numero'], $motorista['admissao'], $motorista['contrato'], $motorista['estado'], $motorista['disponibilidade'], $motorista['viatura_id'], $motorista['user_id']]
    );
    return (int) $conn->insert_id;
}

function ensureCarga(mysqli $conn, array $carga): int {
    $id = scalarValue($conn, 'SELECT id FROM cargas WHERE codigo_rastreio = ? LIMIT 1', 's', [$carga['codigo']]);

    if ($id) {
        runStatement(
            $conn,
            'UPDATE cargas SET estado_carga = ?, descricao = ?, tipo_carga = ?, peso_kg = ?, volume_m3 = ?, quantidade_paletes = ?, local_recolha = ?, data_hora_recolha_prevista = ?, local_entrega = ?, data_hora_entrega_prevista = ?, cliente_id = ?, valor_transporte = ?, pago = ?, viatura_id = ?, motorista_id = ? WHERE id = ?',
            'sssddissssidiiii',
            [$carga['estado'], $carga['descricao'], $carga['tipo'], $carga['peso'], $carga['volume'], $carga['paletes'], $carga['recolha'], $carga['recolha_prevista'], $carga['entrega'], $carga['entrega_prevista'], $carga['cliente_id'], $carga['valor'], $carga['pago'], $carga['viatura_id'], $carga['motorista_id'], $id]
        );
        return (int) $id;
    }

    runStatement(
        $conn,
        'INSERT INTO cargas (codigo_rastreio, estado_carga, descricao, tipo_carga, peso_kg, volume_m3, quantidade_paletes, local_recolha, data_hora_recolha_prevista, local_entrega, data_hora_entrega_prevista, cliente_id, valor_transporte, pago, viatura_id, motorista_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'ssssddissssidiii',
        [$carga['codigo'], $carga['estado'], $carga['descricao'], $carga['tipo'], $carga['peso'], $carga['volume'], $carga['paletes'], $carga['recolha'], $carga['recolha_prevista'], $carga['entrega'], $carga['entrega_prevista'], $carga['cliente_id'], $carga['valor'], $carga['pago'], $carga['viatura_id'], $carga['motorista_id']]
    );
    return (int) $conn->insert_id;
}

$gestorId = ensureUser($conn, 'Gestor Operacional', 'gestor@frota.local', 'gestor123', 'gestor');
$motoristaUserId = ensureUser($conn, 'João Lopes', 'motorista@frota.local', 'motorista123', 'motorista');
$motoristaExtraUserId = ensureUser($conn, 'Marta Silva', 'marta.motorista@frota.local', 'motorista123', 'motorista');

$vehicleIds = [
    ensureVehicle($conn, '30-AG-FC', 'Scania R560 V8', 'ativo', 184320, 29.8, 41.1579, -8.6291),
    ensureVehicle($conn, '82-TL-19', 'Volvo FH 500', 'ativo', 126840, 27.4, 41.5454, -8.4265),
    ensureVehicle($conn, '15-MT-44', 'Mercedes Actros 1845', 'em_manutencao', 213700, 31.2, 40.6405, -8.6538),
    ensureVehicle($conn, '67-RD-02', 'MAN TGX 18.470', 'ocioso', 98540, 26.1, 38.7223, -9.1393),
];

$clientes = [
    ['tipo' => 'Jurídica', 'nome' => 'Porto Fresh Logistics', 'responsavel' => 'Ana Ribeiro', 'nif' => '510900111', 'morada' => 'Rua do Freixo 1071', 'postal' => '4300-219', 'localidade' => 'Porto', 'pais' => 'Portugal', 'telefone' => '225100221', 'email' => 'operacoes@portofresh.local', 'limite' => 18000.00, 'estado' => 'Ativo', 'lat' => 41.1496, 'lon' => -8.5855],
    ['tipo' => 'Jurídica', 'nome' => 'Braga Industrial SA', 'responsavel' => 'Carlos Martins', 'nif' => '510900222', 'morada' => 'Parque Industrial de Celeiros', 'postal' => '4705-414', 'localidade' => 'Braga', 'pais' => 'Portugal', 'telefone' => '253200410', 'email' => 'logistica@bragaindustrial.local', 'limite' => 24000.00, 'estado' => 'Ativo', 'lat' => 41.5332, 'lon' => -8.4321],
    ['tipo' => 'Jurídica', 'nome' => 'Aveiro Tech Components', 'responsavel' => 'Sofia Costa', 'nif' => '510900333', 'morada' => 'Zona Industrial de Taboeira', 'postal' => '3800-055', 'localidade' => 'Aveiro', 'pais' => 'Portugal', 'telefone' => '234900501', 'email' => 'supply@aveirotech.local', 'limite' => 12500.00, 'estado' => 'Ativo', 'lat' => 40.6566, 'lon' => -8.6208],
    ['tipo' => 'Jurídica', 'nome' => 'Lisboa Retail Group', 'responsavel' => 'Miguel Ferreira', 'nif' => '510900444', 'morada' => 'Avenida Infante Dom Henrique', 'postal' => '1950-421', 'localidade' => 'Lisboa', 'pais' => 'Portugal', 'telefone' => '218700300', 'email' => 'rececao@lisboaretail.local', 'limite' => 30000.00, 'estado' => 'Ativo', 'lat' => 38.7436, 'lon' => -9.1028],
    ['tipo' => 'Jurídica', 'nome' => 'Coimbra Farma Distribuição', 'responsavel' => 'Helena Matos', 'nif' => '510900555', 'morada' => 'Rua da Sofia 91', 'postal' => '3000-389', 'localidade' => 'Coimbra', 'pais' => 'Portugal', 'telefone' => '239800100', 'email' => 'compras@coimbrafarma.local', 'limite' => 9000.00, 'estado' => 'Bloqueado', 'lat' => 40.2130, 'lon' => -8.4292],
];

$clienteIds = [];
foreach ($clientes as $cliente) {
    $clienteIds[] = ensureCliente($conn, $cliente);
}

$motoristaIds = [];
$motoristaIds[] = ensureMotorista($conn, ['nome' => 'João Lopes', 'telefone' => '912345610', 'email' => 'motorista@frota.local', 'nif' => '210100001', 'carta' => 'C-123456', 'validade_carta' => '2028-11-15', 'categoria' => 'C+E', 'cam' => 'CAM-88721', 'validade_cam' => '2027-09-30', 'numero' => 'MOT-001', 'admissao' => '2022-03-14', 'contrato' => 'Contrato de trabalho sem termo', 'estado' => 'Ativo', 'disponibilidade' => 'Em Viagem', 'viatura_id' => $vehicleIds[0], 'user_id' => $motoristaUserId]);
$motoristaIds[] = ensureMotorista($conn, ['nome' => 'Marta Silva', 'telefone' => '912345611', 'email' => 'marta.motorista@frota.local', 'nif' => '210100002', 'carta' => 'C-654321', 'validade_carta' => '2029-04-20', 'categoria' => 'C', 'cam' => 'CAM-90231', 'validade_cam' => '2028-01-12', 'numero' => 'MOT-002', 'admissao' => '2023-07-01', 'contrato' => 'Contrato de trabalho sem termo', 'estado' => 'Ativo', 'disponibilidade' => 'Disponível', 'viatura_id' => $vehicleIds[1], 'user_id' => $motoristaExtraUserId]);
$motoristaIds[] = ensureMotorista($conn, ['nome' => 'Pedro Almeida', 'telefone' => '912345612', 'email' => 'pedro.almeida@frota.local', 'nif' => '210100003', 'carta' => 'C-888222', 'validade_carta' => '2027-06-18', 'categoria' => 'C+E', 'cam' => 'CAM-77442', 'validade_cam' => '2026-12-05', 'numero' => 'MOT-003', 'admissao' => '2021-10-25', 'contrato' => 'Contrato de trabalho a termo certo', 'estado' => 'Ativo', 'disponibilidade' => 'Disponível', 'viatura_id' => $vehicleIds[3], 'user_id' => null]);

$now = new DateTime();
$cargaIds = [];
$cargaIds[] = ensureCarga($conn, ['codigo' => 'FTL-2026-001', 'estado' => 'Pendente', 'descricao' => 'Paletes de componentes eletrónicos para entrega urgente.', 'tipo' => 'Eletrónica', 'peso' => 3400.5, 'volume' => 16.2, 'paletes' => 8, 'recolha' => 'Aveiro Tech Components, Aveiro', 'recolha_prevista' => $now->format('Y-m-d') . ' 09:30:00', 'entrega' => 'Porto Fresh Logistics, Porto', 'entrega_prevista' => $now->format('Y-m-d') . ' 14:45:00', 'cliente_id' => $clienteIds[2], 'valor' => 780.00, 'pago' => 0, 'viatura_id' => $vehicleIds[0], 'motorista_id' => $motoristaIds[0]]);
$cargaIds[] = ensureCarga($conn, ['codigo' => 'FTL-2026-002', 'estado' => 'Em Trânsito', 'descricao' => 'Carga refrigerada com controlo de temperatura.', 'tipo' => 'Refrigerada', 'peso' => 6200.0, 'volume' => 24.8, 'paletes' => 14, 'recolha' => 'Porto Fresh Logistics, Porto', 'recolha_prevista' => $now->format('Y-m-d') . ' 07:15:00', 'entrega' => 'Lisboa Retail Group, Lisboa', 'entrega_prevista' => $now->format('Y-m-d') . ' 18:30:00', 'cliente_id' => $clienteIds[0], 'valor' => 1450.00, 'pago' => 0, 'viatura_id' => $vehicleIds[1], 'motorista_id' => $motoristaIds[1]]);
$cargaIds[] = ensureCarga($conn, ['codigo' => 'FTL-2026-003', 'estado' => 'Entregue', 'descricao' => 'Produtos farmacêuticos acondicionados.', 'tipo' => 'Farmacêutica', 'peso' => 1200.0, 'volume' => 8.5, 'paletes' => 4, 'recolha' => 'Coimbra Farma Distribuição, Coimbra', 'recolha_prevista' => $now->modify('-1 day')->format('Y-m-d') . ' 08:00:00', 'entrega' => 'Braga Industrial SA, Braga', 'entrega_prevista' => $now->format('Y-m-d') . ' 13:20:00', 'cliente_id' => $clienteIds[4], 'valor' => 620.00, 'pago' => 1, 'viatura_id' => $vehicleIds[3], 'motorista_id' => $motoristaIds[2]]);
$now = new DateTime();
$cargaIds[] = ensureCarga($conn, ['codigo' => 'FTL-2026-004', 'estado' => 'Pendente', 'descricao' => 'Material industrial pesado para linha de montagem.', 'tipo' => 'Industrial', 'peso' => 8900.0, 'volume' => 31.4, 'paletes' => 18, 'recolha' => 'Braga Industrial SA, Braga', 'recolha_prevista' => $now->modify('+1 day')->format('Y-m-d') . ' 10:00:00', 'entrega' => 'Aveiro Tech Components, Aveiro', 'entrega_prevista' => $now->format('Y-m-d') . ' 17:00:00', 'cliente_id' => $clienteIds[1], 'valor' => 1180.00, 'pago' => 0, 'viatura_id' => $vehicleIds[0], 'motorista_id' => $motoristaIds[0]]);

$today = new DateTime();
for ($i = 0; $i < 7; $i++) {
    $date = (clone $today)->modify("-{$i} days")->format('Y-m-d');
    $kmA = [186, 242, 155, 310, 278, 420, 198][$i];
    $kmB = [96, 165, 210, 144, 320, 180, 260][$i];
    runStatement($conn, 'DELETE FROM viagens WHERE veiculo_id IN (?, ?) AND (data_viagem = ? OR data_viagem = "0000-00-00")', 'iis', [$vehicleIds[0], $vehicleIds[1], $date]);
    runStatement($conn, 'INSERT INTO viagens (veiculo_id, data_viagem, distancia_km) VALUES (?, ?, ?), (?, ?, ?)', 'isiisi', [$vehicleIds[0], $date, $kmA, $vehicleIds[1], $date, $kmB]);
}

runStatement($conn, 'DELETE FROM manutencoes WHERE tipo IN ("Revisão de travões", "Mudança de óleo", "Inspeção tacógrafo")');
runStatement($conn, 'INSERT INTO manutencoes (veiculo_id, tipo, status, criticidade, descricao, data_agendada) VALUES (?, "Revisão de travões", "pendente", "critico", "Ruído ao travar reportado na última viagem.", ?), (?, "Mudança de óleo", "pendente", "medio", "Serviço preventivo aos 185.000 km.", ?), (?, "Inspeção tacógrafo", "pendente", "alto", "Validar registos antes da próxima rota internacional.", ?)', 'isisis', [$vehicleIds[2], (clone $today)->modify('+1 day')->format('Y-m-d'), $vehicleIds[0], (clone $today)->modify('+3 days')->format('Y-m-d'), $vehicleIds[1], (clone $today)->modify('+2 days')->format('Y-m-d')]);

runStatement($conn, 'DELETE FROM avarias_problemas WHERE titulo IN ("Luz de avaria no painel", "Porta traseira com fecho preso", "Temperatura da caixa refrigerada instável")');
runStatement($conn, 'INSERT INTO avarias_problemas (titulo, descricao, prioridade, status, viatura_id, carga_id, reportado_por_id, resposta_gestor) VALUES ("Luz de avaria no painel", "A luz de motor acendeu durante a rota Aveiro-Porto.", "Alta", "Aberto", ?, ?, ?, NULL), ("Porta traseira com fecho preso", "A porta traseira direita demora a trancar depois da descarga.", "Média", "Em análise", ?, ?, ?, "A oficina foi notificada para verificação."), ("Temperatura da caixa refrigerada instável", "Oscilação entre 3 e 8 graus durante 20 minutos.", "Crítica", "Aberto", ?, ?, ?, NULL)', 'iiiiiiiii', [$vehicleIds[0], $cargaIds[0], $motoristaUserId, $vehicleIds[3], $cargaIds[2], $gestorId, $vehicleIds[1], $cargaIds[1], $motoristaExtraUserId]);

echo "Dados de demonstracao criados/atualizados.\n";
echo "Gestor: gestor@frota.local / gestor123\n";
echo "Motorista: motorista@frota.local / motorista123\n";
echo "Motorista extra: marta.motorista@frota.local / motorista123\n";
