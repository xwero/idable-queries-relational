<?php

declare(strict_types=1);

namespace Xwero\IdableQueriesRelational;

use PDO;
use PDOStatement;
use Xwero\IdableQueriesCore\Statement as StatementInterface;

class Statement extends PDOStatement implements StatementInterface
{
    protected function __construct(public readonly PDO $client)
    {}
}