document.getElementById('registro-link').addEventListener('click', function() {
    document.getElementById('login-form').style.display = 'none';
    document.getElementById('registro-form').style.display = 'block';
});

document.getElementById('login-link').addEventListener('click', function() {
    document.getElementById('login-form').style.display = 'block';
    document.getElementById('registro-form').style.display = 'none';
});

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const action = urlParams.get('action');

    // Lógica para lidar com mensagens de erro
    if (message === 'error') {
        Swal.fire({
            title: 'Erro!',
            text: action === 'register' ? 'Erro ao registrar o usuário.' : 'Email ou senha incorretos!',
            icon: 'error',
            showConfirmButton: false,
            timer: 3000 // Tempo para exibir mensagem de erro
        });
    }

    // Lógica para exibir a mensagem de sucesso com atraso no redirecionamento
    if (message === 'success' && action === 'register') {
        Swal.fire({
            title: 'Sucesso!',
            text: 'Registro realizado com sucesso. Redirecionando para a dashboard...',
            icon: 'success',
            showConfirmButton: false
        });

        // Adiciona um atraso de 3 segundos antes do redirecionamento para a dashboard
        setTimeout(function() {
            window.location.href = '../dashboard/'; // Redireciona para a dashboard após 3 segundos
        }, 3000); // Atraso de 3 segundos para o redirecionamento
    }

    if (message === 'success' && action === 'login') {
        Swal.fire({
            title: 'Sucesso!',
            text: 'Login realizado com sucesso. Redirecionando para a dashboard...',
            icon: 'success',
            showConfirmButton: false
        });

        // Adiciona um atraso de 3 segundos antes do redirecionamento para a dashboard
        setTimeout(function() {
            window.location.href = '../dashboard/'; // Redireciona para a dashboard após 3 segundos
        }, 3000); // Atraso de 3 segundos para o redirecionamento
    }

    // Lógica para mostrar o SweetAlert ao enviar o formulário (antes de processar)
    $('form').on('submit', function() {
        Swal.fire({
            title: 'Processando...',
            text: 'Aguarde um momento.',
            icon: 'info',
            showConfirmButton: false
        });
    });
});
