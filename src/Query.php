<?php

namespace App;

use App\Exceptions\NotFoundException;
use App\Models\Model;
use DateTime;
use Exception;
use PDO;
use PDOStatement;

class Query
{
    protected array $where = [];

    protected array $select = [];

    protected ?int $limit = null;

    protected ?string $model = null;

    protected array $bindings = [
        'where' => []
    ];

    protected array $columnMap = [];
    protected array $casts;

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

    public static function from(string $table): static
    {
        return new Query($table);
    }

    public function orWhere($column, $operator, $value): static
    {
        return $this->where($column, $operator, $value, true);
    }

    public function where($column, $operator, $value, $or = false): static
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

    protected function quote($value): string
    {
        return app(DB::class)->connection->quote($value);
    }

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

        $return .= sprintf('%s %s %s',
            $where['column'], $where['operator'], '?'
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


        return $sql;

    }

    public function prepare(): PDOStatement
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

    /**
     * @throws Exception
     * @return Model[]|array
     */
    public function get(): array
    {
        $statement = $this->prepare();

        $entries = [];

        if ($statement->execute()) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createModelFromRow($row);
            }
        }

        return $entries;
    }

    public function when($value, $callback): static
    {
        if ($value) {
            $callback($this, $value);
        }

        return $this;
    }

    public function setCasts(array $casts): static
    {
        $this->casts = $casts;
        return $this;
    }

    public function setModel(string $model): static
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

        return is_null($this->model)
            ? $args
            : new ($this->model)($args);
    }

    /**
     * @throws Exception
     */
    public function first($orFail = false): Model|array|null
    {
        $statement = $this->prepare();

        if ($statement->execute()) {
            if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                return $this->createModelFromRow($row);
            }
        }

        if ($orFail) {
            throw new NotFoundException;
        }

        return null;
    }


}
