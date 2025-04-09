<?php
session_start();
require '../config/config.php'; // Ajuste o caminho conforme necessário

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login/");
    exit;
}

// Lista de estados brasileiros e seus DDDs
$estados = [
    'Acre' => ['68'],
    'Alagoas' => ['82'],
    'Amapá' => ['96'],
    'Amazonas' => ['92', '97'],
    'Bahia' => ['71', '73', '74', '75', '77'],
    'Ceará' => ['85', '88'],
    'Distrito Federal' => ['61'],
    'Espírito Santo' => ['27', '28'],
    'Goiás' => ['62', '64'],
    'Maranhão' => ['98', '99'],
    'Mato Grosso' => ['65', '66'],
    'Mato Grosso do Sul' => ['67'],
    'Minas Gerais' => ['31', '32', '33', '34', '35', '37', '38'],
    'Pará' => ['91', '93', '94'],
    'Paraíba' => ['83'],
    'Paraná' => ['41', '42', '43', '44', '45', '46'],
    'Pernambuco' => ['81', '87'],
    'Piauí' => ['86', '89'],
    'Rio de Janeiro' => ['21', '22', '24'],
    'Rio Grande do Norte' => ['84'],
    'Rio Grande do Sul' => ['51', '53', '54', '55'],
    'Rondônia' => ['69'],
    'Roraima' => ['95'],
    'Santa Catarina' => ['47', '48', '49'],
    'São Paulo' => ['11', '12', '13', '14', '15', '16', '17', '18', '19'],
    'Sergipe' => ['79'],
    'Tocantins' => ['63']
];

/**
 * Função para gerar números de telefone válidos para um estado específico.
 * 
 * Regras:
 * - DDD: um dos disponíveis para o estado (selecionado aleatoriamente)
 * - Número: 9 dígitos, onde:
 *    - o primeiro dígito é fixo em 9,
 *    - o segundo dígito é escolhido entre 7, 8 ou 9,
 *    - os 7 dígitos seguintes são gerados aleatoriamente.
 * 
 * O número gerado será composto por: DDD + 9 dígitos (total de 11 dígitos).
 *
 * @param string $estado Nome do estado
 * @param int $quantidade Quantidade de números a serem gerados (padrão: 10000)
 * @return array|null Retorna um array com os números gerados ou null se o estado não estiver mapeado
 */
function gerar_telefones_estado($estado, $quantidade = 10000) {
    global $estados;
    // Para todos os estados, o segundo dígito permitido é apenas 7, 8 e 9.
    $segundo_digitos_map = [
         'Acre' => [7,8,9],
         'Alagoas' => [7,8,9],
         'Amapá' => [7,8,9],
         'Amazonas' => [7,8,9],
         'Bahia' => [7,8,9],
         'Ceará' => [7,8,9],
         'Distrito Federal' => [7,8,9],
         'Espírito Santo' => [7,8,9],
         'Goiás' => [7,8,9],
         'Maranhão' => [7,8,9],
         'Mato Grosso' => [7,8,9],
         'Mato Grosso do Sul' => [7,8,9],
         'Minas Gerais' => [7,8,9],
         'Pará' => [7,8,9],
         'Paraíba' => [7,8,9],
         'Paraná' => [7,8,9],
         'Pernambuco' => [7,8,9],
         'Piauí' => [7,8,9],
         'Rio de Janeiro' => [7,8,9],
         'Rio Grande do Norte' => [7,8,9],
         'Rio Grande do Sul' => [7,8,9],
         'Rondônia' => [7,8,9],
         'Roraima' => [7,8,9],
         'Santa Catarina' => [7,8,9],
         'São Paulo' => [7,8,9],
         'Sergipe' => [7,8,9],
         'Tocantins' => [7,8,9]
    ];

    if (!isset($estados[$estado]) || !isset($segundo_digitos_map[$estado])) {
         return null;
    }
    $ddd_array = $estados[$estado];
    $allowed_segundo = $segundo_digitos_map[$estado];
    $telefones = [];
    for ($i = 0; $i < $quantidade; $i++) {
         $ddd = $ddd_array[array_rand($ddd_array)]; // Seleciona um DDD dentre os disponíveis
         $primeiro_digito = 9;
         $segundo_digito = $allowed_segundo[array_rand($allowed_segundo)];
         $restante = "";
         // Gera 7 dígitos aleatórios para completar os 9 dígitos do número
         for ($j = 0; $j < 7; $j++) {
              $restante .= mt_rand(0, 9);
         }
         // Monta o número: DDD (2 dígitos) + 9 (fixo) + dígito restrito + 7 dígitos aleatórios = 11 dígitos
         $numero = "{$ddd}{$primeiro_digito}{$segundo_digito}{$restante}";
         $telefones[] = $numero;
    }
    return $telefones;
}

