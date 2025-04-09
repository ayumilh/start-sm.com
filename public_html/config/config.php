<?php
// Configurações do Banco de Dados
$host = 'localhost';  // O endereço do servidor do banco de dados
$db   = 'u964118359_sms';  // O nome do seu banco de dados
$user = 'root';  // Seu usuário do banco de dados
$pass = '';  // Sua senha do banco de dados
$charset = 'utf8mb4';  // O conjunto de caracteres

$dsn = "mysql:host=$host;dbname=$db;charset=$charset"; // Data Source Name com a porta
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Relatar erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Usar consultas preparadas
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage(); // Mensagem de erro detalhada
    exit; // Finaliza o script para evitar comportamento inesperado
}
?>
