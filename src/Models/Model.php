<?php

namespace App\Models;

use App\Query;
use App\Repositories\Repository;

abstract class Model
{
    public function __construct(
        protected array $attributes = []
    ){}

    public function __get(string $name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return null;
    }

    protected static function repositoryClass(): string
    {
        return sprintf('App\\Repositories\\%sRepository',
            (new \ReflectionClass(static::class))->getShortName()
        );
    }

    public static function repository(): Repository
    {
        return app(self::repositoryClass());
    }

    public static function getTable(): string
    {
        return static::repository()->getTable();
    }

    public static function query(): Query
    {
        return static::repository()->getQuery();
    }
}
