<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   24 Nov 2020
 */

declare(strict_types=1);

namespace Frameworkless\Repository;

use function Latitude\QueryBuilder\field;

class UserRepository extends AbstractRepository
{
    /**
     * @param string $userName
     * @param string $password
     * @return array the row from the table that matches the user or empty if no match
     */
    public function findForLogin(string $userName, string $password): array
    {
        $query = $this->select()
                      ->from(static::TABLE_NAME)
                      ->where(field('name')->eq($userName))
                      ->andWhere(field('password')->eq(\Safe\password_hash($password, PASSWORD_DEFAULT)));
        return $this->fetchRow($query);
    }
}