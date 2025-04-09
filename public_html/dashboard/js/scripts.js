document.getElementById('iniciar-campanha').addEventListener('click', function() {
    const btn = document.getElementById('iniciar-campanha');
    const progresso = document.getElementById('progresso-campanha');
    const progressBar = document.querySelector('.progress-bar');

    // Desabilitar o botão por 5 segundos
    btn.disabled = true;
    setTimeout(() => btn.disabled = false, 5000);

    // Mudar o texto e a cor do botão
    if (btn.textContent.includes('Iniciar')) {
        btn.textContent = 'Pausar Campanha';
        btn.classList.add('btn-danger'); // Mudar cor para vermelho
        progresso.style.display = 'block';

        // Simular progresso
        let progressValue = 0;
        const interval = setInterval(() => {
            if (progressValue >= 100) {
                clearInterval(interval);
                progressBar.style.width = '100%';
                progressBar.classList.add('bg-success'); // Mudar a barra para verde
                progressBar.textContent = 'Envio feito com sucesso';
            } else {
                progressValue += 10;
                progressBar.style.width = `${progressValue}%`;
                progressBar.setAttribute('aria-valuenow', progressValue);
                progressBar.textContent = `${progressValue}%`;
            }
        }, 500);
    } else {
        btn.textContent = 'Iniciar Campanha e Enviar SMS';
        btn.classList.remove('btn-danger');
        progresso.style.display = 'none';
    }
});

// Toggle entre mostrar/esconder enviados e não enviados
document.getElementById('toggle-enviados').addEventListener('click', function() {
    const enviados = document.getElementById('sms-enviados');
    enviados.style.display = enviados.style.display === 'none' ? 'block' : 'none';
});

document.getElementById('toggle-nao-enviados').addEventListener('click', function() {
    const naoEnviados = document.getElementById('sms-nao-enviados');
    naoEnviados.style.display = naoEnviados.style.display === 'none' ? 'block' : 'none';
});
