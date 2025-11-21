<?php

declare(strict_types=1);

use Test\Identifiers\Users;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\Map;
use Xwero\IdableQueriesCore\MapCollection;
use function Xwero\IdableQueriesRelational\buildStatement;
use function Xwero\IdableQueriesRelational\getSecondLevelMapCollection;

test('error', function () {
    expect(getSecondLevelMapCollection(new Error(new Exception('test')), 'query'))->toBeInstanceOf(Error::class);
});

test('query', function () {
    $pdo = PdoUsers("INSERT INTO users (name, email, password) VALUES ('me', 'dfsdf', 'dfsdf'), ('metwo', 'dfsdf', 'dfsdf')");
    $query = 'SELECT ~Test\Identifiers\Users:Name, ~Test\Identifiers\Users:Email FROM ~Test\Identifiers\Users:Users';
    $statement = buildStatement($pdo, $query);
    $result = getSecondLevelMapCollection($statement, $query);
    $maps = $result->getAll();
    /** @var Map $firstMap */
    $firstMap = $maps[0];

    expect($result)->toBeInstanceOf(MapCollection::class)
        ->and($maps)->toHaveCount(2)
        ->and($firstMap)->toBeInstanceOf(Map::class)
        ->and($firstMap[Users::Name])->toBe('me');
});