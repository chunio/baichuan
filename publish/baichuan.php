<?php

declare(strict_types=1);

return [
    // handler[START]
    // 發佈至「工程目錄」的config/autoload/baichuan.php
    'traceHandlerStatus' => 1,//是否開啟鏈路跟蹤
    'traceHandlerSync2mongodb' => 0,//是否將輸出同步至mongodb
    'monologHandlerJsonEncodeStatus' => 1,//是否單行，值：0否，1是
    'monologHandlerOutput' => 1,//是否輸出至控制台，值：0否，1是
];