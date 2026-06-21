<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script so pode ser executado pela linha de comandos.');
}

require_once 'config.php';

$conn = getDbConnection();

function fixSpecialCharsExec(mysqli $conn, string $sql, string $types = '', array $params = []): int {
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

    $affected = $stmt->affected_rows;
    $stmt->close();

    return max(0, $affected);
}

function fixSpecialCharsInColumns(mysqli $conn, string $table, array $columns, array $replacements): int {
    $total = 0;

    foreach ($columns as $column) {
        foreach ($replacements as $bad => $good) {
            $total += fixSpecialCharsExec(
                $conn,
                "UPDATE `$table` SET `$column` = REPLACE(`$column`, ?, ?) WHERE `$column` LIKE ?",
                'sss',
                [$bad, $good, '%' . $bad . '%']
            );
        }
    }

    return $total;
}

$replacements = [
    'Campanh?' => 'Campanhã',
    'campanh?' => 'campanhã',
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
    'c?digo' => 'código',
    'C?digo' => 'Código',
    'combust?vel' => 'combustível',
    'Combust?vel' => 'Combustível',
    'od?metro' => 'odómetro',
    'Od?metro' => 'Odómetro',
    'opera??es' => 'operações',
    'Opera??es' => 'Operações',
    'rece??o' => 'receção',
    'Rece??o' => 'Receção',
    'p?gina' => 'página',
    'P?gina' => 'Página',
    'inv?lido' => 'inválido',
    'Inv?lido' => 'Inválido',
    'poss?vel' => 'possível',
    'Poss?vel' => 'Possível',
];

$targets = [
    'abastecimentos' => ['posto', 'observacoes'],
    'avarias_problemas' => ['titulo', 'descricao', 'prioridade', 'status', 'resposta_gestor'],
    'cargas' => ['codigo_rastreio', 'estado_carga', 'descricao', 'tipo_carga', 'local_recolha', 'local_entrega'],
    'clientes' => ['tipo_cliente', 'nome', 'responsavel_contacto', 'morada_fiscal', 'codigo_postal', 'localidade', 'pais', 'telefone', 'email', 'website', 'estado_cliente'],
    'historico_manutencoes_inspecoes' => ['tipo_acao', 'status', 'prioridade', 'descricao_problema', 'acoes_realizadas', 'resultado_inspecao', 'url_relatorio_pdf'],
    'manutencoes' => ['tipo', 'status', 'criticidade', 'descricao'],
    'motoristas' => ['nome_completo', 'telefone', 'email', 'nif', 'numero_carta_conducao', 'categoria_carta', 'numero_cam_cqm', 'numero_mecanografico', 'tipo_contrato', 'estado', 'disponibilidade'],
    'utilizadores' => ['nome', 'email', 'nivel_acesso', 'status'],
    'veiculos' => ['matricula', 'modelo', 'status'],
];

$conn->begin_transaction();

try {
    $total = 0;

    foreach ($targets as $table => $columns) {
        $total += fixSpecialCharsInColumns($conn, $table, $columns, $replacements);
    }

    $conn->commit();
    echo "Caracteres especiais corrigidos. Linhas atualizadas: {$total}\n";
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "Erro: " . $e->getMessage() . "\n");
    exit(1);
}
