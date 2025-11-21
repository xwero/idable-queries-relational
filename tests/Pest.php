<?php


use Xwero\IdableQueriesRelational\Connection;

function PdoUsers(string $query = "") : Connection
{
    $connection = new Connection(new PDO('sqlite::memory:'));

    $connection->client->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    password TEXT NOT NULL
                                 );");

    if($query !== "") {
        $connection->client->exec($query);
    }

    return $connection;
}
