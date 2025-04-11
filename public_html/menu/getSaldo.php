<?php
require '../config/config.php'; 

session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['error' => 'Usuário não autenticado']);
  exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Consulta para obter o saldo atual do usuário
$stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se o usuário foi encontrado
if (!$usuario) {
  echo json_encode(['error' => 'Usuário não encontrado']);
  exit;
}

// Retorna o saldo do usuário em formato JSON
echo json_encode(['saldo' => $usuario['saldo']]);
exit;
?>
