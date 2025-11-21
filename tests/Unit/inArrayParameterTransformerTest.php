<?php

declare(strict_types=1);

use Test\Identifiers\Arr;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\PlaceholderIdentifier;
use Xwero\IdableQueriesCore\PlaceholderIdentifierCollection;
use function Xwero\IdableQueriesRelational\inArrayParameterTransformer;

test('Error return', function (PlaceholderIdentifier $phi, array $data) {
    expect(inArrayParameterTransformer($phi, $data))->toBeInstanceOf(Error::class);
})->with([
    'not enough array items' => [
        new PlaceholderIdentifier(':Arr:Test', Arr::Test),
        [1]
    ],
    'mixed array item types' => [
        new PlaceholderIdentifier(':Arr:Test', Arr::Test),
        [1, '1']
    ]
]);

test('happy path', function (PlaceholderIdentifier $phi, array $data, PlaceholderIdentifierCollection $expected) {
   $result = inArrayParameterTransformer($phi, $data);
    expect($result)->toBeInstanceOf(PlaceholderIdentifierCollection::class)
        ->and($result->getPlaceholderReplacements())->toBe($expected->getPlaceholderReplacements())
        ->and($result->getPlaceholderValuePairs())->toBe($expected->getPlaceholderValuePairs())
        ->and($result->getPlaceholdersAsText())->toBe($expected->getPlaceholdersAsText());
})->with([
    '2 items' => [
        new PlaceholderIdentifier(':Arr:Test', Arr::Test),
        [1, 2],
        new PlaceholderIdentifierCollection(
            new PlaceholderIdentifier(':Arr:Test_0', Arr::Test, 1, '(', ','),
            new PlaceholderIdentifier(':Arr:Test_1', Arr::Test, 2, suffix: ')'),
        ),
    ],
    '3 items' => [
        new PlaceholderIdentifier(':Arr:Test', Arr::Test),
        [1, 2, 3],
        new PlaceholderIdentifierCollection(
            new PlaceholderIdentifier(':Arr:Test_0', Arr::Test, 1, '(', ','),
            new PlaceholderIdentifier(':Arr:Test_1', Arr::Test, 2, suffix:','),
            new PlaceholderIdentifier(':Arr:Test_2', Arr::Test, 3, suffix:')'),
        ),
    ]
]);