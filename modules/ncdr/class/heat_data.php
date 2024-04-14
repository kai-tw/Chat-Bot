<?php

namespace NCDR;

class HeatData
{
    private string $event;
    private \DateTime $expireTime;
    private string $headline;
    private string $description;
    private string $instruction;
    private string $severityLevel;
    private string $alertCriteria;
    private string $alertColor;
    private array $areaList;

    public function __construct(\DOMElement $infoNode)
    {
        $this->event = $infoNode->getElementsByTagName('event')[0]->nodeValue;
        $this->expireTime = new \DateTime($infoNode->getElementsByTagName('expires')[0]->nodeValue);
        $this->headline = $infoNode->getElementsByTagName('headline')[0]->nodeValue;
        $this->description = $infoNode->getElementsByTagName('description')[0]->nodeValue;
        $this->instruction = $infoNode->getElementsByTagName('instruction')[0]->nodeValue;
        $this->severityLevel = Heatdata::parseParameter($infoNode, 'severity_level')[0];
        $this->alertCriteria = Heatdata::parseParameter($infoNode, 'alert_criteria')[0];
        $this->alertColor = HeatData::parseParameter($infoNode, 'alert_color')[0];
        $this->areaList = HeatData::parseArea($infoNode);
    }

    public function getExpireTime() {
        return $this->expireTime;
    }

    public function getHeadline()
    {
        return $this->headline;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getAreaAlertList()
    {
        $areaAlertList = [];

        foreach ($this->areaList as $countyName) {
            $alertRecord = new HeatRecord();
            $alertRecord->setColorFromString($this->severityLevel);
            $alertRecord->setExpireTime($this->expireTime);
            $areaAlertList[$countyName] = $alertRecord;
        }

        return $areaAlertList;
    }

    private static function parseParameter(\DOMElement $infoNode, string $name)
    {
        $parameters = $infoNode->getElementsByTagName('parameter');
        $list = [];

        foreach ($parameters as $param) {
            if ($param->getElementsByTagName('valueName')[0]->nodeValue === $name) {
                array_push($list, $param->getElementsByTagName('value')[0]->nodeValue);
            }
        }

        return $list;
    }

    private static function parseArea(\DOMElement $infoNode)
    {
        $area = $infoNode->getElementsByTagName('area');
        $list = [];

        foreach ($area as $item) {
            array_push($list, $item->getElementsByTagName('areaDesc')[0]->nodeValue);
        }

        return $list;
    }
}
