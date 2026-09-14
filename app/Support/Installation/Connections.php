<?php

declare(strict_types=1);

namespace App\Support\Installation;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;
use Redis;
use RuntimeException;

class Connections
{
    /**
     * Test database connection and check if it is empty.
     *
     * @param array $values
     * @param bool $empty
     *
     * @return void
     */
    public function database(array $values, bool $empty = true): void
    {
        $this->configureDatabase($values);

        $connection = DB::connection('installation');

        try {
            $connection->getPdo();
            $connection->select('SELECT 1');

            if ($empty && $this->databaseHasTables($connection, $values)) {
                throw new RuntimeException('Database must be empty.');
            }
        } finally {
            DB::purge('installation');
        }
    }

    /**
     * Check if the database has any tables.
     *
     * @param Connection $connection
     * @param array $values
     *
     * @return bool
     */
    private function databaseHasTables(Connection $connection, array $values): bool
    {
        $driver = $values['DB_CONNECTION'];

        return match ($driver) {
            'mysql',
            'mariadb' => $this->mysqlDatabaseHasTables(
                $connection,
                $values['DB_DATABASE']
            ),

            'pgsql' => $this->postgresDatabaseHasTables(
                $connection
            ),

            default => throw new RuntimeException(
                "Unsupported database driver: {$driver}"
            ),
        };
    }

    /**
     * Check if the MySQL database has any tables.
     *
     * @param Connection $connection
     * @param string $database
     *
     * @return bool
     */
    private function mysqlDatabaseHasTables(Connection $connection, string $database): bool
    {
        $table = $connection->selectOne(
            <<<'SQL'
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ?
          AND TABLE_TYPE = 'BASE TABLE'
        LIMIT 1
        SQL,
            [$database]
        );

        return $table !== null;
    }

    /**
     * Check if the PostgreSQL database has any tables.
     *
     * @param Connection $connection
     *
     * @return bool
     */
    private function postgresDatabaseHasTables($connection): bool
    {
        $table = $connection->selectOne(
            <<<'SQL'
        SELECT tablename
        FROM pg_catalog.pg_tables
        WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
        LIMIT 1
        SQL
        );

        return $table !== null;
    }

    /**
     * Configure the database connection for installation.
     *
     * @param array $values
     *
     * @return void
     */
    public function configureDatabase(array $values): void
    {
        $driver = $values['DB_CONNECTION'];
        $config = config('database.connections.'.$driver, []);
        $config = array_replace($config, [
            'driver' => $driver, 'url' => null, 'host' => $values['DB_HOST'],
            'port' => $values['DB_PORT'], 'database' => $values['DB_DATABASE'],
            'username' => $values['DB_USERNAME'], 'password' => $values['DB_PASSWORD'] ?? '',
            'prefix' => '', 'options' => [PDO::ATTR_TIMEOUT => 5],
        ]);
        config(['database.connections.installation' => $config]);
        DB::purge('installation');
    }

    /**
     * Test Redis connection and check if the database is available.
     *
     * @param array $values
     *
     * @return void
     */
    public function redis(array $values): void
    {
        foreach (array_unique([$values['REDIS_DB'], $values['REDIS_CACHE_DB']]) as $database) {
            $redis = new Redis;
            try {
                $host = ($values['REDIS_SCHEME'] === 'tls' ? 'tls://' : '').$values['REDIS_HOST'];
                if (! $redis->connect($host, (int) $values['REDIS_PORT'], 5, null, 0, 5)) {
                    throw new RuntimeException('Redis unavailable.');
                }

                if (! empty($values['REDIS_PASSWORD'])) {
                    $credentials = empty($values['REDIS_USERNAME']) ? $values['REDIS_PASSWORD'] : [$values['REDIS_USERNAME'], $values['REDIS_PASSWORD']];
                    if (! $redis->auth($credentials)) {
                        throw new RuntimeException('Redis authentication failed.');
                    }
                }

                if (! $redis->select((int) $database) || ! $redis->ping()) {
                    throw new RuntimeException('Redis database unavailable.');
                }
            } finally {
                if ($redis->isConnected()) {
                    $redis->close();
                }
            }
        }
    }
}
