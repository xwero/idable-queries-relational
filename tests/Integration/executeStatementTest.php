<?php

use Test\Identifiers\Arr;
use Test\Identifiers\Users;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\IdableParameterCollection;
use Xwero\IdableQueriesRelational\Statement;
use function Xwero\IdableQueriesRelational\buildStatement;
use function Xwero\IdableQueriesRelational\executeStatement;

test('error', function () {
    expect(executeStatement(new Error(new Exception('test'))))->toBeInstanceOf(Error::class);
});

test('query types', function (
                                string $setupQuery,
                                string $testQuery,
                                null|IdableParameterCollection|array $parameters,
                                null|callable $fetchAction,
                                mixed $result,
    ) {
    $pdo = PdoUsers($setupQuery);
    $statement = buildStatement($pdo, $testQuery, $parameters);

    expect(executeStatement($statement, $fetchAction))->toBe($result);
})->with([
    'native query no parameters' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        "SELECT email FROM users WHERE name = 'me'",
        null,
        fn(Statement $statement) => $statement->fetchColumn(),
        'email'
    ],
    'native query single parameter' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        'SELECT name,  email FROM users WHERE name = ?',
        ['me'],
        fn(Statement $statement) => $statement->fetch(),
        ['name' => 'me', 'email' => 'email' ],
    ],
    'native query multiple parameters' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf'), ('metwo', 'dfsdf', 'dfsdf')",
        'SELECT name FROM users WHERE name IN (:name1, :name2)',
        [':name1' => 'me', ':name2' => 'metwo'],
        fn(Statement $statement) => $statement->fetchAll(),
        [['name' => 'me'], ['name' => 'metwo']],
    ],
    'get idable single value' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        "SELECT ~Test\Identifiers\Users:Email FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name",
        new IdableParameterCollection()->add(Users::Name, 'me'),
        fn(Statement $statement) => $statement->fetchColumn(),
        'email'
    ],
    'get idable row' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        "SELECT ~Test\Identifiers\Users:Name, ~Test\Identifiers\Users:Email FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name",
        new IdableParameterCollection()->add(Users::Name, 'me'),
        fn(Statement $statement) => $statement->fetch(),
        ['name' => 'me', 'email' => 'email' ],
    ],
    'get idable multiple rows' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'dfsdf', 'dfsdf'), ('metwo', 'dfsdf', 'dfsdf')",
        'SELECT ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users',
        null,
        fn(Statement $statement) => $statement->fetchAll(),
        [['name' => 'me'], ['name' => 'metwo']],
    ]
]);

test('idable query with array', function () {
    $query = 'SELECT ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name IN :Test\Identifiers\Arr:Test';
    $connection = PdoUsers("INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')");
    $statement = buildStatement($connection, $query, new IdableParameterCollection()->add(Arr::Test, ['me', 'metwo']));

    expect(executeStatement($statement, fn(Statement $statement) => $statement->fetchColumn()))->toBe('me');
});