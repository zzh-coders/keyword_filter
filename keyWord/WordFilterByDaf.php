<?php

/**
 * @Description: 敏感词过滤
 * @Project：keyWord
 * User: zouzehua
 * Date: 2017/6/19 下午15:34
 *
 * @version 1.0
 */
class WordFilterByDaf implements KeywordsModuleInterFace
{
    private $sensitiveWordMap = [];
    public static $minMatchTYpe = 1;      //最小匹配规则
    public static $maxMatchType = 2;      //最大匹配规则

    /**
     * 构造函数，初始化敏感词库.
     */
    public function __construct($keyWordSet)
    {
        $this->addSensitiveWordToHashMap($keyWordSet);
    }

    public function getSensitiveWordMapSize()
    {
        return count($this->sensitiveWordMap);
    }

    public function getSensitiveWordMap()
    {
        return $this->sensitiveWordMap;
    }

    public function addSensitiveWordToHashMap($keyWordSet)
    {
        $i = 0;
        foreach ($keyWordSet as $value) {
            /**
             * 将关键字分割成单个的字符（汉字）.
             */
            $keyArr = Tools::mb_str_split($value);
            $keyCount = count($keyArr);

            $nowMap = $this->sensitiveWordMap;

            $this->createTree($nowMap, $keyArr, 0, $keyCount);

            $this->sensitiveWordMap[$keyArr[0]] = $nowMap[$keyArr[0]];
        }
    }

    /**

     * 转换成一颗hash树.
     *
     * @param array $nowMap
     * @param array $keyArr
     * @param int   $i
     * @param int   $keyCount
     */
    private function createTree(&$nowMap, $keyArr, $i, $keyCount)
    {
        $keyChar = $keyArr[$i];
        $wordMap = isset($nowMap[$keyChar]) ? $nowMap[$keyChar] : null;
        if (empty($wordMap)) {
            $nowMap[$keyChar] = ['isEnd' => 0];
        }

        if ($i == ($keyCount - 1)) {
            $nowMap[$keyChar] = ['isEnd' => 1];
        } else {
            ++$i;
            $this->createTree($nowMap[$keyChar], $keyArr, $i, $keyCount);
        }
    }

    /**
     * 判断文字是否包含敏感字符
     * User: zouzehua
     * Date: 2017/6/19 下午15:34.
     *
     * @param string $txt       文字
     * @param int    $matchType 匹配规则&nbsp;1：最小匹配规则，2：最大匹配规则
     *
     * @return bool 若包含返回true，否则返回false
     *
     * @version 1.0
     */
    public function isContaintSensitiveWord($txt, $matchType)
    {
        $flag = false;
        $txtArr = Tools::mb_str_split($txt);

        foreach ($txtArr as $i => $txtChar) {
            $matchFlag = $this->CheckSensitiveWord($txtArr, $i, $matchType); //判断是否包含敏感字符
            if ($matchFlag > 0) {    //大于0存在，返回true
                $flag = true;
            }
        }

        return $flag;
    }

    /**
     * 获取文字中的敏感词.
     *
     * User: zouzehua
     * Date: 2017/6/19 下午15:34.
     *
     * @param string $txt       文字
     * @param int    $matchType 匹配规则&nbsp;1：最小匹配规则，2：最大匹配规则
     *
     * @return array
     *
     * @version 1.0
     */
    public function getSensitiveWord($txt, $matchType)
    {
        $sensitiveWordList = [];

        $txtArr = Tools::mb_str_split($txt);
        $currI = 0;
        foreach ($txtArr as $i => $value) {
            if ($currI < $i) {
                continue;
            }
            $length = $this->CheckSensitiveWord($txtArr, $currI, $matchType); //判断是否包含敏感字符
            ++$currI;
            if ($length > 0) {
                //存在,加入list中
                $newText = array_slice($txtArr, $currI - 1, $length);
                $sensitiveWordList[] = implode('', $newText);
                $currI = $currI + $length - 1; //减1的原因，是因为for会自增
            }
        }

        return $sensitiveWordList;
    }

    /**
     * 替换敏感字字符
     * User: zouzehua
     * Date: 2017/6/19 下午15:34.
     *
     * @param string $txt
     * @param int    $matchType
     * @param string $replaceChar 替换字符，默认*
     *
     * @return string
     *
     * @version 1.0
     */
    public function replaceSensitiveWord($txt, $matchType, $replaceChar)
    {
        $resultTxt = $txt;
        $set = $this->getSensitiveWord($txt, $matchType);     //获取所有的敏感词
        foreach ($set as $word) {
            $replaceString = $this->getReplaceChars($replaceChar, $word);
            $resultTxt = str_ireplace($word, $replaceString, $resultTxt);
        }

        return $resultTxt;
    }

    /**
     * 获取替换字符串
     * User: zouzehua
     * Date: 2017/6/19 下午15:34.
     *
     * @param string $replaceChar
     * @param string $word
     *
     * @return string
     *
     * @version 1.0
     */
    private function getReplaceChars($replaceChar, $word)
    {
        $resultReplace = $replaceChar;
        $wordArr = Tools::mb_str_split($word);
        foreach ($wordArr as $key => $value) {
            $resultReplace .= $replaceChar;
        }

        return $resultReplace;
    }

    /**
     * 检查文字中是否包含敏感字符，检查规则如下：<br>
     * User: zouzehua
     * Date: 2017/6/19 下午15:34.
     *
     * @param array $txtArr
     * @param int   $beginIndex
     * @param int   $matchType
     *
     * @return int 如果存在，则返回敏感词字符的长度，不存在返回0
     *
     * @version 1.0
     */
    public function CheckSensitiveWord($txtArr, $beginIndex, $matchType)
    {
        $flag = false;    //敏感词结束标识位：用于敏感词只有1位的情况
        $matchFlag = 0;     //匹配标识数默认为0

        $nowMap = $this->sensitiveWordMap;
        $count = count($txtArr);
        for ($i = $beginIndex; $i < $count; ++$i) {
            $word = $txtArr[$i];
            $nowMap = (empty($nowMap) || !array_key_exists($word, $nowMap)) ? [] : $nowMap[$word];    //获取指定key
            if (!empty($nowMap)) {     //存在，则判断是否为最后一个
                ++$matchFlag;     //找到相应key，匹配标识+1
                if ($nowMap['isEnd'] == '1') {
                    $flag = true;
                    if ($matchType == self::$minMatchTYpe) {
                        break;
                    }
                }
            } else {     //不存在，直接返回
                break;
            }
        }
        if ($matchType < self::$minMatchTYpe || !$flag) {        //长度必须大于等于1，为词
            $matchFlag = 0;
        }

        return $matchFlag;
    }

    /**
     * 敏感词检测.
     *
     * @param $string
     */
    public function run($string)
    {
        Tools::debug('待检测语句字数：'.count(Tools::mb_str_split($string)));

        $beginTime = microtime(true);

        $set = $this->getSensitiveWord($string, 1);

        $endTime = microtime(true);
        Tools::debug('语句中包含敏感词的个数为：'.count($set).'。包含：');
        Tools::debug($set);
        Tools::debug('总共消耗时间为：'.(($endTime - $beginTime) * 1000).'ms');
    }
}
