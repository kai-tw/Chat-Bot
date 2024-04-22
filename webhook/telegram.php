<?php

use Telegram\Bot\Api;
use Telegram\Bot\Commands\HelpCommand;

require_once '../vendor/autoload.php';
require_once '../config.php';
require_once '../toolbox/common_utility.php';

$telegram = new Api(TELEGRAM_TOKEN);
$response = $telegram->setWebhook(['url' => WEBHOOK_ROOT_PATH . '/telegram.php?token=' . TELEGRAM_TOKEN]);

if (!isset($_GET['token']) || $_GET['token'] !== TELEGRAM_TOKEN) {
    header("HTTP/1.1 400 Bad Request.");
    exit(0);
}

/**
 * Command Registrations
 */
CommonUtility::includeAllFile('../commands');

$telegram->addCommands([
    HelpCommand::class,
    TelegramStartCommand::class,
    TelegramUserIdCommand::class,
    TelegramGroupIdCommand::class
]);
$telegram->commandsHandler(true);
