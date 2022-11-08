<?php 

namespace App\Controllers;

use Medoo\Medoo;
use App\Application;
class Controller
{
	protected $app = null;
	protected $_db = null;

	public function __construct()
	{
		$this->app = Application::getInstance();
		//初始化DB
		$this->_db =  $this->app->getdb();
	}

    /**
    * 生成token
    */
    protected function getToken($data, $key='')
    {
        ksort($data);
        $data['timestamp'] = time();
        $data['sign'] = md5(json_encode($data).$key);
        return urlencode( base64_encode( json_encode($data) ) );
    }
    /**
    *验证token是否有效 
    */
    protected function verifyToken($token, $key='')
    {
        $payload = json_decode( base64_decode( urldecode($token) ), true);
        if (!is_array($payload) || !isset($payload['sign']))
        return false;
        $sign = $payload['sign'];
        unset($payload['sign']);
        ksort($payload);
        //签名验证
        return md5(json_encode($payload).$key)== $sign ? $payload : false;
    }

    /**
     * 默认返回
    */
    protected function response($code, $codemsg = '', $data = [], $type='json')
    {
        $this->app->response($code, $codemsg, $data, $type);
    }
    /**
     * 返回字符串
    */
    protected function html($codemsg='')
    {
        $this->app->response($codemsg, '', [], 'html');
    }
    /**
     * 返回json字符串
    */
    protected function json($data,$option=320)
    {
        $this->app->json($data,$option);
    }
    /**
     * 默认日志
    */
    protected function log($message,$level='info')
    {
        $this->app->log($message,$level);
    }

    /**
     * 默认请求
    */
    protected function curl_request($url, $data='', $method='POST', $headers=array(),$timeout = 3)
    {
        return curl_request($url, $data, $method, $headers,$timeout);
    }

    /**
     * 发送TCP请求
    */
    protected function sendSocket($message, $address, $port='80')
    {
        return  sendSocket($message, $address, $port);
    }

    /**
     * reids 锁
     */
    protected function ttlLock($key, $value, $ttl=1)
    {
       return app()->getRedis()->set($key,$value, ['NX', 'EX' =>intval($ttl)]);
    }
    /**
     * reids 解锁
     */
    protected function unLock($key, $value)
    {
        $script = '
        if redis.call("get",KEYS[1]) == ARGV[1] then
            return redis.call("del",KEYS[1])
        else
            return 0
        end';
       return app()->getRedis()->eval($script, [$key, $value],1);
    }
}