/**
 * Função para gerar números para "Todos os Estados" utilizando apenas os principais DDDs.
 * Para cada estado, utiliza-se o primeiro DDD da lista e a quantidade total é distribuída
 * igualmente entre os estados.
 *
 * @param int $quantidade Quantidade total de números a serem gerados
 * @return array Array com os números gerados para todos os estados
 */
function gerar_telefones_todos($quantidade = 10000) {
    global $estados;
    $num_states = count($estados);
    $base = floor($quantidade / $num_states);
    $remainder = $quantidade % $num_states;
    $telefones = [];
    $allowed_segundo = [7,8,9];
    foreach ($estados as $estado => $ddd_array) {
         // Utiliza o DDD principal (primeiro elemento)
         $ddd = $ddd_array[0];
         $count_for_this_state = $base;
         if ($remainder > 0) {
              $count_for_this_state++;
              $remainder--;
         }
         for ($i = 0; $i < $count_for_this_state; $i++) {
              $primeiro_digito = 9;
              $segundo_digito = $allowed_segundo[array_rand($allowed_segundo)];
              $restante = "";
              for ($j = 0; $j < 7; $j++) {
                   $restante .= mt_rand(0, 9);
              }
              $numero = "{$ddd}{$primeiro_digito}{$segundo_digito}{$restante}";
              $telefones[] = $numero;
         }
    }
    return $telefones;
}

