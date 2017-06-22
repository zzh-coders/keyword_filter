<?php

/**
 * 普通的正则替换
 * User: Administrator
 * Date: 2017/6/20 0020
 * Time: 下午 2:29.
 */
class WordFilterByNomal implements KeywordsModuleInterFace
{
    public static $minMatchTYpe = 1;      //最小匹配规则
    public static $maxMatchType = 2;      //最大匹配规则
    public function __construct($keyWordSet)
    {
        $this->keyWordSet = $keyWordSet;
    }

    /**
     * 获取字符串中敏感词计划.
     *
     * @param string $string
     *
     * @return array
     */
    public function getSensitiveWord($string)
    {
        $result = [];

        foreach ($this->keyWordSet as $word) {
            if (mb_stripos($string, $word) !== false) {
                //替换成*,要不然临界字符会进行查询
                $string = str_ireplace($word, '*', $string);

                $result[] = $word;
            }
        }

        return $result;
    }

    /**
     * 判断文字是否包含敏感字符.
     *
     * @param string $txt
     *
     * @return bool
     */
    public function isContaintSensitiveWord($txt)
    {
        $flag = false;

        foreach ($this->keyWordSet as $word) {
            if (mb_stripos($txt, $word) !== false) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    /**
     * 字符串替换.
     *
     * @param string $txt         字符串
     * @param string $replaceChar 替换字符串
     *
     * @return mixed
     */
    public function replaceSensitiveWord($txt, $replaceChar = '*')
    {
        $resultTxt = $txt;
        $set = $this->getSensitiveWord($txt);     //获取所有的敏感词
        foreach ($set as $word) {
            $txtArr = Tools::mb_str_split($word);

            $replaceCharString = implode('', array_fill(0, count($txtArr), '*'));

            $resultTxt = str_ireplace($word, $replaceCharString, $resultTxt);
        }

        return $resultTxt;
    }

    public function run($string)
    {
        Tools::debug('============================分割线===================');
        Tools::debug('待检测语句字数：'.count(Tools::mb_str_split($string)));

        $beginTime = microtime(true);

        $set = $this->getSensitiveWord($string);

        $endTime = microtime(true);
        Tools::debug('语句中包含敏感词的个数为：'.count($set).'。包含：');
        Tools::debug($set);
        Tools::debug('总共消耗时间为：'.(($endTime - $beginTime) * 1000).'ms');
    }
}
