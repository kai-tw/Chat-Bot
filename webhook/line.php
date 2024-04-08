<?php

use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Parser\EventRequestParser;
use LINE\Parser\Exception\InvalidEventRequestException;
use LINE\Parser\Exception\InvalidSignatureException;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;

require_once '../vendor/autoload.php';
require_once '../config.php';
require_once '../class/common_command_manager.php';
require_once '../toolbox/common_utility.php';
require_once '../toolbox/line_utility.php';

$client = new Client();
$lineConfig = new Configuration();
$lineConfig->setAccessToken(LINE_TOKEN);
$messageApi = new MessagingApiApi($client, $lineConfig);

$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

if (empty($signature)) {
    header("HTTP/1.1 400 Bad Request.");
    exit(0);
}

$httpRequestBody = file_get_contents('php://input');

try {
    $parsedEvents = EventRequestParser::parseEventRequest($httpRequestBody, LINE_SECRETE, $signature);
} catch (InvalidSignatureException $e) {
    header("HTTP/1.1 400 Bad Request.");
} catch (InvalidEventRequestException $e) {
    header("HTTP/1.1 400 Bad Request.");
}

/**
 * Command Registrations
 */
CommonUtility::includeAllFile('../commands');
$commandManager = new CommonCommandManager();
$commandManager->addCommands([
    new StartCommand(),
    new LineUserIdCommand()
]);

foreach ($parsedEvents->getEvents() as $event) {
    if (!($event instanceof MessageEvent)) {
        continue;
    }

    $message = $event->getMessage();

    if (!($message instanceof TextMessageContent)) {
        continue;
    }

    $text = $message->getText();

    /**
     * Only Process commands.
     */
    $commandManager->setLineEvent($event);
    $command = LineUtility::parseCommandName($text);
    $result = $commandManager->execute($command);

    if ($result !== false) {
        $textMessage = new TextMessage();
        $textMessage->setType(\LINE\Constants\MessageType::TEXT);
        $textMessage->setText($result);

        $request = new ReplyMessageRequest();
        $request->setReplyToken($event->getReplyToken());
        $request->setMessages([$textMessage]);

        $messageApi->replyMessage($request);
    }
}
