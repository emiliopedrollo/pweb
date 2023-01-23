<?php

namespace App;

use App\Enums\Relation as RelationType;
use App\Exceptions\NotFoundException;
use App\Models\Model;
use Closure;
use DateTime;
use Exception;
use PDO;
use PDOStatement;

/**
 * @template T of Model
 */
class Query
{
    protected array $where = [];

    protected array $select = [];

    protected ?int $limit = null;

    protected ?string $model = null;

    protected array $bindings = [
        'update' => [],
        'where' => []
    ];

    protected array $columnMap = [];
    protected array $casts;
    protected array $loads = [];

    /**
     * @var Closure[]
     */
    private array $afterGet = [];
    protected array $orders = [];

    public function __construct(
        protected ?string $from = '',
    ){ }

    public function setColumnMap(array $columnMap): static
    {
        $this->columnMap = $columnMap;
        return $this;
    }

    protected function normalizeColumnName($column): string
    {
        foreach ($this->columnMap as $dbColumn => $attributeName) {
            if ($column === $attributeName) return $dbColumn;
        }
        return $column;
    }

    /**
     * @param string $table
     * @return static
     */
    public static function from(string $table): static
    {
        return new Query($table);
    }

    /**
     * @param $column
     * @param $operator
     * @param $value
     * @return $this
     */
    public function orWhere($column, $operator, $value): static
    {
        return $this->where($column, $operator, $value, true);
    }

    /**
     * @param $column
     * @param $operator
     * @param $value
     * @param bool $or
     * @return $this
     */
    public function where($column, $operator, $value, bool $or = false): static
    {
        $this->where[] =  [
            'column' => $this->normalizeColumnName($column),
            'operator' => $operator,
            'value' => $value,
            'or' => $or ? 'OR' : 'AND'
        ];

        $this->bindings['where'][] = $value;

        return $this;
    }

    /**
     * @param $column
     * @param $values
     * @param bool $or
     * @return $this
     */
    public function whereIn($column, $values, bool $or = false): static
    {
        $this->where[] = [
            'column' => $this->normalizeColumnName($column),
            'operator' => 'IN',
            'value' => sprintf("(%s)",join(',',array_fill(0, count($values), '?'))),
            'or' => $or ? 'OR' : 'AND'
        ];

        foreach ($values as $value) {
            $this->bindings['where'][] = $value;
        }

        return $this;
    }

    /**
     * @param $column
     * @param $values
     * @return $this
     */
    public function orWhereIn($column, $values): static
    {
        return $this->whereIn($column, $values, true);
    }

    protected function quote($value): string
    {
        return app(DB::class)->connection->quote($value);
    }

    private function clearSelect(): static
    {
        $this->select = [];
        return $this;
    }

    /**
     * @param string $select
     * @param bool $raw
     * @return $this
     */
    public function addSelect(string $select, bool $raw = false): static
    {
        $this->select[] = [
            'value' => $this->normalizeColumnName($select),
            'raw' => $raw
        ];
        return $this;
    }

    protected function buildWhere($where, $first): string {
        $return = $first ? '' : sprintf(' %s ',$where['or']);

        $value = $where['operator'] === 'IN' ? $where['value'] : '?';

        $return .= sprintf('%s %s %s',
            $where['column'], $where['operator'], $value
        );

        return $return;
    }

    protected function build(): string
    {
        if (empty($this->select)) {
            $this->addSelect('*',true);
        }

        $select = join(', ', array_map(
            fn($value) => ($value['raw']??false) ? $value['value'] : $this->quote($value['value']),
            $this->select
        ));

        $sql = sprintf(<<<QRY
            SELECT %s FROM %s
        QRY, $select, $this->from);

        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $first = true;
            foreach ($this->where as $where) {
                $sql .= $this->buildWhere($where, $first);
                $first = false;
            }
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ';
            $sql .= join(', ', array_map(fn($order) =>
                sprintf('%s %s',$this->normalizeColumnName($order['column']), $order['direction']),
                $this->orders
            ));
        }

