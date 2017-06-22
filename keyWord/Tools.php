<?php

class Tools
{
    /**
     * 将字符串分割为数组.
     *
     * @param string $str 字符串
     *
     * @return array 分割得到的数组
     */
    public static function mb_str_split($str)
    {
        return preg_split('/(?<!^)(?!$)/u', $str);
    }

    public static function debug($arr)
    {
        echo '<pre>'.print_r($arr, true).'</pre>';
    }
}
