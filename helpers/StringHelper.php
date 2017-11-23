<?php namespace helpers;

/**
 * Class StringHelper
 *
 */
class StringHelper
{
    /**
     * @param string $text
     *
     * @return string
     */
    public static function stringToWeb(string $text)
    {
        $text = preg_replace('/[\s]*([A-Z])/', ' $1', $text);
        $text = trim($text);
        $text = strtolower($text);
        $text = str_replace(' ', '-', $text);

        return $text;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function webPathToString(string $text)
    {
        $text = mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
        $text = str_replace('-', '', $text);

        return $text;
    }
}