<?php
require_once 'config.php';

$conn = getDbConnection();

function papScalar(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;
    $stmt->close();
    return $row[0] ?? null;
}

function papExec(mysqli $conn, string $sql, string $types = '', array $params = []): void {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException($error);
    }
    $stmt->close();
}

function papCleanQuestionMarks(mysqli $conn, string $table, array $columns): void {
    $replacements = [
        'Revis?o' => 'Revisão',
        'revis?o' => 'revisão',
        'Inspe??o' => 'Inspeção',
        'inspe??o' => 'inspeção',
        'Manuten??o' => 'Manutenção',
        'manuten??o' => 'manutenção',
        'Corre??o' => 'Correção',
        'corre??o' => 'correção',
        'Distribui??o' => 'Distribuição',
        'distribui??o' => 'distribuição',
        'oscila??o' => 'oscilação',
        'Oscila??o' => 'Oscilação',
        'vibra??o' => 'vibração',
        'Vibra??o' => 'Vibração',
        'ilumina??o' => 'iluminação',
        'Ilumina??o' => 'Iluminação',
        'suspens?o' => 'suspensão',
        'Suspens?o' => 'Suspensão',
        'diagn?stico' => 'diagnóstico',
        'Diagn?stico' => 'Diagnóstico',
        'eletr?nico' => 'eletrónico',
        'Eletr?nico' => 'Eletrónico',
        'relat?rio' => 'relatório',
        'Relat?rio' => 'Relatório',
        'inst?vel' => 'instável',
        'Inst?vel' => 'Instável',
        'pr?xima' => 'próxima',
        'Pr?xima' => 'Próxima',
        '?leo' => 'Óleo',
        'L?mpada' => 'Lâmpada',
        'l?mpada' => 'lâmpada',
        'substitu?da' => 'substituída',
        'Substitu?da' => 'Substituída',
        'Set?bal' => 'Setúbal',
        'Jo?o' => 'João',
        'S?o' => 'São',
        'n?o' => 'não',
        'N?o' => 'Não',
        'Campanh?' => 'Campanhã',
    ];

    foreach ($columns as $column) {
        foreach ($replacements as $bad => $good) {
            papExec(
                $conn,
                "UPDATE `$table` SET `$column` = REPLACE(`$column`, ?, ?) WHERE `$column` LIKE ?",
                'sss',
                [$bad, $good, '%' . $bad . '%']
            );
        }

        papExec($conn, "UPDATE `$table` SET `$column` = REPLACE(`$column`, '?', '') WHERE `$column` LIKE '%?%'");
    }
}

$conn->begin_transaction();

