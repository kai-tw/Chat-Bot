<?php

namespace NCDR;

class HeatRecord
{
    private HeatRecordColor $color;
    private ?\DateTime $expireTime;

    public function __construct(int $colorCode = 0, string $exp = null)
    {
        $this->color = HeatRecordColor::cases()[$colorCode];
        $this->expireTime = is_null($exp) ? $exp : new \DateTime($exp);
    }

    public function setColor(HeatRecordColor $color)
    {
        $this->color = $color;
    }

    public function setColorFromString(string $colorString)
    {
        $map = [
            '高溫紅色燈號' => HeatRecordColor::Red,
            '高溫橙色38燈號' => HeatRecordColor::Orange38,
            '高溫橙色36燈號' => HeatRecordColor::Orange36,
            '高溫黃色燈號' => HeatRecordColor::Yellow
        ];

        $this->color = $map[$colorString] ?? HeatRecordColor::Nothing;
    }

    public function setExpireTime(\DateTime $exp)
    {
        $this->expireTime = $exp;
    }

    public function getColorCode()
    {
        switch ($this->color) {
            case HeatRecordColor::Red:
                return 4;
            case HeatRecordColor::Orange38:
                return 3;
            case HeatRecordColor::Orange36:
                return 2;
            case HeatRecordColor::Yellow:
                return 1;
        }
        return 0;
    }

    public function getColorString()
    {
        switch ($this->color) {
            case HeatRecordColor::Red:
                return '高溫紅色燈號';
            case HeatRecordColor::Orange38:
                return '高溫橙色38燈號';
            case HeatRecordColor::Orange36:
                return '高溫橙色36燈號';
            case HeatRecordColor::Yellow:
                return '高溫黃色燈號';
        }
    }

    public function getExpireTimeString()
    {
        return $this->expireTime->format('Y/m/d H:i:s');
    }

    public function isEqualTo(HeatRecord $target)
    {
        return $this->color === $target->color && $this->expireTime == $target->expireTime;
    }

    public function isNotEqualTo(HeatRecord $target)
    {
        return !$this->isEqualTo($target);
    }
}

enum HeatRecordColor
{
    case Nothing;
    case Yellow;
    case Orange36;
    case Orange38;
    case Red;
}
