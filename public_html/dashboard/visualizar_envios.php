<?php
session_start();
require '../config/config.php'; // Ajuste o caminho conforme necessário

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login/"); // Redireciona se não estiver logado
    exit;
}

// Pega o ID do usuário da sessão
$usuario_id = $_SESSION['usuario_id'];

// Verifica se o numero_campanha foi passado
if (!isset($_GET['numero_campanha'])) {
    header("Location: campanhas.php");
    exit;
}

$numero_campanha = $_GET['numero_campanha'];

// Consulta para verificar se o numero_campanha pertence ao usuário (verifica a campanha)
$stmt = $pdo->prepare('SELECT id FROM campanhas WHERE numero_campanha = ? AND id_usuario = ?');
$stmt->execute([$numero_campanha, $usuario_id]);
$campanha = $stmt->fetch(PDO::FETCH_ASSOC);

// Se a campanha não for encontrada ou não pertencer ao usuário, redireciona
if (!$campanha) {
    header("Location: campanhas.php");
    exit;
}

// Recebe os parâmetros do filtro (se existirem)
$numero_destinatario = $_GET['numero_destinatario'] ?? '';
$status_envio = $_GET['status_envio'] ?? '';
$mensagem = $_GET['mensagem'] ?? '';
$data_hora_envio = $_GET['data_hora_envio'] ?? '';

// Monta a consulta SQL com os filtros
$query = 'SELECT numero_destinatario, status_envio, mensagem, data_hora_envio FROM envios WHERE numero_campanha = ?';
$params = [$numero_campanha];

// Aplica filtros se estiverem preenchidos
if (!empty($numero_destinatario)) {
    $query .= ' AND numero_destinatario LIKE ?';
    $params[] = '%' . $numero_destinatario . '%';
}

if (!empty($status_envio)) {
    $query .= ' AND status_envio LIKE ?';
    $params[] = '%' . $status_envio . '%';
}

if (!empty($mensagem)) {
    $query .= ' AND mensagem LIKE ?';
    $params[] = '%' . $mensagem . '%';
}

if (!empty($data_hora_envio)) {
    $query .= ' AND DATE(data_hora_envio) = ?';
    $params[] = $data_hora_envio;
}

$query .= ' ORDER BY data_hora_envio DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$envios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envios - <?php echo htmlspecialchars($numero_campanha); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div style="position: fixed; top: 10px; left: 50%; transform: translateX(-50%); z-index: 1000;">
    <a href="index.php" title="Ir para a página inicial">
        <img src="https://i.ibb.co/p0cKr26/Captura-de-Tela-2024-12-13-a-s-08-54-39-removebg-preview.png" 
             alt="Start SMS" 
             style="max-width: 120px; height: auto;">
    </a>
</div>

    <!-- Barra Superior -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Envios da Campanha: <?php echo htmlspecialchars($numero_campanha); ?></a>
        <div class="ml-auto">
            <a href="campanhas.php" class="btn btn-primary">Voltar às Campanhas</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Envios da Campanha</h2>

        <!-- Formulário de Filtro -->
        <form method="GET" action="">
            <input type="hidden" name="numero_campanha" value="<?php echo htmlspecialchars($numero_campanha); ?>">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="numero_destinatario">Número do Destinatário</label>
                    <input type="text" class="form-control" id="numero_destinatario" name="numero_destinatario" value="<?php echo htmlspecialchars($numero_destinatario); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label for="status_envio">Status</label>
                    <input type="text" class="form-control" id="status_envio" name="status_envio" value="<?php echo htmlspecialchars($status_envio); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="mensagem">Mensagem</label>
                    <input type="text" class="form-control" id="mensagem" name="mensagem" value="<?php echo htmlspecialchars($mensagem); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="data_hora_envio">Data de Envio</label>
                    <input type="date" class="form-control" id="data_hora_envio" name="data_hora_envio" value="<?php echo htmlspecialchars($data_hora_envio); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>

        <div class="table-responsive mt-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Número de Destinatário</th>
                        <th>Mensagem Enviada</th>
                        <th>Data e Hora do Envio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($envios) > 0): ?>
                        <?php foreach ($envios as $envio): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($envio['numero_destinatario']); ?></td>
                                <td><?php echo htmlspecialchars($envio['mensagem']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($envio['data_hora_envio'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Nenhum envio encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS, Popper.js, jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
