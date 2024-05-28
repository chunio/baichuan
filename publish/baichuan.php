<?php

declare(strict_types=1);

return [
    // 發佈至「工程目錄」的config/autoload/baichuan.php
    'LOG_STATUS' => 0,//是否開啟百川日誌（含：TraceHandler），值：0否，1是
    'traceHandlerStatus' => 1,//是否開啟鏈路跟蹤
    'monologHandlerJsonEncodeStatus' => 1,//是否單行，值：0否，1是
    'monologHandlerOutput' => 1,//是否輸出至控制台，值：0否，1是
];
