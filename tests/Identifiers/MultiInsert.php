<?php

namespace Test\Identifiers;

use Xwero\IdableQueriesCore\CustomParameterIdentifier;
use Xwero\IdableQueriesCore\Identifier;

#[CustomParameterIdentifier('Xwero\IdableQueriesRelational\multiInsertParameterTransformer')]
enum MultiInsert implements Identifier
{
    case Test;
}
