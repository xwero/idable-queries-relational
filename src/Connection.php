<?php

declare(strict_types=1);

namespace Xwero\IdableQueriesRelational;

use PDO;

final readonly class Connection implements RelationalConnection
{

    public function __construct(public PDO $client)
    {
        $client->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $client->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $client->setAttribute(PDO::ATTR_STATEMENT_CLASS, [Statement::class, [$client]]);
    }
}