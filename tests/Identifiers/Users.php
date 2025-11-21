<?php

namespace Test\Identifiers;


use Xwero\IdableQueriesCore\Identifier;

enum Users implements Identifier
{
    case Users;
    case Id;
    case Name;
    case Email;
}
