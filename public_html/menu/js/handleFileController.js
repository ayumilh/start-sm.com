function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function validarEnvioModal(tipoDisparo) {
    const inputMensagem = document.getElementById(`sms${capitalize(tipoDisparo)}Message`);
    const botao = document.getElementById(`btnEnviar${capitalize(tipoDisparo)}`);

    if (!inputMensagem || !botao) return;

    const temMensagem = inputMensagem.value.trim().length > 0;
    const temNumeros = window.numbersWithNames && window.numbersWithNames.length >= 10;

    botao.disabled = !(temMensagem && temNumeros);
}

// Formata valor como moeda brasileira
function formatCurrency(value) {
    return value.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

// Função genérica para processar arquivos
function handleFileByType(tipo, precoPorSms) {
    const fileInput = document.getElementById(`${tipo}File`);
    const file = fileInput.files[0];

    if (!file) return;

    processFile(file)
        .then((numbers) => {
            const uniqueNumbers = [...new Set(numbers.map(r => r.t))];

            if (uniqueNumbers.length < 10) {
                alert("O arquivo deve conter pelo menos 10 números.");
                atualizarStatusArquivo(tipo, "Arquivo inválido", "red");
                fileInput.value = '';
                return;
            }

            const quantity = uniqueNumbers.length;
            const total = quantity * precoPorSms;
            const saldoTexto = document.getElementById("userBalance").innerText;
            const userBalance = parseFloat(saldoTexto.replace("R$", "").replace(/\./g, '').replace(",", "."));

            document.getElementById(`${tipo}QuantityText`).innerText = `Quantidade de SMS: ${quantity}`;
            document.getElementById(`${tipo}Total`).innerText = formatCurrency(total);

            if (userBalance < total) {
                document.getElementById(`${tipo}SaldoRestante`).innerText = "Saldo insuficiente";
                alert("Saldo insuficiente para enviar.");
                fileInput.value = '';
                return;
            }

            const novoSaldo = userBalance - total;
            document.getElementById(`${tipo}SaldoRestante`).innerText = formatCurrency(novoSaldo);
            atualizarStatusArquivo(tipo, "Arquivo carregado", "green");

            window.numbersToSend = uniqueNumbers;
            window.numbersWithNames = numbers;

            validarEnvioModal(tipo);
        })
        .catch(error => alert("Erro ao processar o arquivo: " + error));
}

// Atualiza o status do arquivo (texto + cor)
function atualizarStatusArquivo(tipo, mensagem, cor) {
    const statusEl = document.getElementById(`${tipo}FileStatus`);
    statusEl.innerText = mensagem;
    statusEl.style.color = cor;
}

// Funções específicas que usam o handler genérico
function handleFlexFile() {
    handleFileByType("flex", 0.09);
}

function handleTurboFile() {
    handleFileByType("turbo", 0.14);
}

function handleLeveFile() {
    handleFileByType("leve", 0.08);
}
