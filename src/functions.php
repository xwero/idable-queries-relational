<?php

declare(strict_types=1);

namespace Xwero\IdableQueriesRelational;

use Closure;
use Exception;
use InvalidArgumentException;
use LengthException;
use PDO;
use PDOException;
use Xwero\IdableQueriesCore\AliasCollection;
use Xwero\IdableQueriesCore\BaseNamespaceCollection;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\IdableParameterCollection;
use Xwero\IdableQueriesCore\Map;
use Xwero\IdableQueriesCore\MapCollection;
use Xwero\IdableQueriesCore\PlaceholderIdentifier;
use Xwero\IdableQueriesCore\PlaceholderIdentifierCollection;
use function Xwero\IdableQueriesCore\createMapFromFirstLevelResults;
use function Xwero\IdableQueriesCore\createMapFromSecondLevelResults;
use function Xwero\IdableQueriesCore\isIdableQuery;
use function Xwero\IdableQueriesCore\replaceIdentifiersInQuery;
use function Xwero\IdableQueriesCore\replaceParametersInQuery;

function buildStatement(
    RelationalConnection                                     $conn,
    string                                                   $query,
    IdableParameterCollection|array|null $parameters = null,
    BaseNamespaceCollection|null                             $namespaces = null,
): Statement|Error
{
    $isIdableQuery = isIdableQuery($query);

    if($isIdableQuery && is_array($parameters)) {
        return new Error(new InvalidArgumentException("The parameters argument only accepts an IdableParameterCollection instance."));
    }

    if( ! $isIdableQuery && $parameters instanceof IdableParameterCollection) {
        return new Error(new InvalidArgumentException("The parameters argument only accepts an array."));
    }

    if($isIdableQuery) {
        $query = replaceIdentifiersInQuery($query, $namespaces);
    }

    if($query instanceof Error) {
        return $query;
    }
    // Identifiers with the CustomIdentifier attribute can change the original query.
    if ($parameters instanceof IdableParameterCollection) {
        $queryPlaceholderIdentifierCollection = replaceParametersInQuery($query, $parameters, $namespaces, PDOfyParameterPlaceholder(...));

        if($queryPlaceholderIdentifierCollection instanceof Error) {
            return $queryPlaceholderIdentifierCollection;
        }

        $query = $queryPlaceholderIdentifierCollection->query;
    }

    try {
        /** @var Statement $statement */
        $statement = $conn->client->prepare($query);
    } catch (PDOException $e) {
        return new Error($e);
    }

    if($parameters === null) {
        return $statement;
    }
    // the same code is used twice in the function
    $bindParameters = function(Statement $statement, array $parameters): Statement|Error {
        foreach ($parameters as $placeholder => $value) {
            // the placeholder can be an array with integer keys.
            // In that case the value needs to start from 1 to bind the value.
            $param = is_int($placeholder) ? $placeholder + 1 : $placeholder;

            $status = $statement->bindParameter($param, $value);

            if($status instanceof Error) {
                return $status;
            }
        }

        return $statement;
    };

    if(is_array($parameters)){
        try {
            return $bindParameters($statement, $parameters);
        } catch (Exception $e) {
            return new Error($e);
        }
    }

    try {
        $placeholderReplacements = $queryPlaceholderIdentifierCollection->parameters->getPlaceholderValuePairs(PDOfyParameterPlaceholder(...));

        return $bindParameters($statement, $placeholderReplacements);
    } catch (Exception $e) {
        return new Error($e);
    }
}

function executeStatement(Statement|Error $statement, QueryReturnConfigRelational|null $config = null) : mixed
{
    if ($statement instanceof Error) {
        return $statement;
    }

    return $statement->run($config);
}

function executeTransaction(Statement ...$statements): Error|true
{
    if(count($statements) === 0) {
        return new Error(new InvalidArgumentException('A transaction must have at least one statement.'));
    }

    try {
        $statements[0]->client->beginTransaction();

        foreach ($statements as $statement) {
            $statement->execute();
        }

        $statements[0]->client->commit();

        return true;
    } catch (Exception $e) {
        $statements[0]->client->rollBack();

        return new Error($e);
    }
}

