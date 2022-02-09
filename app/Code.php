<?php

namespace App;

class  Code {
    const SUCCESS = 2000;//成功
    const FAILURE = 4000;//失败
    const NO_AUTH = 4001;//未登录
    const ARR_SUCCESS = [2000,'成功']; //成功
    const ARR_FAILURE = [4000,'失败']; //失败
    const ARR_PARAM_ERR = [1000,'参数错误']; //失败
    const ARR_NO_SERVICE = [4004,'服务不存在']; //失败
}