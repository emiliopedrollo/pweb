<?php

namespace App;

class Pipeline
{
    /**
     * @var callable[]
     */
    protected array $stages = [];

    public function __construct($stages)
    {
        $this->stages = $stages;
    }

    public function pipe(callable $pipe): static
    {
        $this->stages[] = $pipe;
        return $this;
    }

    public function iterator(array $stages, mixed &$payload, callable $then) : iterable{
        $stages = $this->stages;
        do {
            $stage = current($stages);
            $next = next($stages) ?: $then;
            prev($stages);
            yield fn() => $stage($payload, $next);
        } while (next($stages));
    }

    public function process($payload, ?callable $then = null)
    {
        $then ??= fn() => $payload;
        $generator = $this->iterator($this->stages, $payload, $then);
        return ($this->stages[0])($payload, $generator->current() ?? $then);
    }

    public function __invoke($payload)
    {
        return $this->process($payload);
    }
}
