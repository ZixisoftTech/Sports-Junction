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

            $attempts = [];
            $lastException = null;

            // 1. Try explicit unix socket if configured and file exists
            if (!empty($config['socket']) && file_exists($config['socket'])) {
                try {
                    self::$instance = new PDO($dsnSocket, $config['username'], $config['password'], $config['options']);
                    return self::$instance;
                } catch (PDOException $e) {
                    $attempts[] = "socket ({$config['socket']}): " . $e->getMessage();
                    $lastException = $e;
                }
            }

            // 2. Try configured host and port
            try {
                self::$instance = new PDO($dsnTcp, $config['username'], $config['password'], $config['options']);
                return self::$instance;
            } catch (PDOException $e) {
                $attempts[] = "host ({$config['host']}:{$config['port']}): " . $e->getMessage();
                $lastException = $e;
            }

            // 3. Fallback: If host was 127.0.0.1, try 'localhost' (uses server default unix socket in Linux/Hostinger)
            $altHost = ($config['host'] === '127.0.0.1') ? 'localhost' : (($config['host'] === 'localhost') ? '127.0.0.1' : null);
            if ($altHost !== null) {
                try {
                    $dsnAlt = sprintf(
                        'mysql:host=%s;dbname=%s;charset=%s',
                        $altHost,
                        $config['database'],
                        $config['charset']
                    );
                    self::$instance = new PDO($dsnAlt, $config['username'], $config['password'], $config['options']);
                    return self::$instance;
                } catch (PDOException $e) {
                    $attempts[] = "alt host ({$altHost}): " . $e->getMessage();
                    $lastException = $e;
                }
            }

            // 4. Fallback: Common Linux / cPanel / LiteSpeed unix sockets
            $commonSockets = ['/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock', '/var/lib/mysql/mysql.sock'];
            foreach ($commonSockets as $sock) {
                if (file_exists($sock)) {
                    try {
                        $dsnSock = sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $sock, $config['database'], $config['charset']);
                        self::$instance = new PDO($dsnSock, $config['username'], $config['password'], $config['options']);
                        return self::$instance;
                    } catch (PDOException $e) {
                        $attempts[] = "common socket ({$sock}): " . $e->getMessage();
                        $lastException = $e;
                    }
                }
            }

            throw new RuntimeException("Database connection failed. Attempts: " . implode(' | ', $attempts), 500, $lastException);
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
