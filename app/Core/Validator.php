<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small server-side validation helper. All validation in this application
 * happens here, on the server, regardless of any client-side HTML5
 * validation attributes used for UX — client-side checks are never trusted
 * as the sole line of defense (see SECURITY.md).
 *
 * Usage:
 *   $v = new Validator($request->all());
 *   $v->required('name')->maxLength('name', 100);
 *   $v->required('email')->email('email');
 *   if ($v->fails()) { ... $v->errors() ... }
 */
final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function required(string $field, ?string $label = null): self
    {
        $value = $this->value($field);
        if ($value === null || $value === '' || (is_array($value) && $value === [])) {
            $this->addError($field, ($label ?? $this->label($field)) . ' is required.');
        }
        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, 'Enter a valid email address.');
        }
        return $this;
    }

    public function numeric(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, $this->label($field) . ' must be a number.');
        }
        return $this;
    }

    public function integer(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $this->label($field) . ' must be a whole number.');
        }
        return $this;
    }

    public function minLength(string $field, int $min): self
    {
        $value = $this->value($field);
        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, $this->label($field) . " must be at least {$min} characters.");
        }
        return $this;
    }

    public function maxLength(string $field, int $max): self
    {
        $value = $this->value($field);
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, $this->label($field) . " must not exceed {$max} characters.");
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->addError($field, $this->label($field) . ' is not a valid option.');
        }
        return $this;
    }

    public function date(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && strtotime((string) $value) === false) {
            $this->addError($field, $this->label($field) . ' must be a valid date.');
        }
        return $this;
    }

    public function mobile(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', (string) $value)) {
            $this->addError($field, $this->label($field) . ' must be a valid phone number.');
        }
        return $this;
    }

    public function matches(string $field, string $otherField): self
    {
        if ($this->value($field) !== $this->value($otherField)) {
            $this->addError($field, $this->label($field) . ' does not match.');
        }
        return $this;
    }

    public function min(string $field, float $min): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value < $min) {
            $this->addError($field, $this->label($field) . " must be at least {$min}.");
        }
        return $this;
    }

    public function custom(string $field, callable $fn, string $message): self
    {
        if (!$fn($this->value($field))) {
            $this->addError($field, $message);
        }
        return $this;
    }

    private function label(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstErrors(): array
    {
        return array_map(static fn (array $messages) => $messages[0], $this->errors);
    }
}
