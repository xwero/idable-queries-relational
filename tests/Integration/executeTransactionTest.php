<?php

declare(strict_types=1);

use Xwero\IdableQueriesCore\Error;
use function Xwero\IdableQueriesRelational\executeTransaction;

test('return Error', function () {
   expect(executeTransaction())->toBeInstanceOf(Error::class);
});