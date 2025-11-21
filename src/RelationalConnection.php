<?php

namespace Xwero\IdableQueriesRelational;

use PDO;

interface RelationalConnection
{
    function __construct(PDO $pdo);
}