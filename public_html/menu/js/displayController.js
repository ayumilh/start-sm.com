function hideAllContents() {
    document.querySelectorAll('.content-section').forEach(content => content.style.display = 'none');
}

function resetAllMenuHighlights() {
    document.querySelectorAll('li').forEach(item => {
        item.classList.remove('bg-blue-100', 'border-l-4', 'border-blue-600');
        const button = item.querySelector('button');
        const span = item.querySelector('span');
        const icon = item.querySelector('i');

        if (button) button.classList.remove('text-blue-700', 'font-bold');
        if (span) span.classList.remove('text-blue-700', 'font-bold');
        if (icon) icon.classList.remove('text-blue-700');
    });
}

function highlightMenuItem(menuItemId) {
    const activeButton = document.getElementById(menuItemId);
    if (!activeButton) return;

    const li = activeButton.closest('li');
    if (li) li.classList.add('bg-blue-100', 'border-l-4', 'border-blue-600');

    activeButton.classList.add('text-blue-700', 'font-bold');
    const span = activeButton.querySelector('span');
    const icon = activeButton.querySelector('i');
    if (span) span.classList.add('text-blue-700', 'font-bold');
    if (icon) icon.classList.add('text-blue-700');
}

function showContent(contentId, menuItemId) {
    hideAllContents();

    const content = document.getElementById(contentId);
    if (content) content.style.display = 'block';

    resetAllMenuHighlights();
    highlightMenuItem(menuItemId);

    closeMenu();
}

function showSmsForm(contentId, menuItemId) {
    // Esconde todos os conteúdos
    const contents = document.querySelectorAll('.content-section');
    contents.forEach(content => {
        content.style.display = 'none';
    });

    // Fecha o submenu de SMS
    const submenuSms = document.getElementById('submenuSmsDesktop');
    if (submenuSms) submenuSms.classList.add('hidden');

    // Remove ícone rotacionado (seta)
    const smsIcon = document.getElementById('iconSmsDesktop');
    if (smsIcon) smsIcon.classList.remove('rotate-180');

    // Remove destaque de todos os menus e submenus
    const allLis = document.querySelectorAll('nav ul li');
    allLis.forEach(li => li.classList.remove('bg-blue-100', 'border-l-4', 'border-blue-700'));

    const allButtons = document.querySelectorAll('li button');
    allButtons.forEach(button => {
        button.classList.remove('text-blue-700', 'font-bold');
        const span = button.querySelector('span');
        const icon = button.querySelector('i');
        if (span) span.classList.remove('text-blue-700', 'font-bold');
        if (icon) icon.classList.remove('text-blue-700');
    });

    // Exibe o WhatsApp
    const smsFormContainer = document.getElementById(contentId);
    if (smsFormContainer) smsFormContainer.style.display = 'block';

    // Aplica destaque apenas ao item do WhatsApp
    const activeItem = document.getElementById(menuItemId);
    if (activeItem) {
        activeItem.classList.add('text-blue-700', 'font-bold');
        const span = activeItem.querySelector('span');
        const icon = activeItem.querySelector('i');
        if (span) span.classList.add('text-blue-700', 'font-bold');
        if (icon) icon.classList.add('text-blue-700');

        const liPai = activeItem.closest('li');
        if (liPai) liPai.classList.add('bg-blue-100', 'border-l-4', 'border-blue-700');
    }

    closeMenu();
}


function hideContent(contentId) {
    const content = document.getElementById(contentId);
    if (content) content.style.display = 'none';
}

function closeOtherMenus(currentContentId) {
    const contentIds = [
        'smsContent',
        'smsFormContainer',
        'saldoContent',
        'geradorContent',
        'smsFlexContent',
        'smsTurboContent',
        'smsLeveContent'
    ];
    contentIds.forEach(id => {
        if (id !== currentContentId) hideContent(id);
    });
}

function showSaldoModal() {
    document.getElementById('saldoModal')?.classList.remove('hidden');
}

function closeSaldoModal() {
    document.getElementById('saldoModal')?.classList.add('hidden');
}

function closeTransactionModal() {
    document.getElementById('transactionModal')?.style.setProperty('display', 'none');
}

function closeMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.add('translate-x-full', 'hidden');
        mobileMenu.classList.remove('translate-x-0');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Oculta todas as seções no início
    hideAllContents();
});


