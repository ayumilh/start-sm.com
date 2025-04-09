let totalAmount = 0;
let requestNumber = null;

window.onload = function () {
  document.getElementById('btnPagar').addEventListener('click', gerarQrcode);
};


function calculateTotal() {
  // Obtém o valor do input de quantidade de SMS
  let quantity = parseFloat(document.getElementById("smsQuantityAdd").value);

  // Verifica se o valor inserido é válido (número e maior que 10)
  if (isNaN(quantity) || quantity < 10) {
    document.getElementById("totalAmount").innerText = "0,00";
    totalAmount = 0;
    return;
  }

  let pricePerSMS = 0;

  // Define o preço por SMS conforme a quantidade inserida
  if (quantity >= 10 && quantity < 500000) {
    pricePerSMS = 0.12; // Preço para SMS entre 10 e 499,999
  } else if (quantity >= 500000) {
    pricePerSMS = 0.11; // Preço para SMS acima de 500,000
  }

  // Calcula o total
  totalAmount = quantity * pricePerSMS;

  // Adiciona a taxa de R$1,00
  totalAmount += 1; // Taxa fixa de R$1,00

  // Atualiza o valor exibido no modal
  document.getElementById("totalAmount").innerText = totalAmount.toFixed(2).replace(".", ",");
}

function sendTransactionRequest() {
  var requestData = {
    requestNumber: document.getElementById('requestNumber').innerText,
    dueDate: document.getElementById('dueDate').innerText,
    totalAmount: document.getElementById('totalAmountTransaction').innerText,
    clientAddress: document.getElementById('clientAddress').innerText
  };

  // Envia a requisição para o PHP usando AJAX
  var xhr = new XMLHttpRequest();
  xhr.setRequestHeader('Content-Type', 'application/json');

  xhr.onreadystatechange = function () {
    if (xhr.readyState == 4 && xhr.status == 200) {
      var response = JSON.parse(xhr.responseText);

      if (response.status === 'success') {
        console.log('Pagamento efetuado com sucesso:', response);
      } else {
        alert('Erro no pagamento: ' + response.message);
      }
    }
  };

  xhr.send(JSON.stringify(requestData));
}

function confirmPurchase() {
  const dueDate = getDueDateSameDay();

  document.getElementById('requestNumber').textContent = requestNumber; // Exemplo de número de solicitação
  document.getElementById('dueDate').textContent = dueDate; // Exemplo de data de vencimento
  document.getElementById('totalAmountTransaction').textContent = totalAmount.toFixed(2).replace(".", ","); // Passando o total calculado para o modal

  document.getElementById('saldoContent').style.display = 'none';

  document.getElementById('transactionModal').style.display = 'flex';
}

function getDueDateSameDay() {
  const now = new Date(); // Obtém a data e hora atual

  // Obtém o ano, mês (adiciona +1 porque em JavaScript os meses começam do 0) e dia
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0'); // Adiciona zero à esquerda se necessário
  const day = String(now.getDate()).padStart(2, '0'); // Adiciona zero à esquerda se necessário

  // Retorna no formato YYYY-MM-DD
  return `${year}-${month}-${day}`;
}

async function getUserData() {
  try {
    // Envia uma requisição AJAX para obter os dados do usuário
    const response = await fetch('getUserData.php', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    });

    const userData = await response.json();

    if (userData.status === 'success') {
      return userData.user; // Retorna os dados do usuário
    } else {
      console.error('Erro ao obter dados do usuário:', userData.message);
      return null;
    }
  } catch (error) {
    console.error('Erro na requisição para obter dados do usuário:', error);
    return null;
  }
}

async function gerarQrcode() {
  const apiUrl = "https://ws.suitpay.app/api/v1/gateway/request-qrcode";
  
  const dueDate = getDueDateSameDay();
  requestNumber = Date.now();
  const user = await getUserData();

  if (!user) {
    alert('Erro: Não foi possível obter os dados do usuário.');
    return;
  }

    // Verifique se o ID está presente
    if (!user.id) {
      alert('Erro: O ID do usuário não foi encontrado.');
      return;
    }

  const data = {
    requestNumber: requestNumber,
    dueDate: dueDate,
    amount: parseFloat(totalAmount), // Valor com a taxa de R$1,00
    callbackUrl: "https://start-sms.com/menu/webhook.php",
    client: {
      name: user.nome, // Substituir pelo nome do usuário
      document: user.documento, // Substituir pelo documento do usuário
    },
  };

  try {
    const response = await fetch(apiUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "ci": "tutuads_1718106153223",
        "cs": "0f0a1cd4d486e1153d84343dfe38f48a2597177b505f9a56b97a3b115115644491c48a1cb33648f5953dc17699aa3352"
      },
      body: JSON.stringify(data)
    });

    const jsonResponse = await response.json();

    if (jsonResponse.response === "OK") {
      document.getElementById("paymentCode").innerText = jsonResponse.paymentCode;
      document.getElementById("requestNumber").innerText = data.requestNumber;
      document.getElementById("dueDate").innerText = data.dueDate;

      if (jsonResponse.paymentCodeBase64) {
        let imgElement = document.createElement("img");
        imgElement.src = "data:image/png;base64," + jsonResponse.paymentCodeBase64;
        imgElement.alt = "QR Code de pagamento";
        document.getElementById("qrcodeContainer").innerHTML = "";
        document.getElementById("qrcodeContainer").appendChild(imgElement);
      }

      // Enviar os dados para o banco de dados via PHP
      const paymentData = {
        requestNumber: data.requestNumber,
        dueDate: data.dueDate,
        totalAmount: data.amount,
        paymentCode: jsonResponse.paymentCode,
        paymentCodeBase64: jsonResponse.paymentCodeBase64,
        clientName: user.nome,
        clientDocument: user.documento,
        clientId: user.id,
      };
      console.log("Dados do pagamento:", paymentData);

      // Envia os dados via AJAX para o PHP
      const insertResponse = await fetch('insertPayment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(paymentData)
      });

      const responseText = await insertResponse.text();
      console.log("Resposta do PHP:", responseText);

      try {
        const result = JSON.parse(responseText);
        if (result.status === 'success') {
          console.log("Pagamento registrado no banco de dados.");
          if (document.getElementById("pixTitle")) {
            document.getElementById("pixTitle").style.display = "block";
          }

          if (document.getElementById("qrcodeContainer")) {
            document.getElementById("qrcodeContainer").style.display = "flex";
          }

          if (document.getElementById("copyContainer")) {
            document.getElementById("copyContainer").style.display = "flex";
          }

          if (document.getElementById("paymentCodeContainer")) {
            document.getElementById("paymentCodeContainer").style.display = "flex";
          }

        } else {
          console.error("Erro ao registrar o pagamento no banco." + result.message);
        }
      } catch (error) {
        console.error("Erro ao processar a resposta:", error);
      }
    } else {
      alert("Erro ao gerar QR Code: " + jsonResponse.message);
    }
  } catch (error) {
    console.error("Erro na requisição:", error);
    alert("Erro na requisição para gerar QR Code.");
  }
}

function copyPaymentCode() {
  const paymentCode = document.getElementById('paymentCode').innerText;

  // Cria um campo temporário para copiar o código
  const tempInput = document.createElement('input');
  tempInput.value = paymentCode;
  document.body.appendChild(tempInput);

  // Seleciona e copia o conteúdo do campo temporário
  tempInput.select();
  document.execCommand('copy');

  // Remove o campo temporário
  document.body.removeChild(tempInput);

  // Exibe um feedback para o usuário
  alert('Código Pix copiado para a área de transferência!');
}
