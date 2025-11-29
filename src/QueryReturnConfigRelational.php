<?php

declare(strict_types=1);

namespace Xwero\IdableQueriesRelational;

use Xwero\IdableQueriesCore\QueryReturnConfig;

final readonly class QueryReturnConfigRelational implements QueryReturnConfig
{
    public function __construct(
        public bool $trueOnSuccess = true,
        public bool $returnInsertId = false,
        public bool $returnRows = false,
        public bool $returnRow = false,
        public bool $returnValue = false,
    )
    {}
}