<?php
// Definindo o cabeçalho para retornar JSON
header('Content-Type: application/json');

session_start();

// Habilitar a exibição de erros para facilitar o debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/config.php';

// Inicializa a resposta padrão
$response = [
    'success' => false,
    'message' => 'Erro no servidor. Tente novamente mais tarde.'
];

// Verifica se o método é POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe os dados enviados pelo JavaScript
    $data = json_decode(file_get_contents('php://input'), true);
    // Verifique se o json_decode retornou null
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response = [
            'success' => false,
            'message' => 'Erro ao processar JSON: ' . json_last_error_msg()
        ];
        echo json_encode($response);
        exit();
    }

    // Verifica se a estrutura do JSON está correta
    if (isset($data['automations']) && is_array($data['automations']) && isset($data['total'])) {
        // Recupera o total enviado
        $total = $data['total'];

        // Acessa a automação (deve ser um array com pelo menos um item)
        $automations = $data['automations'];

        // Verifica se há automações para processar
        if (count($automations) > 0) {
            // Acessa o ID do usuário da sessão
            if (isset($_SESSION['usuario_id'])) {
                $usuario_id = $_SESSION['usuario_id'];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Usuário não autenticado.'
                ];
                echo json_encode($response);
                exit();
            }

            // Buscar o token de acesso na tabela 'user_tokens' com base no 'usuario_id'
            $sql = "SELECT access_token FROM user_tokens WHERE client_id = :userId LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':userId', $usuario_id);
            $stmt->execute();

            // Verifica se o token foi encontrado
            if ($stmt->rowCount() > 0) {
                $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
                $accessToken = $tokenData['access_token'];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Token de acesso não encontrado para o usuário.'
                ];
                echo json_encode($response);
                exit();
            }

            // Agora que o total foi recebido, realiza o envio
            // Estrutura para enviar ao endpoint
            $postData = [
                "automations" => $automations
            ];

            // Inicializa cURL para enviar os dados
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://portal.keyx.com.br/api/bulk_automations/");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);

            // Executa a requisição
            $responseData = curl_exec($ch);

            // Verifica se houve erro na requisição cURL
            if (curl_errno($ch)) {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao enviar dados para o endpoint: ' . curl_error($ch)
                ];
            } else {
                // Atualiza o saldo no banco de dados
                $sql = "UPDATE usuarios SET saldo = saldo - :total WHERE id = :userId";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':total', $total);
                $stmt->bindParam(':userId', $usuario_id);
                $stmt->execute();

                // Inserir histórico de saldo com a retirada
                $sqlHistorico = "INSERT INTO historico_saldo (usuario_id, valor, tipo) 
    VALUES (:usuarioId, :valor, 'retirada')";
                $stmtHistorico = $pdo->prepare($sqlHistorico);
                $stmtHistorico->bindParam(':usuarioId', $usuario_id);
                $stmtHistorico->bindParam(':valor', $total);
                $stmtHistorico->execute();

                // Se a requisição foi bem-sucedida
                $response = [
                    'success' => true,
                    'message' => 'SMS enviados com sucesso!',
                    'data' => json_decode($responseData)
                ];
            }

            curl_close($ch);
        } else {
            $response = [
                'success' => false,
                'message' => 'Dados inválidos ou faltando.'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Método de requisição inválido.'
        ];
    }

    // Envia a resposta em JSON
    echo json_encode($response);
}
