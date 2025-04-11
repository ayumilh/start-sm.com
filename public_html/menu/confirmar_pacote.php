<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$preco = isset($_POST['preco']) ? floatval($_POST['preco']) : 0;
$pacote_id = isset($_POST['pacote_id']) ? intval($_POST['pacote_id']) : 0;

if ($preco <= 0 || $pacote_id <= 0) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Dados inválidos']);
    exit;
}

// Consulta o saldo atual do usuário
$stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Usuário não encontrado']);
    exit;
}

$saldoAtual = floatval($usuario['saldo']);

if ($saldoAtual < $preco) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Saldo insuficiente']);
    exit;
}

// Desconta o valor do saldo
$novoSaldo = $saldoAtual - $preco;
$stmt = $pdo->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
$stmt->execute([$novoSaldo, $usuario_id]);

// Registra no histórico
$stmt = $pdo->prepare("INSERT INTO historico_saldo (usuario_id, valor, tipo, data_hora) VALUES (?, ?, 'saida', NOW())");
$stmt->execute([$usuario_id, $preco]);

echo json_encode(['status' => 'success', 'mensagem' => 'Pacote confirmado com sucesso', 'novoSaldo' => number_format($novoSaldo, 2, ',', '.')]);
