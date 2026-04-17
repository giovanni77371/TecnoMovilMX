(function () {
  const config = window.TecnoMovilChatConfig || {
    useBotpress: false,
    botId: '',
    clientId: '',
    webhookUrl: 'api/chatbot.php'
  };

  function createNode(tag, className, html) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (html) node.innerHTML = html;
    return node;
  }

  function renderFallbackBubble() {
    const launcher = createNode('button', 'chatbot-launcher', `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 3C6.48 3 2 6.94 2 11.8c0 2.23.95 4.27 2.52 5.83L4 22l4.74-2.08c1 .29 2.1.45 3.26.45 5.52 0 10-3.94 10-8.8S17.52 3 12 3Zm-4.5 7.6h9v1.8h-9v-1.8Zm0-3.2h9v1.8h-9V7.4Zm0 6.4h6v1.8h-6v-1.8Z" fill="currentColor"/>
      </svg>
    `);
    launcher.type = 'button';
    launcher.setAttribute('aria-label', 'Abrir chatbot');

    document.body.appendChild(launcher);
  }

  function loadBotpress() {
    const script = document.createElement('script');
    script.src = 'https://cdn.botpress.cloud/webchat/v3.3/inject.js';
    script.async = true;
    script.onload = () => {
      if (!window.botpress) {
        renderFallbackBubble();
        return;
      }

      window.botpress.init({
        botId: config.botId,
        clientId: config.clientId,
        configuration: {
          botName: 'TecnoMovil Asistente',
          color: '#0a4fa3',
          variant: 'soft',
          themeMode: 'light'
        }
      });
    };
    script.onerror = () => {
      renderFallbackBubble();
    };
    document.head.appendChild(script);
  }

  if (config.useBotpress && config.botId && config.clientId) {
    loadBotpress();
    return;
  }

  renderFallbackBubble();
})();