function getFirstLevelMap(
    Statement|Error $statement,
    string $query,
    AliasCollection|null $aliases = null,
) : Map|Error
{
    if ($statement instanceof Error) {
        return $statement;
    }

    $data = executeStatement($statement, new QueryReturnConfigRelational(returnRow: true));

    if($data instanceof Error) {
        return $data;
    }

    return createMapFromFirstLevelResults($data, $query, $aliases);
}

function getSecondLevelMapCollection(
    Statement|Error $statement,
    string $query,
    AliasCollection|null $aliases = null,
) : MapCollection|Error
{
    if ($statement instanceof Error) {
        return $statement;
    }

    $data = executeStatement($statement, new QueryReturnConfigRelational(returnRows: true));

    if($data instanceof Error) {
        return $data;
    }

    return createMapFromSecondLevelResults($data, $query, $aliases);
}

function inArrayParameterTransformer(
    PlaceholderIdentifier $phi,
    array $value,
    string $placeholderSeparator = '_'
): PlaceholderIdentifierCollection|Error
{
    if (count($value) < 2) {
        return new Error(new InvalidArgumentException('The parameter array must have at least 2 elements.'));
    }

    if(count(array_unique(array_map(gettype(...), $value))) != 1) {
        return new Error(new InvalidArgumentException('The parameter array items all need to be of the same type.'));
    }

    $collection = new PlaceholderIdentifierCollection();
    $count = count($value) - 1;
    $counter = 0;

    foreach ($value as $v) {
        $placeholder = $phi->placeholder . $placeholderSeparator . $counter;

        if ($counter == 0) {
            $collection->add(
                $placeholder,
                $phi->identifier,
                value: $v,
                prefix: '(',
                suffix: ',',
            );
            $counter++;
            continue;
        }

        if ($counter == $count) {
            $collection->add(
                $placeholder,
                $phi->identifier,
                value: $v,
                suffix: ')',
            );
            $counter++;
            continue;
        }

        $collection->add(
            $placeholder,
            $phi->identifier,
            value: $v,
            suffix: ',',
        );
        $counter++;
    }

    return $collection;
}

function multiInsertParameterTransformer(
    PlaceholderIdentifier $phi,
    array $value,
    string $placeholderSeparator = '_'
): PlaceholderIdentifierCollection|Error
{
    if (count($value) < 2) {
        return new Error(new InvalidArgumentException('The value array must have at least 2 elements.'));
    }

    if(array_all($value, fn($i) => is_array($i)) === false) {
        return new Error(new InvalidArgumentException('The value array items all have to be of the array type.'));
    }

    if(count(array_unique(array_map(fn($i) => count($i), $value))) != 1) {
        return new Error(new LengthException('The value array items all have need have the same length.'));
    }

    $collection = new PlaceholderIdentifierCollection();
    $firstLevelCount = count($value) - 1;
    $firstLevelCounter = 0;

    foreach ($value as $values) {
        $secondLevelCount = count($values) - 1;
        $secondLevelCounter = 0;

        foreach ($values as $v) {
            $placeholder = $phi->placeholder . $placeholderSeparator . $firstLevelCounter . $placeholderSeparator . $secondLevelCounter;

            if($secondLevelCounter == 0) {
                $collection->add(
                    $placeholder,
                    $phi->identifier,
                    value: $v,
                    prefix: '(',
                    suffix: ',',
                );
                $secondLevelCounter++;
                continue;
            }

            if($secondLevelCounter == $secondLevelCount) {
                $collection->add(
                        $placeholder,
                        $phi->identifier,
                        value: $v,
                        suffix: $firstLevelCounter < $firstLevelCount ? '),' : ')',
                );
                $secondLevelCounter++;
                continue;
            }

            $collection->add(
                    $placeholder,
                    $phi->identifier,
                    value: $v,
                    suffix: ',',
            );
            $secondLevelCounter++;
        }

        $firstLevelCounter++;
    }

    return $collection;
}

function PDOfyParameterPlaceholder(string $placeholder): string
{
    if (str_contains($placeholder, '\\')) {
        $placeholder = str_replace('\\', '_', $placeholder);
    }

    return preg_replace('/([a-z0-9]):([A-Z])/', '$1_$2', $placeholder);
}
