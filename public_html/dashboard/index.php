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

// Consulta as campanhas
$sql = "SELECT cs.*, u.nome AS usuario_nome 
        FROM campanhas_sms cs 
        JOIN usuarios u ON cs.usuario_id = u.id 
        ORDER BY cs.data_envio DESC";

$stmtCampanhas = $pdo->prepare($sql);
$stmtCampanhas->execute();
$campanhas = $stmtCampanhas->fetchAll(PDO::FETCH_ASSOC);
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
                    <!-- <li class="list-group-item"><a href="/admin/usuarios.php">👥 Gerenciar Usuários</a></li> -->
                    <!-- <li class="list-group-item"><a href="/admin/financeiro.php">💰 Ver Relatórios Financeiros</a></li> -->
                    <li class="list-group-item">
                        <a href="#" onclick="mostrarCampanhas(); return false;">📊 Análise de Campanhas</a>
                    </li>

                    <!-- <li class="list-group-item"><a href="/admin/mensagens.php">📩 Editar Mensagens</a></li> -->
                </ul>
                <a href="/menu/" class="btn btn-primary">Voltar ao Menu Principal</a>
            </div>
        </div>
        <div id="tabelaCampanhas" style="display: none;">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-3">📊 Campanhas de SMS</h5>
                    <?php if (count($campanhas) > 0): ?>
                        <div class="table-responsive">
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
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


    <script>
        function mostrarCampanhas() {
            const tabela = document.getElementById("tabelaCampanhas");
            if (tabela.style.display === "none") {
                tabela.style.display = "block";
                window.scrollTo({
                    top: tabela.offsetTop - 40,
                    behavior: "smooth"
                });
            } else {
                tabela.style.display = "none";
            }
        }
    </script>

</body>

</html>