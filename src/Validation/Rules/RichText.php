<?php

namespace Emaia\LaravelHotwire\Validation\Rules;

use Closure;
use Emaia\LaravelHotwire\Support\RichTextContent;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

final readonly class RichText implements ValidationRule
{
    private const string MAX = 'max';

    private const string MIN = 'min';

    private const string REQUIRED = 'required';

    /** Allow required constraints to run for missing and empty attributes. */
    public bool $implicit;

    /**
     * @param  (Closure(): bool)|bool  $condition
     */
    private function __construct(
        private string $constraint,
        private int $limit = 0,
        private bool|Closure $condition = false,
    ) {
        $this->implicit = $constraint === self::REQUIRED;
    }

    /** Require normalized rich text to contain text or recognized non-text content. */
    public static function required(): self
    {
        return self::requiredIf(true);
    }

    /**
     * Require normalized rich text when the condition evaluates to true.
     *
     * @param  (Closure(): bool)|bool  $condition
     */
    public static function requiredIf(bool|Closure $condition): self
    {
        return new self(self::REQUIRED, condition: $condition);
    }

    /** Require the normalized text to contain at least the given characters. */
    public static function min(int $length): self
    {
        self::ensureNonNegative($length);

        return new self(self::MIN, limit: $length);
    }

    /** Require the normalized text to contain no more than the given characters. */
    public static function max(int $length): self
    {
        self::ensureNonNegative($length);

        return new self(self::MAX, limit: $length);
    }

    /** Validate the configured constraint against normalized rich-text content. */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->constraint === self::REQUIRED && ! $this->conditionPasses()) {
            return;
        }

        if (! is_string($value)) {
            if ($this->constraint === self::REQUIRED && self::isEmptyRequiredValue($value)) {
                $fail('validation.required')->translate();
            }

            return;
        }

        try {
            $content = RichTextContent::fromHtml($value);
        } catch (InvalidArgumentException) {
            $fail('hotwire::validation.invalid_rich_text')->translate();

            return;
        }

        if ($this->constraint === self::REQUIRED) {
            if ($content->isBlank()) {
                $fail('validation.required')->translate();
            }

            return;
        }

        if ($content->isBlank()) {
            return;
        }

        $length = $content->plainTextLength();

        if ($this->constraint === self::MIN && $length < $this->limit) {
            $fail('validation.min.string')->translate(['min' => $this->limit]);
        }

        if ($this->constraint === self::MAX && $length > $this->limit) {
            $fail('validation.max.string')->translate(['max' => $this->limit]);
        }
    }

    private function conditionPasses(): bool
    {
        return $this->condition instanceof Closure
            ? (bool) ($this->condition)()
            : $this->condition;
    }

    private static function ensureNonNegative(int $length): void
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Rich text length limits must be zero or greater.');
        }
    }

    private static function isEmptyRequiredValue(mixed $value): bool
    {
        return $value === null || (is_countable($value) && count($value) === 0);
    }
}
