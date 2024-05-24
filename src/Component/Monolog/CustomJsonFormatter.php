<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Monolog;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class CustomJsonFormatter extends JsonFormatter
{

    /** @var self::BATCH_MODE_* */
    protected int $batchMode;
    /** @var bool */
    protected bool $appendNewline;
    /** @var bool */
    protected bool $ignoreEmptyContextAndExtra;
    /** @var bool */
    protected bool $includeStacktraces = false;

    /**
     * @param self::BATCH_MODE_* $batchMode
     */
    public function __construct(int $batchMode = self::BATCH_MODE_JSON, bool $appendNewline = true, bool $ignoreEmptyContextAndExtra = false, bool $includeStacktraces = false)
    {
        $this->batchMode = $batchMode;
        $this->appendNewline = $appendNewline;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        $this->includeStacktraces = $includeStacktraces;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    //DEBUG_BABEL:待實例（$record array/hyperf2.2 >> LogRecord/hyperf3.1）
    public function format(LogRecord $record): string
    {
        return $record['message'];
    }

}
