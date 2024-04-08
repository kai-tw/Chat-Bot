<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Telegram\Bot\Api;

require_once '../vendor/autoload.php';
require_once '../config.php';
require_once '../class/user.php';
require_once '../modules/ncdr/earthquake.php';

ini_set('display_errors', FALSE);
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8" ?><Data><Status>true</Status></Data>';
$url = 'php://input';
// $url = 'https://alerts.ncdr.nat.gov.tw/Capstorage/CWA/2024/Earthquake/CWA-EQ113178-2024-0408-072850.cap';
$file = file_get_contents($url);
if ($file !== '' && $file !== '<?xml version="1.0" encoding="utf-8"?><alert xmlns="urn:oasis:names:tc:emergency:cap:1.2"><Test>Test</Test></alert>') {
    $xml = new DOMDocument;
    $xml->load($url);

    $messageList = [];

    if (NCDR\Earthquake::isEarthquakeReport($xml)) {
        $messageList = NCDR\Earthquake::parseXml($xml);
    }

    if (sizeof($messageList) > 0) {
        /**
         * LINE Api Initialization
         */
        $client = new Client();
        $lineConfig = new Configuration();
        $lineConfig->setAccessToken(LINE_TOKEN);
        $messageApi = new MessagingApiApi($client, $lineConfig);

        /**
         * Telegram Api Initialization
         */
        $telegram = new Api(TELEGRAM_TOKEN);

        $db = new \mysqli(\DBHOST . ':' . \DBPORT, \DBUSER, \DBPASS, \DBNAME);
        $query = $db->query('SELECT usr.username, usr.line_id, usr.telegram_id FROM `users` usr INNER JOIN `ncdr_users` nusr ON usr.username = nusr.username WHERE nusr.earthquake = 1;');
        while ($item = $query->fetch_assoc()) {
            $username = $item['username'];

            if (!isset($messageList[$username])) {
                // No message needs to be pushed.
                continue;
            }

            $message = $messageList[$username];

            sendLineMessage($messageApi, $item['line_id'], $message);
            sendTelegramMessage($telegram, $item['telegram_id'], $message);
        }
    }
    $db->close();
}

function sendLineMessage(MessagingApiApi $messageApi, ?string $lineId, string $message)
{
    if (!isset($lineId) || strlen($lineId) !== 32) {
        return;
    }

    $textMessage = new TextMessage();
    $textMessage->setType(\LINE\Constants\MessageType::TEXT);
    $textMessage->setText($message);

    $request = new PushMessageRequest();
    $request->setTo($lineId);
    $request->setMessages([$textMessage]);

    try {
        $messageApi->pushMessage($request);
    } catch (RequestException $e) {
    } catch (ConnectException $e) {
    }
}

function sendTelegramMessage(Api $telegram, ?string $telegramId, string $message)
{
    if (!isset($telegramId) || strlen($telegramId) !== 10) {
        return;
    }

    $telegram->sendMessage([
        'chat_id' => $telegramId,
        'text' => $message
    ]);
}
