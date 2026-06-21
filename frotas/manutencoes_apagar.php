<?php
require_once 'config.php';
requireOperationsAccess();

$id = intval($_GET['id'] ?? 0);
$token = $_GET['csrf'] ?? '';
if ($id <= 0) {
    header('Location: manutencoes_listar.php');
    exit;
}

if (!verify_csrf_token($token)) {
    die('Token CSRF inválido.');
}

$conn = getDbConnection();
$stmt = $conn->prepare('DELETE FROM historico_manutencoes_inspecoes WHERE id = ?');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    header('Location: manutencoes_listar.php');
    exit;
} else {
    die('Erro ao apagar registo: ' . htmlspecialchars($conn->error));
}
