<?php

class WordFilterTrie implements KeywordsModuleInterFace
{
    private $root;

    private static $minMatchTYpe = 1;

    public function __construct($keyWordsSet)
    {
        $this->root = self::getNode();

        foreach ($keyWordsSet as $word) {
            $this->insertWord($word);
        }
    }

    /**
     * 插入一个词到root节点.
     *
     * @param string $word
     *
     * @return bool
     */
    public function insertWord($word)
    {
        $characterArr = Tools::mb_str_split($word);
        if (empty($characterArr)) {
            return false;
        }
        $current = &$this->root;
        foreach ($characterArr as $ch) {
            if (!isset($current['children'][$ch])) {
                $node = self::getNode();
                $node['word'] = $current['word'].$ch;
                $node['character'] = $ch;
                $current['children'][$ch] = $node;
            }
            $current = &$current['children'][$ch];
        }
        $current['is_end'] = 1;

        return true;
    }

    /**
     * 迭代方式删除一个词.
     *
     * @param string $word
     *
     * @return bool
     */
    public function deleteWord($word)
    {
        $characterArr = Tools::mb_str_split($word);
        if (empty($characterArr)) {
            return true;
        }
        $current = &$this->root;
        $length = count($characterArr); //长度
        foreach ($characterArr as $i => $ch) {
            if (!isset($current['children'][$ch])) {
                return true;
            }
            $current = &$current['children'][$ch];
            if (($i == $length - 1) && $current['is_end']) {
                $current['is_end'] = 0;

                return true;
            }
        }
    }

    /**
     * 使用递归方式删除一个词.
     *
     * @param string $word
     *
     * @return bool
     */
    public function deleteWordRecursion($word)
    {
        return self::doDelete($this->root, $word, 0, mb_strlen($word, 'utf8'));
    }

    /**
     * 递归删除一个词语.
     *
     * @param array  $trieNode
     * @param string $str
     * @param int    $index
     * @param int    $len
     *
     * @return bool
     */
    private static function doDelete(&$trieNode, $str, $index, $len)
    {
        $ch = mb_substr($str, $index, 1); //字符，也即键
        if (!isset($trieNode['children'][$ch])) {
            return true; //压根不存在
        }
        if ($index == $len - 1) {
            //到所删字符末端。这时trieNode还是它的父节点
            if ($trieNode['children'][$ch]['is_end']) {
                $trieNode['children'][$ch]['is_end'] = 0; //改变之
            }

            return true;
        }

        return self::doDelete($trieNode['children'][$ch], $str, $index + 1, $len);
    }

    public function CheckSensitiveWord($txtArr, $beginIndex, $matchType)
    {
        $flag = false;    //敏感词结束标识位：用于敏感词只有1位的情况
        $matchFlag = 0;     //匹配标识数默认为0

        $nowMap = $this->root;
        $count = count($txtArr);
        for ($i = $beginIndex; $i < $count; ++$i) {
            $word = $txtArr[$i];
            $nowMap = (isset($nowMap['children'][$word])) ? $nowMap['children'][$word] : [];    //获取指定key
            if (!empty($nowMap)) {     //存在，则判断是否为最后一个
                ++$matchFlag;     //找到相应key，匹配标识+1
                if ($nowMap['is_end'] == '1') {
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
     * 在目标串中搜索所有敏感词.
     *
     * @param string $string      目标串
     * @param bool   $returnWords 是否返回词。默认false，只返回在词中目标串中的位置
     *
     * @return array 返回查找到的词
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

    public function load(array $data)
    {
        $this->root = $data;
    }

    private static function getNode()
    {
        return [
            'character' => null,
            'is_end' => 0,
            'word' => '',
            'children' => [],
        ];
    }

    /**
     * 敏感词检测.
     *
     * @param $string
     */
    public function run($string)
    {
        Tools::debug('============================分割线===================');
        $this->insertWord('指南怀');
//        Tools::debug($this->root);
        Tools::debug('待检测语句字数：'.count(Tools::mb_str_split($string)));

        $beginTime = microtime(true);

        $set = $this->getSensitiveWord($string, true);

        $endTime = microtime(true);
        Tools::debug('语句中包含敏感词的个数为：'.count($set).'。包含：');
        Tools::debug($set);
        Tools::debug('总共消耗时间为：'.(($endTime - $beginTime) * 1000).'ms');
    }
}
