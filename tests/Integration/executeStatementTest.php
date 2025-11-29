<?php

use Test\Identifiers\Arr;
use Test\Identifiers\Users;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\IdableParameterCollection;
use Xwero\IdableQueriesRelational\QueryReturnConfigRelational;
use function Xwero\IdableQueriesRelational\buildStatement;
use function Xwero\IdableQueriesRelational\executeStatement;

test('error', function () {
    expect(executeStatement(new Error(new Exception('test'))))->toBeInstanceOf(Error::class);
});

test('query types', function (
                                string                               $setupQuery,
                                string                               $testQuery,
                                null|IdableParameterCollection|array $parameters,
                                null|QueryReturnConfigRelational     $returnConfig,
                                mixed                                $result,
    ) {
    $pdo = PdoUsers($setupQuery);
    $statement = buildStatement($pdo, $testQuery, $parameters);

    expect(executeStatement($statement, $returnConfig))->toBe($result);
})->with([
    'native query no parameters' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        "SELECT name, email FROM users WHERE name = 'me'",
        null,
        null,
        ['name' => 'me', 'email' => 'email' ],
    ],
    'native query single parameter' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        'SELECT name,  email FROM users WHERE name = ?',
        ['me'],
        null,
        ['name' => 'me', 'email' => 'email' ],
    ],
    'native query multiple parameters' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf'), ('metwo', 'dfsdf', 'dfsdf')",
        'SELECT name FROM users WHERE name IN (:name1, :name2)',
        [':name1' => 'me', ':name2' => 'metwo'],
        null,
        [['name' => 'me'], ['name' => 'metwo']],
    ],
    'get idable single value' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        "SELECT ~Test\Identifiers\Users:Email FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name",
        new IdableParameterCollection()->add(Users::Name, 'me'),
        null,
        'email',
    ],
    'get idable single value with return config' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        "SELECT ~Test\Identifiers\Users:Email, ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name",
        new IdableParameterCollection()->add(Users::Name, 'me'),
        new QueryReturnConfigRelational(returnValue: true),
        'email',
    ],
    'get idable row' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')",
        "SELECT ~Test\Identifiers\Users:Name, ~Test\Identifiers\Users:Email FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name",
        new IdableParameterCollection()->add(Users::Name, 'me'),
        null,
        ['name' => 'me', 'email' => 'email' ],
    ],
    'get idable row with return config' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'dfsdf', 'dfsdf'), ('metwo', 'dfsdf', 'dfsdf')",
        'SELECT ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name',
        IdableParameterCollection::createWithIdableParameter(Users::Name, 'me'),
        new QueryReturnConfigRelational(returnRow: true),
        ['name' => 'me'],
    ],
    'get idable multiple rows' => [
        "INSERT INTO users (name, email, password) VALUES ('me', 'dfsdf', 'dfsdf'), ('metwo', 'dfsdf', 'dfsdf')",
        'SELECT ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users',
        null,
        null,
        [['name' => 'me'], ['name' => 'metwo']],
    ]
]);

test('idable query with array', function () {
    $query = 'SELECT ~Test\Identifiers\Users:Name, ~Test\Identifiers\Users:Email FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name IN :Test\Identifiers\Arr:Test';
    $connection = PdoUsers("INSERT INTO users (name, email, password) VALUES ('me', 'email', 'dfsdf')");
    $statement = buildStatement($connection, $query, new IdableParameterCollection()->add(Arr::Test, ['me', 'metwo']));

    expect(executeStatement($statement))->toBe(['name' => 'me', 'email' => 'email']);
});