<?php

namespace App\Actions;

class ExpressionResolver
{
    protected static function resolveExpression(string $expression): float
    {
        if (is_numeric($expression)) {
            return (float) $expression;
        } elseif (preg_match_all("/\((([^()]*|(?R))*)\)/",$expression,$matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                if (is_string($matches[1][$i]) and strlen($matches[1][$i]) > 0) {
                    $expression = str_replace(
                        $matches[0][$i],
                        self::resolveExpression($matches[1][$i]),
                        $expression
                    );
                }
            }
            return self::resolveExpression($expression);
        } else {
            foreach (['^','*','/','+','-'] as $operator) {
                $operator = "\\$operator";
                while (preg_match(
                    "~(?<left_hand>\d+(\.\d+)?)\s*(?<operator>[$operator])\s*(?<right_hand>\d+(\.\d+)?)~",
                    $expression,
                    $matches
                )) {
                    $expression = str_replace(
                        $matches[0],
                        self::resolveOperation($matches['left_hand'], $matches['operator'], $matches['right_hand']),
                        $expression
                    );
                }
            }
            return (float) $expression;
        }
    }

    protected static function resolveOperation($left_hand, $operator, $right_hand): float
    {
        return match ($operator) {
            '^' => pow((float) $left_hand, (float) $right_hand),
            '+' => (float) $left_hand + (float) $right_hand,
            '-' => (float) $left_hand - (float) $right_hand,
            '*' => (float) $left_hand * (float) $right_hand,
            '/' => (float) $left_hand / (float) $right_hand,
        };
    }

    public static function resolve(string $expression): float
    {
        return self::resolveExpression($expression);
    }


}
