<?php

function app($config = null)
{
    return \App\Application::getInstance($config);
}
//获取IP
function getIp()
{
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
// curl请求
function curl_request($url, $data='', $method='POST', $headers=array(),$timeout = 3)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_TIMEOUT,$timeout);

    if(strcasecmp($method,'POST')==0){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }else{
        $str = http_build_query($data);
        if(strpos($url, '?')!==false)   $url .= '&'.$str;
        else                $url .= '?'.$str;
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    if(strpos($url, 'https')!==false){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 检查证书中是否设置域名（为0也可以，就是连域名存在与否都不验证了）
    }

    if(is_array($headers) && !empty($headers)){
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $result = curl_exec($ch);
    if (curl_errno($ch)){
        app()->log('curl_request fail:'.$url.' '.var_export(curl_error($ch),true),'error');
    }
    curl_close($ch);

    return $result;
}

//发送socket请求
function sendSocket($message, $address, $port, $timeout=3)
{
    $socket = fsockopen($address,$port, $errno, $errstr,$timeout);
    if (!$socket) {
        app()->log("fsockopen connect failed: $errno-$errstr",'error');
        return false;
    }
    stream_set_timeout($socket,$timeout);
    $buff = @fwrite($socket, $message, strlen($message))?@fread($socket, 8192):false;
    fclose($socket);
    return $buff;
}
/**
 * 获取随机字符串
 * @param  [int] $len 长度
 * @return [string]   字符串
 */
function getRandStr($len)
{
    $str = 'abcdefghijkmnpqrstuvwxyz0123456789ABCDEFGHIGKLMNPQRSTUVWXYZ';
    $rand = '';
    for ($i = 0; $i < $len - 1; $i ++) {
        $rand .= $str[mt_rand(0, strlen($str) - 1)];
    }
   return $rand;
}