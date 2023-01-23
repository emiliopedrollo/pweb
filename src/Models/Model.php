<?php

namespace App\Models;

use App\Query;
use App\Relation;
use App\Repositories\Repository;

/**
 * @property int $id
 */
abstract class Model
{
    protected bool $exists = false;
    private array $dirty = [];
    private array $original;
    private array $relations = [];

    public function __construct(
        protected array $attributes = []
    ){
        $this->original = $attributes;
    }

    public function __get(string $name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        if (method_exists($this, $name)) {
            if (array_key_exists($name, $this->relations)){
                return $this->relations[$name];
            }
            return call_user_func([$this, $name])->get();
        }
        return null;
    }

    public function __set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
        $this->dirty[] = $name;
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

    public static function create($atributes): static
    {
        $model = static::make($atributes);
        $model->save();
        return $model;
    }

    public static function make($attributes): static
    {
        return new static($attributes);
    }

    public function fill(array $attributes): static {
        foreach ($attributes as $attribute => $value) {
            $this->$attribute = $value;
        }
        return $this;
    }

    public function update(array $attributes): static
    {
        return $this->fill($attributes)->save();
    }

    public function save(): static
    {
        if ($this->exists) {
            self::query()
                ->where('id', '=', $this->id)
                ->update(
                    array_filter(
                        $this->attributes,
                        fn($key) => in_array($key, $this->dirty),
                        ARRAY_FILTER_USE_KEY
                    )
                );
        } else {
            $this->id = self::query()->insert($this->attributes);
            $this->exists = true;
        }

        $this->dirty = [];
        $this->original = $this->attributes;

        return $this;
    }

    public function delete(): bool
    {
        if ($this->exists) {
            return self::query()
                ->where('id','=', $this->id)
                ->delete();
        }
        return true;
    }

    /**
     * @param bool $exists
     * @return Model
     */
    public function setExists(bool $exists): Model
    {
        $this->exists = $exists;
        return $this;
    }

    /**
     * @return array
     */
    public function getOriginal(): array
    {
        return $this->original;
    }

    public function hasMany(string $model, string $foreignKey = null, string $localKey = 'id'): Relation
    {
        $name = debug_backtrace()[1]['function'];
        return Relation::HasMany($name, $model, $foreignKey, $localKey, $this)->getQuery();
    }

    public function belongsTo(string $model, string $foreignKey = null, string $localKey = 'id'): Relation
    {
        $name = debug_backtrace()[1]['function'];
        return Relation::BelongsTo($name, $model, $foreignKey, $localKey, $this)->getQuery();
    }

    /**
     * @param string $relation
     * @param Model|array $items
     * @return Model
     */
    public function addRelation(string $relation, Model|array $items): Model
    {
        $this->relations[$relation] = $items;
        return $this;
    }
}
