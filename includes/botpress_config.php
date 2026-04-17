<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';

function botpress_bot_id(): string
{
    return getenv('BOTPRESS_BOT_ID') ?: '';
}

function botpress_client_id(): string
{
    return getenv('BOTPRESS_CLIENT_ID') ?: '';
}

function botpress_is_ready(): bool
{
    return botpress_bot_id() !== '' && botpress_client_id() !== '';
}
