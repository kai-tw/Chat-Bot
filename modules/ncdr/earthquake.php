<?php

namespace NCDR;

class Earthquake
{
    public static function parseXml($xml)
    {
        $identifier = $xml->getElementsByTagName('identifier')[0]->nodeValue;

        if (!preg_match('/^CWA-EQ\d{6}-\d{4}-\d{4}-\d{6}$/', $identifier)) {
            // This is not a report related to an earthquake.
            return false;
        }

        $db = new \mysqli(\DBHOST . ':' . \DBPORT, \DBUSER, \DBPASS, \DBNAME);

        $intensity = Earthquake::parseIntensity($xml);
        $nameAreaMap = Earthquake::parseNameAreaMap($db);
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

    private static function parseNameAreaMap(\mysqli $db)
    {
        $sql = 'SELECT usr.username, area.area_name FROM `ncdr_users` usr INNER JOIN `ncdr_users_area` area ON usr.username = area.username WHERE `earthquake` = 1  
ORDER BY `usr`.`username` ASC';
        $query = $db->query($sql);

        $nameAreaMap = [];
        while ($item = $query->fetch_assoc()) {
            $username = $item['username'];
            $areaName = mb_substr($item['area_name'], 0, 3);

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
