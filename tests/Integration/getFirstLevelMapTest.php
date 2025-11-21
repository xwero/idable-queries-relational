<?php

use Test\Identifiers\Users;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\IdableParameterCollection;
use Xwero\IdableQueriesCore\Map;
use function Xwero\IdableQueriesRelational\buildStatement;
use function Xwero\IdableQueriesRelational\getFirstLevelMap;

test('error', function () {
    expect(getFirstLevelMap(new Error(new Exception('test')), 'query'))->toBeInstanceOf(Error::class);
});

test('get name and email', function () {
    $pdo = PdoUsers("INSERT INTO users (name, email, password) VALUES ('me', 'dfsdf', 'dfsdf')");
    $query = 'SELECT ~Test\Identifiers\Users:Name, ~Test\Identifiers\Users:Email FROM ~Test\Identifiers\Users:Users WHERE ~Test\Identifiers\Users:Name = :Test\Identifiers\Users:Name';
    $statement = buildStatement($pdo, $query, new IdableParameterCollection()->add(Users::Name, 'me'));
    $result = getFirstLevelMap($statement, $query);

    expect($result)->toBeInstanceOf(Map::class)
        ->and($result[Users::Name])->toBe('me')
        ->and($result[Users::Email])->toBe('dfsdf');
});