        return $sql;

    }

    protected function prepare(): PDOStatement
    {
        $sql = $this->build();
        $statement = (app(DB::class))->connection->prepare($sql);

        $i = 1;
        foreach ($this->bindings as $bindings) {
            foreach ($bindings as $binding) {
                $statement->bindValue($i++, $binding);
            }
        }

        return $statement;

    }

    protected function prepareInsert(array $attributes): PDOStatement
    {

        $columns = join(', ', array_map(fn($column) => $this->normalizeColumnName($column), array_keys($attributes)));
        $values = join(', ', array_map(fn($value) => '?', $attributes));

        $sql = sprintf(<<<QRY
            INSERT INTO %s (%s) VALUES (%s)
        QRY, $this->from, $columns, $values);

        $statement = (app(DB::class))->connection->prepare($sql);

        $i = 1;
        foreach ($attributes as $attribute => $value) {
            $statement->bindValue($i++, $this->getAttributeDBValue($attribute, $value));
        }

        return $statement;
    }

    protected function getLastInsertedId(): ?int
    {
        $statement = (app(DB::class))->connection->prepare("SELECT LAST_INSERT_ID() AS LAST_ID");

        if ($statement->execute()) {
            return $statement->fetch(PDO::FETCH_ASSOC)["LAST_ID"];
        }

        return null;
    }

    protected function getAttributeDBValue($attribute, $value): mixed
    {
        return match ($this->casts[$attribute] ?? 'string'){
            'datetime' => ($value instanceof Datetime
                ? $value
                : DateTime::createFromFormat('d/m/Y H:i:s',$value)
            )->format('Y-m-d H:i:s'),
            default => $value
        };
    }

    protected function prepareDelete(): PDOStatement
    {
        /** @noinspection SqlWithoutWhere */
        $sql = "DELETE FROM {$this->from}";

        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $first = true;
            foreach ($this->where as $where) {
                $sql .= $this->buildWhere($where, $first);
                $first = false;
            }
        }

        $statement = (app(DB::class))->connection->prepare($sql);

        $i = 1;
        foreach ($this->bindings as $bindings) {
            foreach ($bindings as $binding) {
                $statement->bindValue($i++, $binding);
            }
        }

        return $statement;

    }

    protected function prepareUpdate(array $attributes): PDOStatement
    {
        $sets = join(', ',
            array_map(fn ($column) =>
                sprintf('%s = ?', $this->normalizeColumnName($column)),
                array_keys($attributes)
            )
        );

        foreach ($attributes as $attribute => $value) {
            $this->bindings['update'][] = $this->getAttributeDBValue($attribute, $value);
        }

        /** @noinspection SqlWithoutWhere */
        $sql = "UPDATE {$this->from} SET {$sets}";

        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $first = true;
            foreach ($this->where as $where) {
                $sql .= $this->buildWhere($where, $first);
                $first = false;
            }
        }

        $statement = (app(DB::class))->connection->prepare($sql);

        $i = 1;
        foreach ($this->bindings as $bindings) {
            foreach ($bindings as $binding) {
                $statement->bindValue($i++, $binding);
            }
        }

        return $statement;
    }

    /**
     * @throws Exception
     */
    public function find($id, $orFail = false): ?Model
    {
        return $this->where('id','=',$id)->first($orFail);
    }

    /**
     * @param $column
     * @return $this
     */
    public function orderByDesc($column): static
    {
        return $this->orderBy($column, true);
    }

    /**
     * @param $column
     * @param $desc
     * @return $this
     */
    public function orderBy($column, $desc = false): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => $desc ? 'DESC' : 'ASC'
        ];

        return $this;
    }

    /**
     * @throws Exception
     * @return Model|Model[]|array|null
     */
    public function get(): Model|array|null
    {
        $statement = $this->prepare();

        $entries = [];

        if ($statement->execute()) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createModelFromRow($row);
            }
        }

        $this->loadRelations($entries);

        foreach ($this->afterGet as $callback) {
            call_user_func($callback, $entries, $this, 'get');
        }

        return $entries;
    }

    /**
     * @param $value
     * @param $callback
     * @return $this
     */
    public function when($value, $callback): static
    {
        if ($value) {
            $callback($this, $value);
        }

        return $this;
    }

    /**
     * @param array|string $relations
     * @return $this
     */
    public function with(array|string ...$relations): static
    {
        $relations = array_flatten($relations);
        foreach ($relations as $relation){
            $this->loads[] = $relation;
        }

        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function addCallback(callable $callback): static
    {
        $this->afterGet[] = $callback(...);
        return $this;
    }

    /**
     * @param array $casts
     * @return $this
     */
    public function setCasts(array $casts): static
    {
        $this->casts = $casts;
        return $this;
    }

    /**
     * @param string|null $model
     * @return $this
     */
    public function setModel(?string $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param $attributes
     * @return Model|array
     * @throws Exception
     * @throws Exception
     */
    protected function createModelFromRow($attributes): Model|array
    {
        if (is_null($this->model)) {
            return $attributes;
        }

        $map = $this->columnMap;
        $args = [];

        foreach ($map as $column => $attribute) {
            $value = $attributes[$column] ?? null;

            $value = match ($this->casts[$attribute] ?? 'string'){
                'datetime' => new DateTime($value),
                default => $value
            };

            $args[$attribute] = $value;

        }

        /** @var Model $model */
        $model = (new ($this->model)($args));
        $model->setExists(true);

        return $model;
    }

    /**
     * @throws Exception
     */
    protected function loadRelations(array $models): void
    {
        $model = new $this->model;

        $ids = array_map(fn($model) => $model->id, $models);

        foreach ($this->loads as $load) {

            /** @var Relation $relation */
            $relation = call_user_func([$model,$load]);
            $relation->setIsEagerLoad(true);

            $foreignKey = $relation->getForeignKey();
            $localKey = $relation->getLocalKey();

            $fkeys = array_map(fn($model) => $model->$foreignKey, $models);

            $related_models = match ($relation->getType()) {
                RelationType::HasMany =>
                    $relation->whereIn($relation->getForeignKey(), $ids)->get(),
                RelationType::BelongsTo =>
                    $relation->whereIn($relation->getLocalKey(), $fkeys)->get(),
            };

            foreach ($models as $model) {

                $model_relations = array_filter($related_models, fn ($related_model) =>
                    match ($relation->getType()) {
                        RelationType::HasMany =>
                            $related_model->$foreignKey === $model->$localKey,
                        RelationType::BelongsTo =>
                            $related_model->$localKey === $model->$foreignKey
                    }
                );

                match ($relation->getType()) {
                    RelationType::HasMany =>
                        $model->addRelation($load, $model_relations),
                    RelationType::BelongsTo =>
                        $model->addRelation($load, $model_relations[0] ?? null)
                };
            }
        }
    }

    /**
     * @throws Exception
     */
    public function first($orFail = false): Model|array|null
    {
        $statement = $this->prepare();

        if ($statement->execute()) {
            if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $model = $this->createModelFromRow($row);
                $this->loadRelations([$model]);
                foreach ($this->afterGet as $callback) {
                    call_user_func($callback, $model, $this, 'first');
                }
                return $model;
            }
        }

        if ($orFail) {
            throw new NotFoundException;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        $statement = $this->prepareDelete();
        return $statement->execute();
    }

    /**
     * @param array $attributes
     * @return int|null
     */
    public function insert(array $attributes): ?int
    {
        $statement = $this->prepareInsert($attributes);
        $statement->execute();

        return $this->getLastInsertedId();
    }

    public function update(array $attributes): bool
    {
        $statement = $this->prepareUpdate($attributes);
        return $statement->execute();
    }

    /**
     * @throws Exception
     */
    public function count(): int
    {
        return (clone $this)
            ->clearSelect()
            ->setModel(null)
            ->addSelect('count(*) as count', true)
            ->first()['count'];
    }

    /**
     * @throws Exception
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }


}
