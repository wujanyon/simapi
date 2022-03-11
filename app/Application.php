<?php 

namespace App;

class Application
{
	private static $instance = null;
	public $config = null;
	public $reqid = null;
	public $args = [];
	protected $db = null;
	protected $dbs = null;
	protected $redis = null;
	public $log_file_name = null;
	//初始化
	private function __construct($config)
	{
		$this->config = $config;
		//设置时区
		date_default_timezone_set($this->config['timezone']);
		//请求标识
		$this->reqid = function_exists('posix_getpid')?posix_getpid():getmypid().mt_rand(10000,99999);
		//注册异常处理
		$this->registerShutdownErrorException();
	}

	public static function getInstance($config = [])
	{
		if (!self::$instance instanceof self) {
			self::$instance = new self($config);
		}
		return self::$instance;
	}
	
	public function registerShutdownErrorException()
	{
		//错误处理
		set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []){
			if(in_array($level,[E_NOTICE,E_WARNING])){
                $this->log($level.' '.$message.' in '. $file .':'.$line,'error');
                return;
            }
			self::report( new \ErrorException($message, 0, $level, $file, $line) );
		});
		//异常处理
		set_exception_handler(function ($exception){
			self::report($exception);
		});
		//结束处理
		register_shutdown_function(function (){
			if (!is_null($error = error_get_last()) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])){
				self::report( new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']) );
			}
			exit;
		});
	}
	public function report($exception)
	{
		$message = $exception->getCode().' '.$exception->getMessage().' in '.$exception->getFile().':'.$exception->getLine();
		$this->log($message,'error');
		if($exception instanceof \WebGeeker\Validation\ValidationException){
			$this->response(400,$this->config['debug']?$message:'param error:'.$exception->getMessage());
		}elseif ($exception instanceof \App\RepException) {
			$this->response($exception->getCode(), $this->config['debug']?$message:$exception->getMessage());
		}elseif ($exception instanceof \ErrorException) {
			header($_SERVER['SERVER_PROTOCOL'].' 500 Unknown Error');
			$this->response(500,$this->config['debug']?$message:'Unknown Error');
		}elseif ($exception instanceof \PDOException) {
			$this->log($this->db?$this->db->log():$exception->getMessage(),'error');
			header($_SERVER['SERVER_PROTOCOL'].' 500 Unknown Error');
			$this->response(500,$this->config['debug']?$message:'Unknown Error');
		}elseif ($exception instanceof NotFoundException) {
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
			$this->response(404,'NotFound');
		}else{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
			$this->response(404,$this->config['debug']?$message:'Not Found And Unknown Error');
		}
	}

	/**
	 * 日志记录
	 */
	public function log($message,$level='info')
	{
		if ($this->config['log_level']!='debug' && $level=='debug') return;
		$message = is_array($message)?json_encode($message,320):$message;
		$message = '['.date('Y-m-d H:i:s').' '.$this->reqid.']['.$level.'] ' . $message . PHP_EOL;
		$file = RUNTIME_PATH.'log/'.date('Ym').'/'.($this->log_file_name?:'app').date('Ymd').'.txt';
		is_dir(dirname($file)) || mkdir(dirname($file), 0755,true);
		file_put_contents($file, $message, FILE_APPEND);
	}

    /**
	 * 默认返回
	*/
    public function response($code, $codemsg = '', $data = [], $type='json')
    {
        if (is_array($code)) {
            $codemsg = $code[1];
            $code = $code[0];
        }
        $this->log('response:'.var_export(json_encode([$code,$codemsg,$data,$type],320),true));
        switch ($type) {
            case 'json':
                $res = $data?['code'=>$code,'codemsg'=>$codemsg,'data'=>$data]:['code'=>$code,'codemsg'=>$codemsg];
                header("Content-Type: application/json;charset=utf8");
                echo json_encode($res,320);
                exit;
                break;
            case 'html':
                echo $code;
                exit;
                break;
        }
    }

    /**
	 * 默认返回
	*/
    public function json($data,$option=320)
    {
        $str_json = is_array($data)?json_encode($data,$option):$data;
        $this->log('response:'.var_export($str_json,true));
        header("Content-Type: application/json;charset=utf8");
        exit($str_json);
    }

    /**
	 * 解析路由
	 */
	public function run()
	{
		//记录日志
        $this->log( getIp().' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_URI']);
		$this->middleware();
		// \NoahBuscher\Macaw\Macaw::error(function (){throw new NotFoundException('NotFoundException',404);});
		// \NoahBuscher\Macaw\Macaw::dispatch();
	}

	/**
	 * 中间件
	 */
	public function middleware()
	{
		$middleware = $this->config['middleware']?:[];
		$carry = function($dispatch, $middleware)
		{
			return function() use ($dispatch, $middleware) {return $middleware::handle($dispatch);};
		};
		$dispatch = function()
		{
			\NoahBuscher\Macaw\Macaw::error(function (){throw new NotFoundException('NotFoundException',404);});
			\NoahBuscher\Macaw\Macaw::dispatch();
		};
		call_user_func(array_reduce(array_reverse($middleware), $carry, $dispatch));
	}
	/**
	 * redis
	 */
	public function getRedis()
	{
		if(!$this->redis instanceof \Redis){
			$this->redis = new \Redis();
			$this->redis->connect($this->config['redis']['host'], $this->config['redis']['port']);
			$this->redis->auth($this->config['redis']['auth']);
            $this->redis->select((int)$this->config['redis']['seldb']);
		}
		return $this->redis;
	}
	/**
	 * db
	 */
	public function getdb($config = 'default')
	{
		if(!isset($this->dbs[$config]) || !$this->dbs[$config] instanceof \Medoo\Medoo){
			$this->dbs[$config] = new \Medoo\Medoo($this->config['database'][$config]);
		}
		$this->db = $this->dbs[$config];
		return $this->db;
	}
}

class NotFoundException extends \Exception{}
class ValidException extends \Exception{}
class RepException extends \Exception{}
