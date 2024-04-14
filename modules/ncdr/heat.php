<?php

namespace NCDR;

require_once 'class/heat_record.php';
require_once 'class/heat_data.php';

class Heat
{
    public static function parseXml($xml)
    {
        if (!Heat::isReport($xml)) {
            // This is not a report related to an earthquake.
            return false;
        }

        $db = new \mysqli(\DBHOST . ':' . \DBPORT, \DBUSER, \DBPASS, \DBNAME);
        $sql = 'SELECT `county`,`heat_color`,`heat_exp` FROM `ncdr_alerts`';
        $result = $db->query($sql);
        $areaAlertRecordList = [];

        // Get all saved records
        while ($item = $result->fetch_assoc()) {
            $areaAlertRecordList[$item['county']] = new HeatRecord($item['heat_color'], $item['heat_exp']);
        }

        $areaAlertList = Heat::parseAreaAlertList($xml);

        // Get the intersected area list and filter out the different elements
        $arrayIntersect = array_intersect_key($areaAlertList, $areaAlertRecordList);
        $arrayDiff = array_udiff_assoc($arrayIntersect, $areaAlertRecordList, function (HeatRecord $a, HeatRecord $b) {
            return $a->isNotEqualTo($b);
        });

        // Update all new records to DB
        $sql = "UPDATE `ncdr_alerts` SET `heat_color` = ?,`heat_exp` = ? WHERE `county` = ?";
        $statement = $db->prepare($sql);
        $statement->bind_param("iss", $colorCode, $expireTimeString, $countyName);

        foreach ($arrayDiff as $countyName => $record) {
            $colorCode = $record->getColorCode();
            $expireTimeString = $record->getExpireTimeString();
            $statement->execute();
        }

        // Message construction
        $messageList = [];
        $nameAreaMap = NCDRUtility::getNameAreaMap($db);
        $heatData = new HeatData($xml->getElementsByTagName('info')[0]);

        foreach ($nameAreaMap as $username => $areaList) {
            $userAreaArray = array_intersect_key($arrayDiff, array_flip($areaList));

            $message = Heat::messageConstructor($heatData, $userAreaArray);
            if ($message !== false) {
                $messageList[$username] = $message;
            }
        }

        $db->close();

        return $messageList;
    }

    public static function isReport(\DOMDocument $xml)
    {
        $identifier = $xml->getElementsByTagName('identifier')[0]->nodeValue;
        return preg_match('/^CWB-Weather_heat_\d{15}$/', $identifier);
    }

    private static function parseAreaAlertList($xml)
    {
        $infoNodes = $xml->getElementsByTagName('info');
        $areaAlertList = [];

        foreach ($infoNodes as $info) {
            $heatData = new HeatData($info);
            $areaAlertList = array_merge($areaAlertList, $heatData->getAreaAlertList());
        }

        return $areaAlertList;
    }

    private static function messageConstructor(HeatData $heatData, array $areaList)
    {
        $description = $heatData->getDescription();
        $message = "<告知> 高溫速報\n{$description}\n\n";
        $expireTime = $heatData->getExpireTime()->format('Y/m/d h:i:s');

        $push = false;
        foreach ($areaList as $countyName => $record) {
            $colorString = $record->getColorString();

            if (!is_null($colorString)) {
                $push = true;
                $message .= "- {$countyName}：{$colorString}\n";
            }
        }
        $message .= "\n有效期限：{$expireTime}\n詳：https://www.cwa.gov.tw/V8/C/P/Warning/W29.html";

        return $push ? $message : false;
    }
}
