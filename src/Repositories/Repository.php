<?php

namespace App\Repositories;

use App\DB;
use App\Models\Model;
use App\Query;
use DateTime;
use Exception;
use PDO;
use PDOStatement;

/**
 * @template T of Model
 */
abstract class Repository
{
    /**
     * @var array
     */
    protected static array $columnMap = [];

    /**
     * @var array
     */
    protected array $casts = [];

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var class-string|T
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    protected string $model;

    /**
     * @param array $columnMap
     */
    public static function setColumnMap(array $columnMap): void
    {
        self::$columnMap = $columnMap;
    }

    /**
     * @return array
     */
    public static function getColumnMap(): array
    {
        return static::$columnMap;
    }

    /**
     * @param string $attribute
     * @return string|null
     */
    public static function getDatabaseColumn(string $attribute): ?string
    {
        $map = static::getColumnMap();
        foreach ($map as $column => $attributeName) {
            if ($attribute === $attributeName) return $column;
        }
        return null;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $query
     * @param array $bindings
     * @return PDOStatement|null
     */
    private function getStatement(string $query, array $bindings = []): ?PDOStatement
    {
        $stmt = (app(DB::class))->connection->prepare($query);

        foreach ($bindings as $binding => $value) {
            $stmt->bindParam($binding, $value);
        }

        return $stmt;
    }

    /**
     * @return T[]
     * @throws Exception
     */
    public function get(): array
    {
        return $this->getQuery()->get();
    }

    /**
     * @param $id
     * @return T|null
     * @throws Exception
     */
    public function find($id): ?Model
    {
        return $this->findByColumn('id', $id);
    }

    public function getQuery(): Query
    {
        return Query::from($this->table)
            ->setColumnMap(static::$columnMap)
            ->setCasts($this->casts)
            ->setModel($this->model);
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return T|null
     * @throws Exception
     */
    public function findByColumn(string $column, mixed $value): ?Model
    {
        return $this->getQuery()->where($column,'=',$value)->first();
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return T[]
     * @throws Exception
     */
    public function getByColumn(string $column, mixed $value): array
    {
        return $this->getQuery()->where($column,'=',$value)->get();
    }
}
