<?php
// Conectar ao banco de dados
include '../config/config.php';

session_start();
$id_usuario = $_SESSION['usuario_id'];

// Consulta para selecionar as campanhas criadas pelo usuário logado
$sql = "SELECT id, status, data_criacao, numero_campanha, total_enviados, total_sucesso, total_falhas, enviado_para, saldo_total_gasto 
        FROM campanhas 
        WHERE id_usuario = :id_usuario";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
$stmt->execute();
$campanhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Campanhas</title>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 50px auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .btn-dashboard {
            display: inline-block;
            padding: 10px 20px;
            color: white;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-dashboard:hover {
            background-color: #394264;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
        }

        table th {
            background-color: #007bff;
            color: white;
            font-weight: 500;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        .btn-view {
            display: inline-block;
            padding: 8px 15px;
            color: white;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-view:hover {
            background-color: #394264;
        }

        .actions {
            text-align: center;
        }

        .empty {
            text-align: center;
            padding: 20px;
            font-size: 18px;
            color: #888;
        }
    </style>
</head>

<body>

    <div class="container">
        <div style="position: fixed; top: 10px; left: 10px; z-index: 1000;">
            <a href="index.php" title="Ir para a página inicial">
                <img src="https://i.ibb.co/p0cKr26/Captura-de-Tela-2024-12-13-a-s-08-54-39-removebg-preview.png"
                    alt="Start SMS"
                    style="max-width: 120px; height: auto;">
            </a>
        </div>

        <!-- Botão Voltar ao Dashboard -->
        <a href="../menu/index.php" class="btn-dashboard">← Voltar ao Dashboard</a>

        <h1>Minhas Campanhas</h1>

        <?php if (count($campanhas) > 0): ?>
            <!-- Adicionando scroll horizontal para a tabela -->
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Data de Criação</th>
                            <th>Número da Campanha</th>
                            <th>Total Enviados</th>
                            <th>Saldo Total Gasto</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campanhas as $campanha): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($campanha['id']); ?></td>
                                <td><?php echo htmlspecialchars($campanha['status']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($campanha['data_criacao'])); ?></td>
                                <td><?php echo htmlspecialchars($campanha['numero_campanha']); ?></td>
                                <td><?php echo htmlspecialchars($campanha['total_enviados']); ?></td>
                                <td><?php echo 'R$ ' . number_format($campanha['saldo_total_gasto'], 2, ',', '.'); ?></td>
                                <td class="actions">
                                    <a href="visualizar_envios.php?numero_campanha=<?php echo htmlspecialchars($campanha['numero_campanha']); ?>" class="btn-view">Visualizar Envios</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">Nenhuma campanha encontrada.</div>
        <?php endif; ?>
    </div>

</body>

</html>
