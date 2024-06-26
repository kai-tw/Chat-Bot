<?php

use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use Telegram\Bot\Api;

require_once '../vendor/autoload.php';
require_once '../config.php';
require_once '../class/user.php';
require_once '../modules/ncdr/earthquake.php';
require_once '../modules/ncdr/heat.php';
require_once '../modules/ncdr/utility.php';

ini_set('display_errors', FALSE);
header('Content-Type: text/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8" ?><Data><Status>true</Status></Data>';
$url = 'php://input';
$file = file_get_contents($url);
if ($file !== '' && $file !== '<?xml version="1.0" encoding="utf-8"?><alert xmlns="urn:oasis:names:tc:emergency:cap:1.2"><Test>Test</Test></alert>') {
    $xml = new DOMDocument;
    $xml->load($url);

    /**
     * Telegram Api Initialization
     */
    $telegram = new Api(TELEGRAM_TOKEN);

    $db = new \mysqli(\DBHOST . ':' . \DBPORT, \DBUSER, \DBPASS, \DBNAME);
    $statement = $db->prepare("INSERT INTO `ncdr_rawdata`(`identifier`, `xml`) VALUES (?, ?)");
    $statement->bind_param("ss", $identifier, $rawData);
    $rawData = $file;

    /**
     * Inform Admin that received a report.
     */
    $identifier = $xml->getElementsByTagName('identifier')[0]->nodeValue;
    $sender = $xml->getElementsByTagName('sender')[0]->nodeValue;
    $sent = $xml->getElementsByTagName('sent')[0]->nodeValue;
    $logMessage = "{$identifier}\n{$sender}\n{$sent}";

    $statement->execute();

    $messageList = [];

    if (NCDR\Earthquake::isReport($xml)) {
        $messageList = NCDR\Earthquake::parseXml($xml);
    }

    if (NCDR\Heat::isReport($xml)) {
        $messageList = NCDR\Heat::parseXml($xml);
    }

    if (sizeof($messageList) > 0) {
        /**
         * LINE Api Initialization
         */
        $client = new Client();
        $lineConfig = new Configuration();
        $lineConfig->setAccessToken(LINE_TOKEN);
        $messageApi = new MessagingApiApi($client, $lineConfig);

        $query = $db->query('SELECT usr.username, usr.line_id, usr.telegram_id FROM `users` usr INNER JOIN `ncdr_users` nusr ON usr.username = nusr.username WHERE nusr.earthquake = 1;');
        while ($item = $query->fetch_assoc()) {
            $username = $item['username'];

            if (!isset($messageList[$username])) {
                // No message needs to be pushed.
                continue;
            }

            $message = $messageList[$username];

            $lineException =
                \NCDR\NCDRUtility::sendLineMessage($messageApi, $item['line_id'], $message);
            \NCDR\NCDRUtility::sendTelegramMessage($telegram, $item['telegram_id'], $message);

            if ($lineException) {
                $logMessage .= "\n" . $lineException->getMessage();
            }
        }
    }
    $db->close();

    $telegram->sendMessage([
        'chat_id' => ADMIN_ACCOUNT,
        'text' => $logMessage
    ]);
}
