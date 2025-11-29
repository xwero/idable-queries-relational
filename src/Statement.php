<?php

declare(strict_types=1);

namespace Xwero\IdableQueriesRelational;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use Xwero\IdableQueriesCore\Error;
use Xwero\IdableQueriesCore\QueryReturnConfig;
use Xwero\IdableQueriesCore\Statement as StatementInterface;

class Statement extends PDOStatement implements StatementInterface
{
    protected function __construct(public readonly PDO $client)
    {}

    /**
     * @inheritDoc
     */
    public function bindParameter(int|string $param, mixed $value): true|Error
    {
        $type = match (true) {
            is_string($value) || is_float($value) => PDO::PARAM_STR,
            is_bool($value) => PDO::PARAM_BOOL,
            is_int($value) => PDO::PARAM_INT,
            is_null($value) => PDO::PARAM_NULL,
            is_resource($value) => PDO::PARAM_LOB,
            default => false,
        };

        if($type === false) {
            return new Error(new InvalidArgumentException('The value is not a valid parameter type: '. gettype($value)));
        }

        $this->bindParam($param, $value, $type);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function run(QueryReturnConfig|null $config = null): mixed
    {
        if(count(explode(' ', $this->queryString)) < 2) {
            return new Error(new InvalidArgumentException('An executable query string needs at least two parts, like SELECT 1.'));
        }

        if($config !== null && ! $config instanceof QueryReturnConfigRelational) {
            return new Error(new InvalidArgumentException("Only an QueryReturnConfigRelational instance can be added."));
        }

        try {
            $status = $this->execute();

            $sqlType = strtoupper(explode(' ', $this->queryString, 2)[0]);

            if(in_array($sqlType, ['UPDATE', 'DELETE'])) {
                return $config->trueOnSuccess ? true : $status;
            }

            if($config instanceof QueryReturnConfigRelational) {
                if ($config->returnInsertId) {
                    return $this->client->lastInsertId();
                }

                if ($config->returnRow) {
                    return $this->fetch();
                }

                if ($config->returnValue) {
                    return $this->fetchColumn();
                }
            }

            $return = $this->fetchAll();

            if($config instanceof QueryReturnConfigRelational && $config->returnRows) {
                return $return;
            }

            $rows = count($return);

            if($rows > 1) {
                return $return;
            }

            if($rows == 0) {
                return $config->trueOnSuccess ? true : $status;
            }

            $row = $return[0];

            return count($row) == 1 ? array_shift($row) : $row;
        } catch (PDOException $e) {
            return new Error($e);
        }
    }
}