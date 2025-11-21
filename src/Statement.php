<?php

declare(strict_types=1);

namespace Xwero\IdableQueriesRelational;

use PDO;
use PDOStatement;

class Statement extends PDOStatement
{
    protected function __construct(public readonly PDO $client)
    {}
}