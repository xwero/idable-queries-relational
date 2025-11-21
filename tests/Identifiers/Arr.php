<?php

namespace Test\Identifiers;

use Xwero\IdableQueriesCore\CustomParameterIdentifier;
use Xwero\IdableQueriesCore\Identifier;

#[CustomParameterIdentifier('Xwero\IdableQueriesRelational\inArrayParameterTransformer')]
enum Arr implements Identifier
{
    case Test;
}
