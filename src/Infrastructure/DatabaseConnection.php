<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 Nov 2020
 */

declare(strict_types=1);

namespace Frameworkless\Infrastructure;

use Frameworkless\Environment;

class DatabaseConnection
{
    private \PDO $pdo;

    public function fetchAll(string $sql, array $parameters = []): array
    {
        return $this->perform($sql, $parameters)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function fetchPairs(string $sql, array $parameters = []): array
    {
        return $this->perform($sql, $parameters)->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
    }

    public function fetchRow(string $sql, array $parameters = []): array
    {
        return $this->perform($sql, $parameters)->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    public function fetchValue(string $sql, array $parameters = []): string
    {
        return (string) $this->perform($sql, $parameters)->fetchColumn(0);
    }

    public function execute(string $sql, array $parameters = []): int
    {
        return $this->perform($sql, $parameters)->rowCount();
    }

    public function lastInsertId(): int
    {
        return intval($this->connect()->lastInsertId());
    }

    public function transaction(callable $function): void
    {
        $this->connect();
        try {
            $this->pdo->beginTransaction();
            $function();
            $this->pdo->commit();
        } catch (\PDOException $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function connect(): \PDO
    {
        return $this->pdo ?? $this->pdo = new \PDO(
                'mysql:host=' . Environment::getString('MYSQL_HOST')
                . ';dbname=' . Environment::getString('MYSQL_DATABASE'),
                Environment::getString('MYSQL_USER'),
                Environment::getString('MYSQL_PASSWORD'),
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
    }

    private function perform(string $sql, array $parameters = []): \PDOStatement
    {
        $statement = $this->connect()->prepare($sql);
        foreach ($parameters as $index => $value) {
            switch (gettype($value)) {
                case 'boolean':
                    $type = \PDO::PARAM_BOOL;
                    break;
                case 'integer':
                    $type = \PDO::PARAM_INT;
                    break;
                case 'NULL':
                    $type = \PDO::PARAM_NULL;
                    break;
                default:
                    $type = \PDO::PARAM_STR;
                    break;
            }
            $statement->bindValue($value, $value, $type);
        }
        $statement->execute();
        return $statement;
    }
}