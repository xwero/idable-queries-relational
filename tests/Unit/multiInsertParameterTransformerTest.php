<?php

declare(strict_types=1);

use Test\Identifiers\MultiInsert;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\PlaceholderIdentifier;
use Xwero\IdableQueriesCore\PlaceholderIdentifierCollection;
use function Xwero\IdableQueriesRelational\multiInsertParameterTransformer;

test('Error returns', function (PlaceholderIdentifier $phi, array $data) {
    expect(multiInsertParameterTransformer($phi, $data))->toBeInstanceOf(Error::class);
})->with([
    'not enough array items' => [
        new PlaceholderIdentifier(':MultiInsert:Test', MultiInsert::Test),
        [1]
    ],
    'array items are not all arrays' => [
        new PlaceholderIdentifier(':MultiInsert:Test', MultiInsert::Test),
        [[1], 2]
    ],
    'array items are not all of the same length' => [
        new PlaceholderIdentifier(':MultiInsert:Test', MultiInsert::Test),
        [[1], [2, 3]]
    ]
]);

test('happy path', function (PlaceholderIdentifier $phi, array $data, PlaceholderIdentifierCollection $expected) {
  $result = multiInsertParameterTransformer($phi, $data);
  expect($result)->toBeInstanceOf(PlaceholderIdentifierCollection::class)
      ->and($result->getPlaceholderReplacements())->toBe($expected->getPlaceholderReplacements())
      ->and($result->getPlaceholderValuePairs())->toBe($expected->getPlaceholderValuePairs())
      ->and($result->getPlaceholdersAsText())->toBe($expected->getPlaceholdersAsText());
})->with([
    '2 rows' => [
        new PlaceholderIdentifier(':MultiInsert:Test', MultiInsert::Test),
        [[1, 2], [3, 4]],
        new PlaceholderIdentifierCollection(
            new PlaceholderIdentifier(':MultiInsert:Test_0_0', MultiInsert::Test, 1, '(', ','),
            new PlaceholderIdentifier(':MultiInsert:Test_0_1', MultiInsert::Test, 2, suffix: '),'),
            new PlaceholderIdentifier(':MultiInsert:Test_1_0', MultiInsert::Test, 3, '(', ','),
            new PlaceholderIdentifier(':MultiInsert:Test_1_1', MultiInsert::Test, 4, suffix: ')'),
        )
    ],
]);