<?php

use \NoahBuscher\Macaw\Macaw;


Macaw::get('/', function() {
  echo $_SERVER['REMOTE_ADDR']?:'unknown';
});
//敏感词过滤
Macaw::any('/wordsFilter', '\App\Controllers\SensitiveWord@wordsFilter');