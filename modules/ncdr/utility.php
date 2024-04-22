<?php

namespace NCDR;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Telegram\Bot\Api;

class NCDRUtility
{
    public static function getNameAreaMap(\mysqli $db, AreaType $type = AreaType::Full)
    {
        $sql = 'SELECT `usr`.`username`, `area`.`area_name` FROM `ncdr_users` usr INNER JOIN `ncdr_users_area` area ON `usr`.`username` = `area`.`username` WHERE `earthquake` = 1  
ORDER BY `usr`.`username` ASC';
        $query = $db->query($sql);

        $nameAreaMap = [];
        while ($item = $query->fetch_assoc()) {
            $username = $item['username'];

            switch ($type) {
                case AreaType::City:
                    $areaName = mb_substr($item['area_name'], 0, 3);
                    break;
                default:
                    $areaName = $item['area_name'];
            }

            // If it doesn't initialize, initializa it.
            if (!isset($nameAreaMap[$item['username']])) {
                $nameAreaMap[$item['username']] = [];
            }

            // If it doesn't duplicated, push it into the map.
            if (!in_array($areaName, $nameAreaMap[$username])) {
                array_push($nameAreaMap[$username], $areaName);
            }
        }

        return ($nameAreaMap);
    }

    public static function sendLineMessage(MessagingApiApi $messageApi, ?string $lineId, string $message)
    {
        if (!isset($lineId) || strlen($lineId) !== 33) {
            return;
        }

        $currentTime = new \DateTime();
        $isNotify = (new \DateTime('7:00')) <= $currentTime && $currentTime <= (new \DateTime('22:00'));

        $textMessage = new TextMessage();
        $textMessage->setType(\LINE\Constants\MessageType::TEXT);
        $textMessage->setText($message);

        $request = new PushMessageRequest();
        $request->setTo($lineId);
        $request->setMessages([$textMessage]);
        $request->setNotificationDisabled(!$isNotify);

        try {
            $messageApi->pushMessage($request);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public static function sendTelegramMessage(Api $telegram, ?string $telegramId, string $message)
    {
        $idLength = strlen($telegramId);
        if (!isset($telegramId) || ($idLength !== 10 && $idLength !== 11)) {
            return;
        }

        $telegram->sendMessage([
            'chat_id' => $telegramId,
            'text' => $message
        ]);
    }
}

enum AreaType
{
    case Full;
    case City;
}
