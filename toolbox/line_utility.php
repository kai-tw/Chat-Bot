<?php
class LineUtility
{
    public static function parseCommandName(string $text)
    {
        $start = strPos($text, '/');

        if ($start === false || strlen($text) <= 1) {
            return '';
        }

        $end = strPos($text, ' ', $start);
        $end = $end !== false ? $end : strPos($text, "\n", $end);
        $end = $end !== false ? $end : strlen($text);
        return substr($text, $start + 1, $end - $start - 1);
    }
}
