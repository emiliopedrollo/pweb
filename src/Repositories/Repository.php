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
    public static array $columnMap = [];

    /**
     * @var array
     */
    public array $casts = [];

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var class-string|T
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    protected string $model;

    public function getTable(): string
    {
        return $this->table;
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
