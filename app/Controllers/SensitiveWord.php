<?php

namespace App\Controllers;

use App\Code;
use WebGeeker\Validation\Validation;
use App\lib\TrieTree;

class SensitiveWord extends Controller
{
    /**
     * @api {post} /wordFilter 敏感词过滤
     * @apiName wordFilter
     * @apiGroup 客户端
     * @apiParam {string} appkey  应用key
     * @apiParam {string} contents  检测过滤字符串
     *
     * @apiSuccess {int} code 状态吗 2000 成功 其他失败
     * @apiSuccess {string} codemsg 状态描述
     * @apiSuccess {object} data 返回数据
     * @apiSuccess {string} contents 过滤后的字符串，未过滤返回原字符串
     * @apiSuccessExample {json} 成功的返回:
     *   {
     *       "code": 2000,
     *       "codemsg": "登录成功",
     *       "data": {
     *           "contents": "ABC***",
     *       }
     *   }
     * @apiErrorExample {json} 失败返回:
     *{
     *    "code": 4000,
     *    "codemsg": "失败",
     *}
     */
    public function wordsFilter()
    {
        //接收数据
        $params = $_REQUEST;
        if(empty($params)){
           $params = json_decode(file_get_contents('php://input'),true);
        }
        $this->log(__FUNCTION__.' params:'.json_encode($params));
        //检查参数
        Validation::validate((array)$params, [
            "contents" => "Required|Str",
        ]);
        $illegal_words = file_get_contents('illegal_words.txt');
        if(empty($illegal_words)){
            $this->log(__FUNCTION__.' illegal_words empty:'.json_encode($illegal_words));
            $this->response(4000,'illegal_words empty');
        }
        $illegal_words = explode(',',$illegal_words );
        //干扰字符
        $disturbList = ['&', '*',' '];
        $wordObj = new TrieTree($disturbList);
        $wordObj->addWords($illegal_words);
        //检测过滤
        $contents = $wordObj->filter($params['contents']);
        //返回数据
        $ret = [
            'contents' => $contents,
        ];
        $this->response(2000,'成功',$ret);
    }
}