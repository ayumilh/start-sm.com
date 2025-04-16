function showContent(contentId, menuItemId) {
    // Esconde todos os conteúdos
    const contents = document.querySelectorAll('.content-section');
    contents.forEach(content => content.style.display = 'none');
  
    // Mostra o conteúdo desejado
    const content = document.getElementById(contentId);
    if (content) {
      content.style.display = 'block';
    }
  
    // Remove destaque de todos os botões
    const allButtons = document.querySelectorAll('li button');
    allButtons.forEach(button => {
      button.classList.remove('text-blue-700', 'font-bold');
      const span = button.querySelector('span');
      const icon = button.querySelector('i');
      if (span) span.classList.remove('text-blue-700', 'font-bold');
      if (icon) icon.classList.remove('text-blue-700');
    });
  
    // Remove destaque de todos os <li> pais
    const allLis = document.querySelectorAll('nav ul li');
    allLis.forEach(li => li.classList.remove('bg-blue-100', 'border-l-4', 'border-blue-700'));
  
    // Adiciona destaque ao botão clicado
    const activeButton = document.getElementById(menuItemId);
    if (activeButton) {
      activeButton.classList.add('text-blue-700', 'font-bold');
      const span = activeButton.querySelector('span');
      const icon = activeButton.querySelector('i');
      if (span) span.classList.add('text-blue-700', 'font-bold');
      if (icon) icon.classList.add('text-blue-700');
  
      // Destaca o <li> pai (item clicado ou submenu pai)
      const liPai = activeButton.closest('li');
      if (liPai) liPai.classList.add('bg-blue-100', 'border-l-4', 'border-blue-700');
  
      // Se for submenu, destaca o <li> do menu principal também
      const liMenuPai = activeButton.closest('ul')?.closest('li');
      if (liMenuPai) liMenuPai.classList.add('bg-blue-100', 'border-l-4', 'border-blue-700');
    }
  
    // Fecha menu mobile se estiver aberto
    closeMenu?.();
  }
  

// Função para esconder o conteúdo
function hideContent(contentId) {
    const content = document.getElementById(contentId);
    if (content) {
        content.style.display = 'none'; // Esconde o conteúdo desejado
    }
}

// Função para garantir que apenas um conteúdo esteja aberto por vez
function closeOtherMenus(currentContentId) {
    const contentIds = [
        'smsContent',
        'smsFormContainer',
        'saldoContent',
        'geradorContent',
        'smsFlexContent',
        'smsTurboContent',
        'smsLeveContent'
      ];contentIds.forEach(contentId => {
        if (contentId !== currentContentId) {
            hideContent(contentId);
        }
    });
}

// Inicializa com os dois conteúdos ocultos
document.addEventListener('DOMContentLoaded', function () {
    hideContent('smsContent');
    hideContent('saldoContent');
    hideContent('smsFormContainer');
    hideContent('geradorContent');
    hideContent('smsFlexContent');
    hideContent('smsTurboContent');
    hideContent('smsLeveContent');
});

function showSmsForm(contentId, menuItemId) {
    // Esconde todos os conteúdos
    const contents = document.querySelectorAll('.content-section');
    contents.forEach(function (content) {
        content.style.display = 'none'; // Esconde todos os conteúdos
    });

    // Exibe o conteúdo específico
    const smsFormContainer = document.getElementById('smsFormContainer');
    if (smsFormContainer) {
        smsFormContainer.style.display = 'block'; // Exibe o conteúdo desejado
    }

    // Remove a classe ativa (texto azul) de todos os itens de menu
    const menuItems = document.querySelectorAll('li');
    menuItems.forEach(function (item) {
        item.classList.remove('text-blue-700', 'font-bold'); // Remove a classe de ativo de todos os itens
        const span = item.querySelector('span');
        const icon = item.querySelector('i');
        if (span) {
            span.classList.remove('text-blue-700'); // Remove o azul do texto
        }
        if (icon) {
            icon.classList.remove('text-blue-700'); // Remove o azul do ícone
        }
    });

    // Adiciona a classe de ativo ao item que foi clicado
    const activeItem = document.getElementById(menuItemId);
    if (activeItem) {
        activeItem.classList.add('text-blue-700', 'font-bold'); // Destaca o item do menu
        const span = activeItem.querySelector('span');
        const icon = activeItem.querySelector('i');
        if (span) {
            span.classList.add('text-blue-700'); // Adiciona o texto azul no <span>
        }
        if (icon) {
            icon.classList.add('text-blue-700'); // Adiciona o azul no ícone
        }
    }

    closeMenu();
}


function closeTransactionModal() {
    document.getElementById('transactionModal').style.display = "none";
}


// Função para fechar o menu lateral
function closeMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.add('translate-x-full'); // Fecha o menu
        mobileMenu.classList.remove('translate-x-0'); // Remove a classe para exibir o menu
        mobileMenu.classList.add('hidden'); // Esconde o menu
    }
}