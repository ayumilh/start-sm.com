<?php
session_start();

// Habilitar a exibição de erros para facilitar o debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclua o arquivo de configuração para conectar ao banco de dados
require '../config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não está logado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Recebe os dados via POST (garanta que esteja recebendo um JSON)
$data = json_decode(file_get_contents('php://input'), true);

// Verifique os dados recebidos
if (empty($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Nenhum dado recebido']);
    exit;
}

// Depuração: Verifique se as chaves estão presentes no array
if (!isset($data['requestNumber'])) {
    echo json_encode(['status' => 'error', 'message' => 'requestNumber não foi enviado']);
    exit;
}


// Verificar se a chave 'totalAmount' está presente
if (!isset($data['totalAmount'])) {
    echo json_encode(['status' => 'error', 'message' => 'totalAmount não foi enviado']);
    exit;
}

$totalAmount = $data['totalAmount'];

// Descontar 1 real da taxa
$totalAmount = $totalAmount - 1;  // Subtrai 1 real da taxa

// Verifique o valor calculado
if ($totalAmount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Total inválido']);
    exit;
}

// Certifique-se de que o 'clientId' esteja sendo passado corretamente
if (!isset($data['clientId']) || empty($data['clientId'])) {
    echo json_encode(['status' => 'error', 'message' => 'clientId não foi recebido corretamente']);
    exit;
}

$clientId = $data['clientId'];  // Atribui o 'clientId' recebido

// Função para inserir os dados no banco de dados
function insertPaymentData($requestNumber, $dueDate, $totalAmount, $paymentCode, $paymentCodeBase64, $clientName, $clientDocument, $clientId)
{
    global $pdo;

    try {
        // Prepara o comando SQL para inserir os dados na tabela de pagamentos
        $stmt = $pdo->prepare("
            INSERT INTO pagamentos 
            (request_number, due_date, total_amount, payment_code, payment_code_base64, client_name, client_document, client_id, status, created_at)
            VALUES 
            (:request_number, :due_date, :total_amount, :payment_code, :payment_code_base64, :client_name, :client_document, :client_id,'Pendente', NOW())
        ");

        // Executa o comando SQL com os parâmetros fornecidos
        $stmt->execute([
            ':request_number' => $requestNumber,
            ':due_date' => $dueDate,
            ':total_amount' => $totalAmount,
            ':payment_code' => $paymentCode,
            ':payment_code_base64' => $paymentCodeBase64,
            ':client_name' => $clientName,
            ':client_document' => $clientDocument,
            ':client_id' => $clientId
        ]);

        // Retorna a resposta de sucesso após inserir os dados
        return ['status' => 'success', 'message' => 'Pagamento registrado com sucesso.'];
    } catch (Exception $e) {
        // Se ocorrer um erro, retorna uma mensagem de erro
        return ['status' => 'error', 'message' => 'Erro ao registrar pagamento: ' . $e->getMessage()];
    }
}

// Chama a função para inserir os dados no banco de dados
$response = insertPaymentData(
    $data['requestNumber'],
    $data['dueDate'],
    $totalAmount,
    $data['paymentCode'],
    $data['paymentCodeBase64'],
    $data['clientName'],
    $data['clientDocument'],
    $clientId
);

// Retorna a resposta JSON
echo json_encode($response);
