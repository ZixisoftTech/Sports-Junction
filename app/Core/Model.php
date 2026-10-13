<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected static ?PDO $db = null;

    protected static function db(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::getConnection();
        }
        return self::$db;
    }

    public static function beginTransaction(): bool
    {
        if (!self::db()->inTransaction()) {
            return self::db()->beginTransaction();
        }
        return true;
    }

    public static function commit(): bool
    {
        if (self::db()->inTransaction()) {
            return self::db()->commit();
        }
        return true;
    }

    public static function rollBack(): bool
    {
        if (self::db()->inTransaction()) {
            return self::db()->rollBack();
        }
        return false;
    }

    public static function inTransaction(): bool
    {
        return self::db()->inTransaction();
    }

    public static function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::execute($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::execute($sql, $params);
        return $stmt->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = self::execute($sql, $params);
        return $stmt->fetchColumn();
    }

    public static function lastInsertId(): string
    {
        return self::db()->lastInsertId();
    }
}
