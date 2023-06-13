<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\UriTemplate;

use League\Uri\Exceptions\SyntaxError;
use League\Uri\Exceptions\TemplateCanNotBeExpanded;
use Stringable;
use function array_keys;
use function array_reduce;
use function preg_match_all;
use function preg_replace;
use function str_contains;
use const PREG_SET_ORDER;

final class Template
{
    /**
     * Expression regular expression pattern.
     */
    private const REGEXP_EXPRESSION_DETECTOR = '/(?<expression>\{[^}]*})/x';

    /** @var array<Expression> */
    private readonly array $expressions;
    /** @var array<string> */
    public readonly array $variableNames;

    private function __construct(public readonly string $value, Expression ...$expressions)
    {
        $this->expressions = $expressions;
        $this->variableNames = array_keys(
            array_reduce(
                $expressions,
                fn (array $curry, Expression $expression): array => [...$curry, ...array_fill_keys($expression->variableNames, 1)],
                []
            )
        );
    }

    /**
     * @throws SyntaxError if the template contains invalid expressions
     * @throws SyntaxError if the template contains invalid variable specification
     */
    public static function fromString(Stringable|string $template): self
    {
        $template = (string) $template;
        /** @var string $remainder */
        $remainder = preg_replace(self::REGEXP_EXPRESSION_DETECTOR, '', $template);
        if (str_contains($remainder, '{') || str_contains($remainder, '}')) {
            throw new SyntaxError('The template "'.$template.'" contains invalid expressions.');
        }

        $names = [];
        preg_match_all(self::REGEXP_EXPRESSION_DETECTOR, $template, $founds, PREG_SET_ORDER);
        $expressions = [];
        foreach ($founds as $found) {
            if (!isset($names[$found['expression']])) {
                $expressions[] = Expression::fromString($found['expression']);
                $names[$found['expression']] = 1;
            }
        }

        return new self($template, ...$expressions);
    }

    /**
     * @throws TemplateCanNotBeExpanded if the variables is an array and a ":" modifier needs to be applied
     * @throws TemplateCanNotBeExpanded if the variables contains nested array values
     */
    public function expand(VariableBag|iterable $variables): string
    {
        if (!$variables instanceof VariableBag) {
            $variables = new VariableBag($variables);
        }

        return array_reduce(
            $this->expressions,
            fn (string $uri, Expression $expr): string => str_replace($expr->value, $expr->expand($variables), $uri),
            $this->value
        );
    }
}
