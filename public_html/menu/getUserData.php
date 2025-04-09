<?php
session_start();
header('Content-Type: application/json');

require '../config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não logado.']);
    exit;
}

$userId = $_SESSION['usuario_id'];

// Consulta ao banco de dados para buscar os dados do usuário
try {
    $stmt = $pdo->prepare("SELECT nome, documento, id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Usuário não encontrado.']);
    }
} catch (Exception $e) {
    // Em caso de erro na consulta ao banco de dados
    echo json_encode(['status' => 'error', 'message' => 'Erro ao consultar o banco de dados: ' . $e->getMessage()]);
}
