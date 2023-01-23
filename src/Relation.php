<?php

namespace App;

use App\Enums\Relation as RelationType;
use App\Models\Model;
use App\Models\User;
use App\Repositories\Repository;
use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * @template T of Model
 */
class Relation extends Query
{
    protected string $name;

    protected string $localKey = 'id';

    protected Model $localModel;

    protected string $foreignKey;

    protected bool $isEagerLoad = false;

    protected RelationType $type;

    /**
     * @param string $name
     * @param class-string<T> $model
     * @param string|null $foreignKey
     * @param string $localKey
     * @param Model|null $local
     * @return Relation<T>
     * @throws ReflectionException
     */
    public static function HasMany(
        string $name, string $model, string $foreignKey = null, string $localKey = 'id', Model $local = null
    ): Relation {
        $table = call_user_func([$model,'getTable']);
        return (new self($table))
            ->setName($name)
            ->setLocalModel($local)
            ->setLocalKey($localKey)
            ->setModel($model)
            ->setType(RelationType::HasMany)
            ->setForeignKey($foreignKey ??
                sprintf("%s_id",mb_strtolower((new ReflectionClass($local))->getShortName()))
            );
    }

    /**
     * @param string $name
     * @param class-string<T> $model
     * @param string|null $foreignKey
     * @param string $localKey
     * @param Model|null $local
     * @return Relation<T>
     * @throws ReflectionException
     */
    public static function BelongsTo(
        string $name, string $model, string $foreignKey = null, string $localKey = 'id', Model $local = null
    ): Relation {
        $table = call_user_func([$model,'getTable']);
        return (new self($table))
            ->setName($name)
            ->setLocalModel($local)
            ->setLocalKey($localKey)
            ->setModel($model)
            ->setType(RelationType::BelongsTo)
            ->setForeignKey($foreignKey ??
                sprintf("%s_id",mb_strtolower((new ReflectionClass($model))->getShortName()))
            );
    }

    /**
     * @param string $foreignKey
     * @return self
     */
    public function setForeignKey(string $foreignKey): Relation
    {
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function getQuery(): Relation
    {
        /** @var Repository $repository */
        $repository = call_user_func([$this->model,'repository']);

        return $this
            ->setColumnMap($repository::$columnMap)
            ->setCasts($repository->casts)
            ->setModel($this->model);
    }

    /**
     * @return T|array|T[]|null
     * @throws Exception
     */
    public function get(): Model|array|null
    {
        if ($this->isEagerLoad) return parent::get();

        $this->addCallback(fn($items) => $this->localModel->addRelation($this->name, $items));

        switch ($this->type){
            case RelationType::HasMany:
                $this->where($this->foreignKey,'=',$this->localModel->{$this->localKey});
                return parent::get();
            case RelationType::BelongsTo:
                $this->where($this->localKey,'=', $this->localModel->{$this->foreignKey});
                return parent::first();
        }
        return null;
    }

    public function first($orFail = false): Model|array|null
    {
        if ($this->isEagerLoad) return parent::first($orFail);

        $this->addCallback(fn($items) => $this->localModel->addRelation($this->name, $items));

        switch ($this->type){
            case RelationType::HasMany:
                $this->where($this->foreignKey,'=',$this->localModel->{$this->localKey});
                break;
            case RelationType::BelongsTo:
                $this->where($this->localKey,'=', $this->localModel->{$this->foreignKey});
                break;
        }
        return parent::first($orFail);

    }

    /**
     * @param Model $model
     * @return self
     */
    protected function setLocalModel(Model $model): Relation
    {
        $this->localModel = $model;
        return $this;
    }

    /**
     * @param RelationType $type
     * @return self
     */
    protected function setType(RelationType $type): Relation
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param mixed $localKey
     * @return self
     */
    public function setLocalKey(string $localKey): Relation
    {
        $this->localKey = $localKey;
        return $this;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): Relation
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param bool $isEagerLoad
     * @return Relation
     */
    public function setIsEagerLoad(bool $isEagerLoad): Relation
    {
        $this->isEagerLoad = $isEagerLoad;
        return $this;
    }

    /**
     * @return RelationType
     */
    public function getType(): RelationType
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * @return string
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

}