// Processamento da geração dos números quando o usuário confirma a compra
if (isset($_GET['gerar']) && $_GET['gerar'] == '1') {
    $estado = $_GET['estado'] ?? '';
    $quantidade = isset($_GET['quantidade']) ? intval($_GET['quantidade']) : 10000;
    
    if ($estado === 'Todos os Estados') {
        $telefones = gerar_telefones_todos($quantidade);
    } else {
        $telefones = gerar_telefones_estado($estado, $quantidade);
        if ($telefones === null) {
            echo "Geração de números não implementada para o estado: " . htmlspecialchars($estado);
            exit;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Telefones Gerados para <?php echo htmlspecialchars($estado); ?></title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                background-color: #f8f9fa;
            }
            .telefones { 
                max-height: 400px; 
                overflow-y: scroll; 
                border: 1px solid #ccc; 
                padding: 10px; 
                background: #f4f4f4;
                text-align: left;
            }
            button.copy-btn {
                display: block;
                background-color: #0056b3;
                color: white;
                font-size: 16px;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin: 10px auto;
            }
            button.copy-btn:hover {
                background-color: #003d82;
            }
            a.voltar {
                display: inline-block;
                margin-top: 20px;
                text-decoration: none;
                color: #0056b3;
                font-size: 16px;
            }
            /* Notificação estilo padrão */
            #notification {
                display: none;
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #0056b3;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                font-size: 16px;
                z-index: 1001;
            }
        </style>
    </head>
    <body>
        <!-- Notificação -->
        <div id="notification"></div>
        
        <h1>Telefones Gerados para <?php echo htmlspecialchars($estado); ?></h1>
        <p>Quantidade: <?php echo $quantidade; ?></p>
        <div class="telefones" id="telefones">
            <?php
            foreach ($telefones as $telefone) {
                echo htmlspecialchars($telefone) . "<br>";
            }
            ?>
        </div>
        <br>
        <button class="copy-btn" onclick="copiarNumeros()">Copiar Números</button>
        <br>
        <a class="voltar" href="<?php echo $_SERVER['PHP_SELF']; ?>">Voltar</a>
        <script>
          function copiarNumeros() {
              var conteudo = document.getElementById("telefones").innerText;
              navigator.clipboard.writeText(conteudo).then(function() {
                var notification = document.getElementById("notification");
                notification.innerText = "Números copiados para a área de transferência!";
                notification.style.display = "block";
                setTimeout(function() {
                    notification.style.display = "none";
                }, 3000);
              }, function(err) {
                var notification = document.getElementById("notification");
                notification.innerText = "Erro ao copiar: " + err;
                notification.style.display = "block";
                setTimeout(function() {
                    notification.style.display = "none";
                }, 3000);
              });
          }
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerador de Telefone</title>
  <link rel="icon" href="image/sms.png" type="image/png">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
      text-align: center;
    }
    header {
      background-color: #f8f9fa;
      /* border-bottom: 5px solid #0056b3; */
      padding: 20px;
    }
    .container {
      padding: 20px;
    }
    .btn-estados {
      display: block;
      background-color: #0056b3;
      color: white;
      font-size: 20px;
      font-weight: bold;
      padding: 15px;
      margin: 20px auto;
      width: 80%;
      border-radius: 10px;
      text-decoration: none;
      transition: background 0.3s;
    }
    .btn-estados:hover {
      background-color: #003d82;
    }
    .menu {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 20px;
      max-width: 900px;
      margin: 0 auto;
    }
    .menu-item {
      background-color: #ffffff;
      padding: 20px;
      border: 1px solid #ddd;
      border-radius: 10px;
      text-align: center;
      transition: transform 0.3s;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      cursor: pointer;
    }
    .menu-item:hover {
      transform: scale(1.05);
      background-color: #e6f4ea;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      width: 350px;
      position: relative;
    }
    .close {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 20px;
      cursor: pointer;
    }
    .confirm-btn {
      background-color: #0056b3;
      color: white;
      font-size: 18px;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 20px;
    }
    input[type="text"] {
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      text-align: center;
      width: 100px;
      padding: 5px;
      margin: 0 10px;
    }
    button {
      background-color: #0056b3;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
      padding: 5px 10px;
      font-size: 16px;
    }
    button:hover {
      background-color: #003d82;
    }
    .subtitulo {
      font-size: 18px;
      color: #333;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <header>
    <h1>Gerador de Telefone</h1>
    <p class="subtitulo">Gere 10.000 Leads Qualificados por R$ 15,00 - Números Separados por Estado!</p>
  </header>
  <div class="container">
    <a href="#" class="btn-estados" onclick="abrirModal('Todos os Estados')">Todos os Estados</a>
    <div class="menu">
      <?php foreach ($estados as $estado => $ddds): ?>
        <div class="menu-item" onclick="abrirModal('<?php echo $estado; ?>')">
          <span><?php echo $estado; ?> (<?php echo implode(', ', $ddds); ?>)</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div id="modal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="fecharModal()">&times;</span>
      <p id="modal-text"></p>
      <div>
        <label for="quantidade">Quantidade de Leads:</label>
        <div style="display: flex; align-items: center; justify-content: center; margin-top: 10px;">
          <button onclick="alterarQuantidade(-10000)">-</button>
          <input type="text" id="quantidade" value="10000" oninput="validarQuantidade(this)">
          <button onclick="alterarQuantidade(10000)">+</button>
        </div>
      </div>
      <p id="valor-total" style="margin-top: 20px;">Valor Total: R$ 15,00</p>
      <button class="confirm-btn" onclick="confirmarCompra()">Confirmar</button>
    </div>
  </div>
  
  <script>
    let estadoSelecionado = "";
    let quantidade = 10000;

    function calcularValorTotal(quantidade) {
      let valorPorLead;
      if (quantidade >= 1000000) {
        valorPorLead = 11.00;
      } else if (quantidade >= 100000) {
        valorPorLead = 14.00;
      } else {
        valorPorLead = 15.00;
      }
      return (quantidade / 10000) * valorPorLead;
    }

    function atualizarValorTotal() {
      const valorTotal = calcularValorTotal(quantidade);
      document.getElementById('valor-total').innerText = `Valor Total: R$ ${valorTotal.toFixed(2)}`;
    }

    function alterarQuantidade(delta) {
      const novaQuantidade = quantidade + delta;
      if (novaQuantidade >= 10000) {
        quantidade = novaQuantidade;
        document.getElementById('quantidade').value = quantidade;
        atualizarValorTotal();
      }
    }

    function validarQuantidade(input) {
      let valor = parseInt(input.value.replace(/\D/g, ''), 10);
      if (isNaN(valor) || valor < 10000) {
        valor = 10000;
      }
      valor = Math.round(valor / 10000) * 10000;
      input.value = valor;
      quantidade = valor;
      atualizarValorTotal();
    }

    function abrirModal(estado) {
      estadoSelecionado = estado;
      quantidade = 10000; // Resetar para o valor inicial
      document.getElementById("quantidade").value = quantidade;
      document.getElementById("modal-text").innerText = `Será gerado ${quantidade} leads do ${estado}`;
      atualizarValorTotal();
      document.getElementById("modal").style.display = "flex";
    }

    function fecharModal() {
      document.getElementById("modal").style.display = "none";
    }

    function confirmarCompra() {
      // Encaminha para o script PHP para geração dos números
      window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?gerar=1&estado=' + encodeURIComponent(estadoSelecionado) + '&quantidade=' + quantidade;
    }
  </script>
</body>
</html>
