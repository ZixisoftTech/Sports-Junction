<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $configPath = dirname(__DIR__) . '/Config/Database.php';
            if (!file_exists($configPath)) {
                throw new RuntimeException("Database configuration file not found at: {$configPath}");
            }

            $config = require $configPath;

            // First attempt socket connection (standard in XAMPP)
            $dsnSocket = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=%s',
                $config['socket'],
                $config['database'],
                $config['charset']
            );

            // Second attempt TCP connection
            $dsnTcp = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                if (!empty($config['socket']) && file_exists($config['socket'])) {
                    self::$instance = new PDO($dsnSocket, $config['username'], $config['password'], $config['options']);
                } else {
                    self::$instance = new PDO($dsnTcp, $config['username'], $config['password'], $config['options']);
                }
            } catch (PDOException $e) {
                // Fallback to TCP if socket failed
                try {
                    self::$instance = new PDO($dsnTcp, $config['username'], $config['password'], $config['options']);
                } catch (PDOException $tcpError) {
                    throw new RuntimeException("Database connection failed: " . $tcpError->getMessage(), (int)$tcpError->getCode(), $tcpError);
                }
            }
        }

        return self::$instance;
    }

    public static function setConnection(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function resetConnection(): void
    {
        self::$instance = null;
    }
}
