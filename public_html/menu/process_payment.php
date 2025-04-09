<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../config/config.php';
require '../config/suitPay/suitpay_config.php';
require '../config/suitPay/auth_suitpay.php';

header('Content-Type: application/json');

if (!isset($_POST['totalAmount']) || !isset($_POST['usuario_id']) || !isset($_POST['usuario_nome'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
    exit;
}

// function suitpayApiRequest($endpoint, $data = null, $method = 'POST')
// {
//     // Verifique se o access_token está disponível na sessão
//     if (!isset($_SESSION['access_token'])) {
//         error_log("Erro: Token de acesso não encontrado na sessão.");
//         return ['status' => 'error', 'message' => 'Token de acesso não encontrado'];
//     }

//     $url = SUITPAY_API_URL . $endpoint;
//     $headers = [
//         "Content-Type: application/json",
//         "Authorization: Bearer " . $_SESSION['access_token'] // Usando o token armazenado na sessão
//     ];

//     $ch = curl_init();

//     curl_setopt($ch, CURLOPT_URL, $url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//     if ($method == 'POST') {
//         curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
//     } elseif ($method == 'GET') {
//         curl_setopt($ch, CURLOPT_HTTPGET, true);
//     }

//     $response = curl_exec($ch);
//     $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//     curl_close($ch);

//     return json_decode($response, true); // Retorna a resposta da API
// }



$totalAmount = $_POST['totalAmount'];
$usuario_id = $_POST['usuario_id'];
$usuario_email = $_POST['usuario_email'];

// Função para processar o pagamento
function processPayment($totalAmount, $user_id, $user_email){
    // Converte $totalAmount para float (numérico) antes de multiplicar por 100
    $totalAmount = floatval($totalAmount);

    // Verifica se $totalAmount é um número válido
    if ($totalAmount <= 0) {
        error_log("Erro: O valor de totalAmount é inválido.");
        return ['status' => 'error', 'message' => 'Valor de pagamento inválido'];
    }

    $paymentData = [
        'amount' => $totalAmount * 100, // Convertendo para centavos
        'currency' => 'BRL',
        'order_id' => 'order_' . uniqid(), // Gerando ID único
        'customer' => [
            'id' => $user_id,
            'email' => $user_email, // Agora usa o nome do usuário enviado
        ],
        'payment_method' => 'CREDIT_CARD', // Ou o método de pagamento desejado
        'callback_url' => 'https://start-sms.com/callback', // URL de callback
        'return_url' => 'https://start-sms.com/obrigado', // URL de retorno após o pagamento
    ];

    // Registra os dados enviados para a API
    // error_log("Dados enviados para a API: " . print_r($paymentData, true));

    // $response = suitpayApiRequest('/payments', $paymentData); // Chama a API do SuitPay

    // Registra a resposta da API
    // error_log("Resposta da API: " . print_r($response, true));

    // Verifica se a resposta não é nula e se contém a chave 'status'
    // if (is_array($response) && isset($response['status'])) {
    //     // Erro: falta uma chave de fechamento ou vírgula
    //     if ($response['status'] == 'success') {
    //         return ['status' => 'success', 'payment_url' => $response['payment_url']];
    //     } else {
    //         return ['status' => 'error', 'message' => $response['message']];
    //     }
    // } else {
    //     // Caso a resposta seja nula ou não tenha o índice 'status', loga o erro
    //     error_log("Erro na resposta da API: " . print_r($response, true));
    //     return ['status' => 'error', 'message' => 'Resposta inválida ou malformada da API'];
    // }
}

// Processando o pagamento
$paymentResult = processPayment($totalAmount, $usuario_id, $usuario_email);

echo json_encode($paymentResult);
