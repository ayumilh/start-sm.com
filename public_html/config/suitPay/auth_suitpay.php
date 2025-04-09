<?php
// Endpoint da API de autenticação
$authUrl = 'https://sandbox.ws.suitpay.app/api/v1/auth/token';

// Dados de autenticação
$data = array(
    'grant_type' => 'client_credentials',
    'client_id' => 'testesandbox_1687443996536',
    'client_secret' => '5b7d6ed3407bc8c7efd45ac9d4c277004145afb96752e1252c2082d3211fe901177e09493c0d4f57b650d2b2fc1b062d', 
);

// Inicializar cURL
$ch = curl_init();

// Adicionar cabeçalhos à requisição
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json',
));

// Configuração da requisição
curl_setopt($ch, CURLOPT_URL, $authUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

// Adicionar timeout para evitar hangs
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 segundos de timeout

// Executar a requisição
$response = curl_exec($ch);

// Verificar se houve erro na execução do cURL
if ($response === false) {
    error_log("Erro na requisição cURL: " . curl_error($ch));
    echo json_encode(['status' => 'error', 'message' => 'Erro na requisição cURL']);
    exit;
}

// Log da resposta da API para depuração
error_log("Resposta da API: " . $response);

// Fechar a conexão cURL
curl_close($ch);

// Converter a resposta JSON
$responseData = json_decode($response, true);

// Verifique a resposta da API
if (!$responseData) {
    // Caso a resposta não seja JSON válido
    error_log("Erro ao decodificar a resposta JSON: " . $response);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao decodificar a resposta JSON']);
    exit;
}

// Verifique se o access_token está na resposta
if (isset($responseData['access_token'])) {
    $accessToken = $responseData['access_token'];
    error_log("Token de acesso: " . $accessToken);  // Log do token
    echo json_encode(['status' => 'success', 'access_token' => $accessToken]);
} else {
    error_log("Erro ao obter o token: " . print_r($responseData, true));
    echo json_encode(['status' => 'error', 'message' => 'Erro ao obter o token de acesso']);
    exit; // Saia do script
}

?>
