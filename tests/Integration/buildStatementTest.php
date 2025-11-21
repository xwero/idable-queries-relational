<?php

use Test\Identifiers\Arr;
use Test\Identifiers\Users;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\IdableParameterCollection;
use function Xwero\IdableQueriesRelational\buildStatement;

test('Error returns', function (string $query, array|IdableParameterCollection|null $parameters) {
    $connection = PdoUsers();

    expect(buildStatement($connection, $query, $parameters))->toBeInstanceOf(Error::class);
})->with([
    'bad placeholder in query' => [
        'SELECT * FROM ~Users:Users',
        null
    ],
    'bad placeholder in query with parameters' => [
        'SELECT * FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Users:Name',
        new IdableParameterCollection()->add(Users::Name, 'me'),
    ],
    'bad character in query' => [
        'SELECT * FROM Test\Identifiers\Users:Users',
        null
    ],
    'bad native parameters' => [
        'SELECT * FROM users',
        [[1,2]]
    ],
    'native query with IdableParameterCollection parameters' => [
        'SELECT * FROM users',
        new IdableParameterCollection()->add(Users::Name, 'me'),
    ],
    'idable query with array parameters' => [
        'SELECT * FROM ~Users:Users',
        [1]
    ]
]);

test('native statement', function (string $query, array|null $parameters, string $result) {
    $connection = PdoUsers();

    expect(buildStatement($connection, $query, $parameters)->queryString)->toBe($result);
})->with([
    'no parameters' => [
        'SELECT * FROM users',
        null,
        'SELECT * FROM users',
    ],
    'single parameter' => [
      'SELECT * FROM users WHERE id = ?',
      [1],
      'SELECT * FROM users WHERE id = ?',
    ],
    'multiple parameters' => [
        'SELECT * FROM users WHERE id IN (:id1, :id2)',
        [':id1' => 1, ':id2' => 2],
        'SELECT * FROM users WHERE id IN (:id1, :id2)',
    ]
]);


test('simple idable statement', function () {
   $query = 'SELECT ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users';
   $connection = PdoUsers();

   $statement = buildStatement($connection, $query);

   expect($statement->queryString)->toBe('SELECT name FROM users');
});

test('idable statement with parameter', function () {
    $query = 'SELECT ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name';
    $connection = PdoUsers();
    $statement = buildStatement($connection, $query, new IdableParameterCollection()->add(Users::Name, 'me'));

    expect($statement->queryString)->toBe("SELECT name FROM users WHERE name = :Test_Identifiers_Users_Name");
});

test('idable statement with array parameter', function () {
    $query = 'SELECT ~Test\Identifiers\Users:Name FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name IN :Test\Identifiers\Arr:Test';
    $connection = PdoUsers();
    $statement = buildStatement($connection, $query, new IdableParameterCollection()->add(Arr::Test , ['me', 'metwo']));

    expect($statement->queryString)->toBe("SELECT name FROM users WHERE name IN (:Test_Identifiers_Arr_Test_0,:Test_Identifiers_Arr_Test_1)");
});


