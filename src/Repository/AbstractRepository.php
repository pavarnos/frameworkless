<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   15 Jun 2020
 */

declare(strict_types=1);

namespace Frameworkless\Repository;

use Frameworkless\Infrastructure\DatabaseConnection;
use Latitude\QueryBuilder\Engine\MySqlEngine;
use Latitude\QueryBuilder\ExpressionInterface;
use Latitude\QueryBuilder\Query\AbstractQuery;
use Latitude\QueryBuilder\Query\DeleteQuery;
use Latitude\QueryBuilder\Query\MySql\InsertQuery;
use Latitude\QueryBuilder\Query\SelectQuery;
use Latitude\QueryBuilder\Query\UpdateQuery;
use Latitude\QueryBuilder\QueryFactory;

use function Latitude\QueryBuilder\express;
use function Latitude\QueryBuilder\field;

/**
 * Utility functions to ease database access
 *
 * Public ones are higher level
 * Protected are low level
 */
abstract class AbstractRepository
{
    public const TABLE_NAME = '';

    private static QueryFactory $queryFactory;

    private DatabaseConnection $database;

    public function __construct(DatabaseConnection $database)
    {
        assert(static::TABLE_NAME !== '', 'set TABLE_NAME in your derived class');
        if (!isset(self::$queryFactory)) {
            self::$queryFactory = new QueryFactory(new MySqlEngine());
        }
        $this->database = $database;
    }

    public function now(): ExpressionInterface
    {
        return express('now()');
    }

    /**
     * @param int   $recordID primary key
     * @param array $columns  to return or [] for all
     * @return ?array
     */
    public function findOrNull(int $recordID, array $columns = []): ?array
    {
        if (empty($recordID)) {
            // no id means no record. Records should not have an id of 0
            return null;
        }
        $query = $this->select()
                      ->from(static::TABLE_NAME)
                      ->where(field('id')->eq($recordID));
        if (!empty($columns)) {
            $query->columns(...$columns);
        }
        return $this->fetchRow($query) ?: null;
    }

    /**
     * @param int   $recordID primary key
     * @param array $columns  to return or [] for all
     * @return array rejects on no such record
     */
    public function findOrException(int $recordID, array $columns = []): array
    {
        $result = $this->findOrNull($recordID, $columns);
        if (is_null($result)) {
            throw new RepositoryException('Not found', static::TABLE_NAME, $recordID);
        }
        return $result;
    }

    /**
     * save the $data back in to the repository.
     * A new record has $data[self::ID_FIELD] empty or not set
     * An existing record has this field populated, so the existing database row will be updated.
     * @param array $data
     * @return int $recordID created or updated
     */
    public function save(array $data): int
    {
        if (empty($data['id'])) {
            return $this->insertRow($data);
        }
        return $this->updateRow($data['id'], $data);
    }

    public function deleteRow(int $recordID): int
    {
        if (empty($recordID)) {
            throw new RepositoryException('Record to delete not specified', static::TABLE_NAME, $recordID);
        }
        $query    = $this->delete(static::TABLE_NAME)->where(field('id')->eq($recordID));
        $compiled = $query->compile();
        return $this->database->execute($compiled->sql(), $compiled->params());
    }

    protected function fetchAll(SelectQuery $select): array
    {
        $compiled = $select->compile();
        return $this->database->fetchAll($compiled->sql(), $compiled->params());
    }

    protected function fetchRow(SelectQuery $select): array
    {
        $compiled = $select->compile();
        return $this->database->fetchRow($compiled->sql(), $compiled->params());
    }

    protected function fetchInt(SelectQuery $select): int
    {
        $compiled = $select->compile();
        return intval($this->database->fetchValue($compiled->sql(), $compiled->params()));
    }

    protected function fetchPairs(SelectQuery $select): array
    {
        $compiled = $select->compile();
        return $this->database->fetchPairs($compiled->sql(), $compiled->params());
    }

    protected function fetchColumn(SelectQuery $select, string $columnName = 'title'): array
    {
        $output = [];
        foreach ($this->fetchAll($select) as $row) {
            $output[] = $row[$columnName];
        }
        return $output;
    }

    protected function updateRow(int $id, array $data): int
    {
        unset($data['id']);
        $update   = $this->update(static::TABLE_NAME, $data)->where(field('id')->eq($id));
        $compiled = $update->compile();
        $this->database->execute($compiled->sql(), $compiled->params());
        return $id;
    }

    protected function insertRow(array $data): int
    {
        $insert   = $this->insert(static::TABLE_NAME, $data);
        $compiled = $insert->compile();
        $this->database->execute($compiled->sql(), $compiled->params());
        return $this->database->lastInsertId();
    }

    protected function write(AbstractQuery $query): void
    {
        $compiled = $query->compile();
        $this->database->execute($compiled->sql(), $compiled->params());
    }

    protected function writeAndCount(AbstractQuery $query): int
    {
        $compiled = $query->compile();
        return $this->database->execute($compiled->sql(), $compiled->params());
    }

    /**
     * @param string|ExpressionInterface ...$columns
     * @return SelectQuery
     */
    protected function select(...$columns): SelectQuery
    {
        return self::$queryFactory->select(...$columns);
    }

    /**
     * @param string|ExpressionInterface ...$columns
     * @return SelectQuery
     */
    protected function selectAll(...$columns): SelectQuery
    {
        return $this->select(...$columns)->from(static::TABLE_NAME);
    }

    protected function insert(string $tableName, array $map = []): InsertQuery
    {
        $insert = self::$queryFactory->insert($tableName, $map);
        assert($insert instanceof InsertQuery);
        return $insert;
    }

    protected function update(string $tableName, array $map = []): UpdateQuery
    {
        return self::$queryFactory->update($tableName, $map);
    }

    protected function delete(string $tableName): DeleteQuery
    {
        return self::$queryFactory->delete($tableName);
    }
}