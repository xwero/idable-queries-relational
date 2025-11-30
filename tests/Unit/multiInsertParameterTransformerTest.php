<?php

declare(strict_types=1);

use Test\Identifiers\MultiInsert;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\Placeholder;
use Xwero\IdableQueriesCore\PlaceholderCollection;
use function Xwero\IdableQueriesRelational\multiInsertParameterTransformer;

test('Error returns', function (Placeholder $phi, array $data) {
    expect(multiInsertParameterTransformer($phi, $data))->toBeInstanceOf(Error::class);
})->with([
    'not enough array items' => [
        new Placeholder(':MultiInsert:Test', MultiInsert::Test),
        [1]
    ],
    'array items are not all arrays' => [
        new Placeholder(':MultiInsert:Test', MultiInsert::Test),
        [[1], 2]
    ],
    'array items are not all of the same length' => [
        new Placeholder(':MultiInsert:Test', MultiInsert::Test),
        [[1], [2, 3]]
    ]
]);

test('happy path', function (Placeholder $phi, array $data, PlaceholderCollection $expected) {
  $result = multiInsertParameterTransformer($phi, $data);
  expect($result)->toBeInstanceOf(PlaceholderCollection::class)
      ->and($result->getPlaceholderReplacements())->toBe($expected->getPlaceholderReplacements())
      ->and($result->getPlaceholderValuePairs())->toBe($expected->getPlaceholderValuePairs())
      ->and($result->getPlaceholdersAsText())->toBe($expected->getPlaceholdersAsText());
})->with([
    '2 rows' => [
        new Placeholder(':MultiInsert:Test', MultiInsert::Test),
        [[1, 2], [3, 4]],
        new PlaceholderCollection(
            new Placeholder(':MultiInsert:Test_0_0', MultiInsert::Test, 1, '(', ','),
            new Placeholder(':MultiInsert:Test_0_1', MultiInsert::Test, 2, suffix: '),'),
            new Placeholder(':MultiInsert:Test_1_0', MultiInsert::Test, 3, '(', ','),
            new Placeholder(':MultiInsert:Test_1_1', MultiInsert::Test, 4, suffix: ')'),
        )
    ],
]);