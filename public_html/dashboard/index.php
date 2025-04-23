<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login/");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare('SELECT nome, nivel_acesso FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['nivel_acesso'] !== 'admin') {
    echo "<h1 style='color:red; text-align:center; margin-top: 50px;'>🚫 Acesso Negado</h1>";
    echo "<p style='text-align:center;'>Essa página é restrita aos administradores.</p>";
    echo "<div style='text-align:center; margin-top:20px;'>
            <a href='/menu' style='background:#007bff; padding:10px 20px; color:#fff; text-decoration:none; border-radius:5px;'>Voltar</a>
          </div>";
    exit;
}

$nome = htmlspecialchars($usuario['nome']);


// ==========================
// Consulta 1: campanhas_sms
// ==========================
$sqlCampanhas = "SELECT cs.*, u.nome AS usuario_nome 
                 FROM campanhas_sms cs 
                 JOIN usuarios u ON cs.usuario_id = u.id 
                 ORDER BY cs.data_envio DESC";

$stmtCampanhas = $pdo->prepare($sqlCampanhas);
$stmtCampanhas->execute();
$campanhas = $stmtCampanhas->fetchAll(PDO::FETCH_ASSOC);

// =======================================
// Consulta 2: envios_sms_campanha (nova)
// =======================================
$sqlEnvios = "SELECT esc.*, u.nome AS usuario_nome
              FROM envios_sms_campanha esc
              JOIN usuarios u ON esc.usuario_id = u.id
              ORDER BY esc.criado_em DESC";

$stmtEnvios = $pdo->prepare($sqlEnvios);
$stmtEnvios->execute();
$campanhasEnvio = $stmtEnvios->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Administrador</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table th,
        .table td {
            vertical-align: middle;
        }

        .badge-leve {
            background-color: #28a745;
        }

        .badge-flex {
            background-color: #ffc107;
        }

        .badge-turbo {
            background-color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="text-center mb-4">
            <h1 class="display-4">Painel do Administrador</h1>
            <p class="lead">Bem-vindo, <strong><?php echo $nome; ?></strong></p>
        </div>

        <div class="card shadow mb-5">
            <div class="card-body">
                <h5 class="card-title">Funções disponíveis</h5>
                <ul class="list-group mb-3">
                    <li class="list-group-item">
                        <a href="#" id="btnCampanhas">📊 Disparo de SMS</a>
                    </li>
                    <li class="list-group-item">
                        <a href="#" id="btnEnvios">📨 Envio de Campanhas</a>
                    </li>
                </ul>
                <a href="/menu/" class="btn btn-primary">Voltar ao Menu Principal</a>
            </div>
        </div>

        <div id="tabelaCampanhas" style="display: none; margin-bottom: 40px;">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-3">📊 Campanhas de SMS</h5>
                    <?php if (count($campanhas) > 0): ?>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Tipo</th>
                                        <th>Nome</th>
                                        <!-- <th>Status</th> -->
                                        <th>Total</th>
                                        <th>Valor (R$)</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campanhas as $camp): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($camp['usuario_nome']) ?></td>
                                            <td><span class="badge badge-<?= $camp['tipo'] ?>"><?= ucfirst($camp['tipo']) ?></span></td>
                                            <td><?= htmlspecialchars($camp['nome_campanha']) ?></td>
                                            <!-- <td>
                                            <?= strtoupper($camp['status_envio']) ?>
                                        </td> -->
                                            <td><?= (int)$camp['total_enviados'] ?></td>
                                            <td>R$ <?= number_format($camp['valor_debitado'], 2, ',', '.') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($camp['data_envio'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma campanha encontrada.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="tabelaEnvios" style="display: none; margin-bottom: 40px;">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-3">📨 Envios Registrados</h5>
                    <?php if (count($campanhasEnvio) > 0): ?>
                        <div class="table-responsive" style="max-height: 800px; overflow-y: auto;">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuário</th>
                                        <th>Data</th>
                                        <th>Total Enviados</th>
                                        <th>Mensagem</th>
                                        <th>Números</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campanhasEnvio as $envio): ?>
                                        <tr>
                                            <td><?= (int) $envio['id'] ?></td>
                                            <td><?= htmlspecialchars($envio['usuario_nome']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($envio['criado_em'])) ?></td>
                                            <td><?= count(json_decode($envio['enviados'] ?? '[]')) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="mostrarTexto(`<?= htmlspecialchars($envio['mensagem_escolhida'], ENT_QUOTES) ?>`)">
                                                    Ver
                                                </button>
                                            </td>
                                            <td>
                                                <?php
                                                $numeros = $envio['numeros'];
                                                $fileDir = __DIR__ . '/tmp/';
                                                $fileName = 'numeros_envio_' . $envio['id'] . '.txt';
                                                $filePath = $fileDir . $fileName;

                                                // Garante que o diretório existe
                                                if (!file_exists($fileDir)) {
                                                    mkdir($fileDir, 0777, true);
                                                }

                                                // Salva o arquivo
                                                file_put_contents($filePath, $numeros);
                                                ?>
                                                <a href="tmp/<?= $fileName ?>" class="btn btn-sm btn-outline-secondary" download>
                                                    📥 Baixar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhum envio encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal -->
    <div id="modalTexto" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Conteúdo Completo</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <pre id="textoCompleto" class="text-wrap text-break"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


    <script src="js/tableController.js"></script>


</body>

</html>