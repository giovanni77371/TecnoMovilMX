<?php
include_once __DIR__ . "/botpress_config.php";
?>
<script>
window.TecnoMovilChatConfig = {
  useBotpress: <?= botpress_is_ready() ? 'true' : 'false' ?>,
  botId: <?= json_encode(botpress_bot_id(), JSON_UNESCAPED_UNICODE) ?>,
  clientId: <?= json_encode(botpress_client_id(), JSON_UNESCAPED_UNICODE) ?>,
  webhookUrl: "api/chatbot.php"
};
</script>
<script src="js/chatbot.js?v=2"></script>
