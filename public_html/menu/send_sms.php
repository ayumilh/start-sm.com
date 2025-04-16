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
    if (isset($data['phone_message_sending']) && is_array($data['phone_message_sending']) && isset($data['total'])) {
        // Recupera o total enviado
        $total = $data['total'];

        // Acessa a automação (deve ser um array com pelo menos um item)
        $phoneMessageSending = $data['phone_message_sending'];

        // Verifica se há automações para processar
        if (count($phoneMessageSending) > 0) {
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
                $accessToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTE5LCJpYXQiOjE3NDI5MzI3MTV9.ifM_9Jnhe7_dceMJiaWf4emy30D2jU8Lt2k3AKkrpso";
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Token de acesso não encontrado para o usuário.'
                ];
                echo json_encode($response);
                exit();
            }


            $postData = [
                "name" => "Automação de Envio de SMS",  // Nome da automação
                "phone_message_sending" => $phoneMessageSending  // Lista de mensagens personalizadas com número de telefone, mensagem e external_id
            ];

            // Inicializa cURL para enviar os dados
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://api.plugni.com.br/v1/sms/bulks");
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

                // Extraindo os dados da resposta
                $apiResponse = json_decode($responseData, true);

                $statusEnvio = $apiResponse['data']['payload']['status'] ?? 'IN_PROGRESS';
                $totalMessages = $apiResponse['data']['payload']['total_messages'] ?? count($phoneMessageSending);
                $nomeCampanha = $apiResponse['data']['payload']['name'] ?? 'Campanha SMS';
                $tipoEnvio = !empty($data['tipo']) ? strtolower(trim($data['tipo'])) : 'leve';

                // valida tipo
                $tiposValidos = ['leve', 'flex', 'turbo'];
                if (!in_array($tipoEnvio, $tiposValidos)) {
                    $tipoEnvio = 'leve';
                }
                
                // Garante que não divide por zero
                $valorUnitario = $total;

                // Inserção no banco com os campos existentes
                $sqlCampanha = "INSERT INTO campanhas_sms (
    usuario_id,
    tipo,
    total_enviados,
    status_envio,
    nome_campanha,
    valor_debitado
) VALUES (
    :usuario_id,
    :tipo,
    :total,
    :status_envio,
    :nome,
    :valor_unitario
)";
                $stmtCampanha = $pdo->prepare($sqlCampanha);
                $stmtCampanha->bindParam(':usuario_id', $usuario_id);
                $stmtCampanha->bindParam(':tipo', $tipoEnvio);
                $stmtCampanha->bindParam(':total', $totalMessages);
                $stmtCampanha->bindParam(':status_envio', $statusEnvio);
                $stmtCampanha->bindParam(':nome', $nomeCampanha);
                $stmtCampanha->bindParam(':valor_unitario', $valorUnitario);
                $stmtCampanha->execute();



                // Resposta final
                $response = [
                    'success' => true,
                    'message' => 'SMS enviados com sucesso!',
                    'data' => $apiResponse
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