try {
    papExec($conn, "UPDATE utilizadores SET email = 'motorista@frota.local', nome = 'João Lopes' WHERE email = 'motorista@frota.lfasocal' OR nome = 'Joao Lopes'");
    papExec($conn, "UPDATE motoristas SET email = 'motorista@frota.local', nome_completo = 'João Lopes' WHERE email = 'motorista@frota.lfasocal' OR nome_completo = 'Joao Lopes'");
    papExec($conn, "UPDATE clientes SET nome = 'Coimbra Farma Distribuição' WHERE nome = 'Coimbra Farma Distribuicao'");

    $setubalId = papScalar($conn, "SELECT id FROM clientes WHERE nif_nipc = '517800221'");
    if (!$setubalId) {
        papExec($conn, "INSERT INTO clientes (tipo_cliente, nome, responsavel_contacto, nif_nipc, morada_fiscal, codigo_postal, localidade, pais, telefone, email, website, limite_credito, prazo_pagamento_dias, estado_cliente, lat, lon) VALUES ('Jurídica','Setúbal Pharma Hub','Inês Carvalho','517800221','Parque Industrial BlueBiz, Lote 12','2910-741','Setúbal','Portugal','265700430','logistica@setubalpharma.local','https://setubalpharma.local',22000,30,'Ativo',38.5244,-8.8882)");
    }

    $vianaId = papScalar($conn, "SELECT id FROM clientes WHERE nif_nipc = '516420990'");
    if (!$vianaId) {
        papExec($conn, "INSERT INTO clientes (tipo_cliente, nome, responsavel_contacto, nif_nipc, morada_fiscal, codigo_postal, localidade, pais, telefone, email, website, limite_credito, prazo_pagamento_dias, estado_cliente, lat, lon) VALUES ('Jurídica','Viana Metal Works','Rui Martins','516420990','Zona Industrial do Neiva, Rua 3','4935-232','Viana do Castelo','Portugal','258700612','expedicao@vianametal.local','https://vianametal.local',26000,45,'Ativo',41.6932,-8.8329)");
    }

    papExec($conn, "UPDATE cargas SET estado_carga = 'Em Trânsito' WHERE codigo_rastreio = 'FTL-PAP-102'");
    papExec($conn, "UPDATE avarias_problemas SET status = 'Em análise' WHERE titulo = 'Ruído anormal no eixo traseiro'");
    papExec($conn, "UPDATE avarias_problemas SET status = 'Em análise' WHERE status = '' OR status IS NULL");
    papExec($conn, "UPDATE avarias_problemas SET prioridade = 'Crítica' WHERE prioridade = '' OR prioridade IS NULL");
    papExec($conn, "UPDATE avarias_problemas SET prioridade = ? WHERE titulo = ?", 'ss', ['Crítica', 'Sensor de temperatura instável']);
    papExec($conn, "UPDATE avarias_problemas SET titulo = 'Sensor de temperatura instável' WHERE titulo LIKE 'Sensor de temperatura%'");
    papExec($conn, "UPDATE avarias_problemas SET titulo = 'Ruído anormal no eixo traseiro' WHERE titulo LIKE 'Ru%anormal no eixo traseiro'");
    papExec($conn, "UPDATE avarias_problemas SET titulo = 'Lâmpada lateral substituída' WHERE titulo LIKE 'L%mpada lateral substitu%da'");
    papExec($conn, "UPDATE avarias_problemas SET titulo = 'Temperatura da caixa refrigerada instável' WHERE titulo = 'Temperatura da caixa refrigerada instavel'");
    papExec($conn, "UPDATE avarias_problemas SET descricao = 'Oscilação entre 3 e 8 graus durante 20 minutos.' WHERE descricao = 'Oscilacao entre 3 e 8 graus durante 20 minutos.'");
    papExec($conn, "UPDATE cargas SET local_recolha = 'Setúbal Pharma Hub, Setúbal' WHERE codigo_rastreio = 'FTL-PAP-101'");
    papExec($conn, "UPDATE cargas SET local_entrega = 'Setúbal Pharma Hub, Setúbal' WHERE codigo_rastreio = 'FTL-PAP-104'");
    papExec($conn, "UPDATE cargas SET local_recolha = 'Coimbra Farma Distribuição, Coimbra' WHERE codigo_rastreio = 'FTL-2026-003'");

    papCleanQuestionMarks($conn, 'manutencoes', ['tipo', 'descricao']);
    papCleanQuestionMarks($conn, 'historico_manutencoes_inspecoes', ['descricao_problema', 'acoes_realizadas', 'url_relatorio_pdf']);
    papCleanQuestionMarks($conn, 'avarias_problemas', ['titulo', 'descricao', 'resposta_gestor']);
    papCleanQuestionMarks($conn, 'cargas', ['codigo_rastreio', 'descricao', 'tipo_carga', 'local_recolha', 'local_entrega']);
    papCleanQuestionMarks($conn, 'clientes', ['nome', 'responsavel_contacto', 'morada_fiscal', 'codigo_postal', 'localidade', 'pais', 'telefone', 'email', 'website']);
    papCleanQuestionMarks($conn, 'motoristas', ['nome_completo', 'email', 'numero_carta_conducao', 'categoria_carta', 'numero_cam_cqm', 'numero_mecanografico', 'tipo_contrato']);
    papCleanQuestionMarks($conn, 'utilizadores', ['nome', 'email']);
    papCleanQuestionMarks($conn, 'veiculos', ['matricula', 'modelo', 'status']);
    papExec($conn, "UPDATE manutencoes SET descricao = REPLACE(descricao, ' aps ', ' apos ') WHERE descricao LIKE '% aps %'");

    $conn->commit();
    echo "Dados PAP corrigidos/atualizados.\n";
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "Erro: " . $e->getMessage() . "\n");
    exit(1);
}
