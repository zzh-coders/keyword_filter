<?php

spl_autoload_register(function ($class) {
    include 'keyWord/'.$class.'.php';
});

$keywordset = KeyWords::initSet();

$daf_model = new WordFilterByDaf($keywordset);

$string = '太多的伤感情怀也许只局限于饲养基地 荧幕中的情节，主人公尝试着去用某种方式渐渐的很潇洒地释自杀指南怀那些自己经历的伤感。'
    .'然后法轮功 我们的扮演的角色就是跟随着主人公的喜红客联盟 怒哀乐而过于牵强的把自己的情感也附加于银幕情节中，然后感动就流泪，'
    .'难过就躺在某一个人的怀里尽情的阐述心扉或者手机卡复制器一个人一杯红酒一部电影在夜三级片 深人静的晚上，关上电话静静的发呆着。';
Tools::debug('===================敏感词===========================');
Tools::debug($string);
$keywords = new KeyWords($daf_model);
$keywords->run($string);

unset($keywords);

$nomal_module = new WordFilterByNomal($keywordset);
$keywords = new KeyWords($nomal_module);
$keywords->run($string);

unset($keywords);

$nomal_module = new WordFilterTrie($keywordset);
$keywords = new KeyWords($nomal_module);
$keywords->run($string);
