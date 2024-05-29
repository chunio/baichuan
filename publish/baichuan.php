<?php

declare(strict_types=1);

return [
    // 發佈至「工程目錄」的config/autoload/baichuan.php
    'LOG_STATUS' => 0,//TODO:[百川日誌]是否開啟（未完全實現「開啟/關閉」控制（返回值非布爾類型的仍會觸發，待完善）：TraceHandlerAspect >> TraceHandler），值：0否，1是
    'traceHandlerStatus' => 1,//是否開啟鏈路跟蹤
    'monologHandlerJsonEncodeStatus' => 1,//是否單行，值：0否，1是
    'monologHandlerOutput' => 1,//是否輸出至控制台，值：0否，1是
];