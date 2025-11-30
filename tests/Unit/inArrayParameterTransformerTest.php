<?php

declare(strict_types=1);

use Test\Identifiers\Arr;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\Placeholder;
use Xwero\IdableQueriesCore\PlaceholderCollection;
use function Xwero\IdableQueriesRelational\inArrayParameterTransformer;

test('Error return', function (Placeholder $phi, array $data) {
    expect(inArrayParameterTransformer($phi, $data))->toBeInstanceOf(Error::class);
})->with([
    'not enough array items' => [
        new Placeholder(':Arr:Test', Arr::Test),
        [1]
    ],
    'mixed array item types' => [
        new Placeholder(':Arr:Test', Arr::Test),
        [1, '1']
    ]
]);

test('happy path', function (Placeholder $phi, array $data, PlaceholderCollection $expected) {
   $result = inArrayParameterTransformer($phi, $data);
    expect($result)->toBeInstanceOf(PlaceholderCollection::class)
        ->and($result->getPlaceholderReplacements())->toBe($expected->getPlaceholderReplacements())
        ->and($result->getPlaceholderValuePairs())->toBe($expected->getPlaceholderValuePairs())
        ->and($result->getPlaceholdersAsText())->toBe($expected->getPlaceholdersAsText());
})->with([
    '2 items' => [
        new Placeholder(':Arr:Test', Arr::Test),
        [1, 2],
        new PlaceholderCollection(
            new Placeholder(':Arr:Test_0', Arr::Test, 1, '(', ','),
            new Placeholder(':Arr:Test_1', Arr::Test, 2, suffix: ')'),
        ),
    ],
    '3 items' => [
        new Placeholder(':Arr:Test', Arr::Test),
        [1, 2, 3],
        new PlaceholderCollection(
            new Placeholder(':Arr:Test_0', Arr::Test, 1, '(', ','),
            new Placeholder(':Arr:Test_1', Arr::Test, 2, suffix:','),
            new Placeholder(':Arr:Test_2', Arr::Test, 3, suffix:')'),
        ),
    ]
]);