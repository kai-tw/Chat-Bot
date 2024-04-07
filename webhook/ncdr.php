<?php

use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;

require_once '../vendor/autoload.php';
require_once '../config.php';
require_once '../class/user.php';
require_once '../modules/ncdr/earthquake.php';

ini_set('display_errors', FALSE);
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8" ?><Data><Status>true</Status></Data>';
$url = 'php://input';
// $url = 'https://alerts.ncdr.nat.gov.tw/Capstorage/CWA/2024/Earthquake/CWA-EQ113129-2024-0405-023502.cap';
$file = file_get_contents($url);
if ($file !== '' && $file !== '<?xml version="1.0" encoding="utf-8"?><alert xmlns="urn:oasis:names:tc:emergency:cap:1.2"><Test>Test</Test></alert>') {
    $xml = new DOMDocument;
    $xml->load($url);

    $messageList = NCDR\Earthquake::parseXml($xml);

    $client = new Client();
    $lineConfig = new Configuration();
    $lineConfig->setAccessToken(LINE_TOKEN);
    $messageApi = new MessagingApiApi($client, $lineConfig);

    $db = new \mysqli(\DBHOST . ':' . \DBPORT, \DBUSER, \DBPASS, \DBNAME);
    $query = $db->query('SELECT usr.username, usr.line_id FROM `users` usr INNER JOIN `ncdr_users` nusr ON usr.username = nusr.username WHERE nusr.earthquake = 1;');
    while ($item = $query->fetch_assoc()) {
        $username = $item['username'];
        $message = $messageList[$username];

        $textMessage = new TextMessage();
        $textMessage->setType(\LINE\Constants\MessageType::TEXT);
        $textMessage->setText($message);

        $request = new PushMessageRequest();
        $request->setTo($item['line_id']);
        $request->setMessages([$textMessage]);

        $messageApi->pushMessage($request);
    }
    $db->close();
}

function getParam(&$info, $str)
{
    $parameters = $info->getElementsByTagName('parameter');
    foreach ($parameters as $parameter) {
        if (strcmp($parameter->getElementsByTagName('valueName')[0]->nodeValue, $str) === 0) {
            return $parameter->getElementsByTagName('value')[0]->nodeValue;
        }
    }
    return '';
}
