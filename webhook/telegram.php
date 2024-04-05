<?php
require_once '../vendor/autoload.php';
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$telegram = new Telegram\Bot\Api(TELEGRAM_TOKEN);
$response = $telegram->setWebhook(['url' => WEBHOOK_ROOT_PATH . '/telegram.php?token=' . TELEGRAM_TOKEN]);

if (!isset($_GET['token']) || $_GET['token'] !== TELEGRAM_TOKEN) {
    echo '{}';
    exit(0);
}

/**
 * Command Registrations
 */
require_once '../commands/start_telegram.php';

$telegram->addCommands([StartCommand::class]);
$telegram->commandsHandler(true);
