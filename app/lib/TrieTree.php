<?php

namespace App\lib;

/**
 * 敏感词过滤类
 * 采用DFA算法
 * 装载后数据结构如下：
 *   $words = [
 *       '小' => [
 *           'end'=>0,
 *           '日' => [
 *               'end'=>0,
 *               '本' => [
 *                   'end'=>1,
 *                ],
 *           ],
 *       ],
 *       '日' => [
 *          'end'=>0,
 *          '本' => [
 *              'end'=>0,
 *              '鬼' => [
 *                   'end'=>0,
 *                   '子' => [
     *                   'end'=>1,
     *                ],
 *               ],
 *               '人' => [
 *                   'end'=>1,
 *                ],
 *           ],
 *       ],
 *   ];
 */
class TrieTree
{
    /**
     * 替换码
     * @var string
     */
    private $replaceCode = '*';

    /**
     * 是否替换
     * @var bool
     */
    public $is_filter = false;

    /**
     * 敏感词库集合
     * @var array
     */
    private $trieTreeMap = array();
    
    /**
     * 干扰因子集合
     * @var array
     */
    private $disturbList = array( '&', '*', '#','？', '！', '￥', '（', '）', '：', '‘', '’', '“', '”', '《', '》', '，', '…', '。', '、', 'nbsp', '】', '【', '～',' ','%','(',')' );

    public function __construct($disturbList = array())
    {
        $this->disturbList = array_merge($this->disturbList,$disturbList);
    }

    /**
     * 添加敏感词
     * @param array $txtWords
     */
    public function addWords(array $wordsList)
    {
        foreach ($wordsList as $words) {
            if(preg_match("/[\x{4e00}-\x{9fa5}]+/u", $words)){
                $words = str_replace([' '], '', trim($words));
            }
            $nowWords = &$this->trieTreeMap;
            $len = mb_strlen($words);
            for ($i = 0; $i < $len; $i++) {
                $word = mb_substr($words, $i, 1);
                //大小写不敏感
                if (preg_match("/[A-Za-z]+/", $word)) {
                    $word = strtoupper($word);
                }
                if (isset($nowWords[$word])) {
                    //存在节点
                    if ($i == ($len - 1)) {
                        $nowWords[$word]['end'] = 1;
                    }
                }else{
                    //不存在节点
                    $nowWords[$word]['end'] = $i == ($len - 1)?1:0;
                }
                $nowWords = &$nowWords[$word];
            }
        }
    }

    /**
     * 查找对应敏感词
     * @param $txt
     * @return array
     */
    public function search($txt, $hasReplace=false, &$replaceCodeList = array(),$type='')
    {
        $wordsList = array();
        $txtLength = mb_strlen($txt);
        for ($i = 0; $i < $txtLength; $i++) {
            $wordLength = $this->checkWord($txt, $i, $txtLength,$type);
            if ($wordLength > 0) {
                $words = mb_substr($txt, $i, $wordLength);
                $wordsList[] = $words;
                $hasReplace && $replaceCodeList[] = str_repeat($this->replaceCode, mb_strlen($words));
                $i += $wordLength - 1;
            }
        }
        return $wordsList;
    }

    /**
     * 过滤敏感词
     * @param $txt
     * @return mixed
     */
    public function filter($txt)
    {
        $replaceCodeList = array();
        //增加一个纯中文过滤,以过滤敏感词夹带非中文字符
        $wordsList_ch = $this->search($txt, true, $replaceCodeList,'chinese');
        $wordsList = $this->search($txt, true, $replaceCodeList);
        $wordsList = array_merge($wordsList_ch,$wordsList);
        if (empty($wordsList)) {
            $this->is_filter = false;
            return $txt;
        }else{
            $this->is_filter = true;
        }
        return str_replace($wordsList, $replaceCodeList, $txt);
    }

    /**
     * 敏感词检测
     * @param $txt
     * @param $beginIndex
     * @param $length
     * @return int
     */
    private function checkWord($txt, $beginIndex, $length,$type='')
    {
        $flag = false;
        $wordLength = 0;
        $trieTree = &$this->trieTreeMap;
        for ($i = $beginIndex; $i < $length; $i++) {
            $word = mb_substr($txt, $i, 1);
            if ($this->checkDisturb($word)) {
                $wordLength++;
                continue;
            }
            if ($type=='chinese' && !preg_match("/[\x{4e00}-\x{9fa5}]+/u",$word)) {
                $wordLength++;
                continue;
            }
            //大小写不敏感
            if (preg_match("/[A-Za-z]+/", $word)) {
                $word = strtoupper($word);
            }
            if (!isset($trieTree[$word])) {
                break;
            }
            $wordLength++;
            if (!$trieTree[$word]['end']) {
                $trieTree = &$trieTree[$word];
            } else {
                $flag = true;
            }
            //贪婪匹配
            $next = $beginIndex + $wordLength;
            $next_w = mb_substr($txt, $next, 1);
            if ($next_w && $flag && isset($trieTree[$word][$next_w])) {
                $i++;
                $wordLength++;
                $trieTree = &$trieTree[$word];
                $trieTree = &$trieTree[$next_w];
                continue;
            }
        }
        $flag || $wordLength = 0;
        return $wordLength;
    }

    /**
     * 干扰因子检测
     * @param $word
     * @return bool
     */
    private function checkDisturb($word)
    {
        return in_array($word, $this->disturbList);
    }
}

