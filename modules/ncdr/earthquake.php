<?php

namespace NCDR;

require_once 'utility.php';

class Earthquake
{
    public static function parseXml($xml)
    {
        if (!Earthquake::isReport($xml)) {
            // This is not a report related to an earthquake.
            return false;
        }

        $db = new \mysqli(\DBHOST . ':' . \DBPORT, \DBUSER, \DBPASS, \DBNAME);

        $intensity = Earthquake::parseIntensity($xml);
        $nameAreaMap = NCDRUtility::getNameAreaMap($db, AreaType::City);
        $messageList = [];

        foreach ($nameAreaMap as $username => $areaList) {
            $message = Earthquake::messageConstructor($xml, $intensity, $areaList);
            if ($message !== false) {
                $messageList[$username] = $message;
            }
        }

        $db->close();

        return $messageList;
    }

    public static function isReport($xml)
    {
        $identifier = $xml->getElementsByTagName('identifier')[0]->nodeValue;
        return preg_match('/^CWA-EQ\d{6}-\d{4}-\d{4}-\d{6}$/', $identifier);
    }

    private static function parseIntensity(\DOMDocument $xml)
    {
        $intensity = [];
        $info = $xml->getElementsByTagName('info')[0];
        $parameters = $info->getElementsByTagName('parameter');
        foreach ($parameters as $param) {
            if ($param->getElementsByTagName('valueName')[0]->nodeValue === 'LocalMaxIntensity') {
                $val = explode(';', $param->getElementsByTagName('value')[0]->nodeValue);
                $intensity[mb_substr($val[1], 1, 3, 'UTF-8')] = $val[0];
            }
        }
        return $intensity;
    }

    private static function messageConstructor(\DOMDocument $xml, array $intensity, array $areaList)
    {
        $message = "<告知> 地震速報\n";

        $info = $xml->getElementsByTagName('info')[0];
        $message .= $info->getElementsByTagName('description')[0]->nodeValue . "\n";

        $identifier = $xml->getElementsByTagName('identifier')[0]->nodeValue;

        $push = false;
        foreach ($areaList as $area) {
            if (isset($intensity[$area])) {
                $push = true;
                $message .= "{$area}：{$intensity[$area]}\n";
            }
        }
        $message .= '詳：https://www.cwa.gov.tw/V8/C/E/EQ/' . substr($identifier, 4, 9) . substr($identifier, 18) . '.html';

        return $push ? $message : false;
    }
}
