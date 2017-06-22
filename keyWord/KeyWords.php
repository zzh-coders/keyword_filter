<?php

class KeyWords
{
    protected $keyWord;
    public function __construct(KeywordsModuleInterFace $module)
    {
        $this->keyWord = $module;
    }

    public function run($string)
    {
        $this->keyWord->run($string);
    }

    /**
     * 进行敏感词转换成数组.
     * 逐行读取，可以节省内存.
     *
     * @return array
     */
    public static function initSet()
    {
        $filename = __DIR__.'/keyWordSet.txt';
        $keyWordSet = [];
        $file_handle = fopen($filename, 'r');
        while (!feof($file_handle)) {
            $line = trim(fgets($file_handle));
            array_push($keyWordSet, $line);
        }
        fclose($file_handle);

        return $keyWordSet;
    }
}
