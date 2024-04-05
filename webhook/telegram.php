<?php
require_once '../vendor/autoload.php';
require_once '../config.php';

$telegram = new Telegram\Bot\Api(TELEGRAM_TOKEN);
$response = $telegram->setWebhook(['url' => WEBHOOK_ROOT_PATH . '/telegram.php?token=' . TELEGRAM_TOKEN]);

if (!isset($_GET['token']) || $_GET['token'] !== TELEGRAM_TOKEN) {
    header("HTTP/1.1 400 Bad Request.");
    exit(0);
}

/**
 * Command Registrations
 */
require_once '../commands/start.php';

$telegram->addCommands([StartCommand::class]);
$telegram->commandsHandler(true);
