<?php

namespace LaravelCQRS;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\MessageBag;

abstract class Command
{
    /**
     * Command data.
     *
     * @var array
     */
    protected array $data;

    /**
     * Command constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get command data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get a specific value from command data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set a value in command data.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Check if a key exists in command data.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get all data as object.
     * Recursively converts arrays to objects, preserving indexed arrays.
     *
     * @return object
     */
    public function toObject(): object
    {
        return $this->arrayToObject($this->data);
    }

    /**
     * Recursively convert array to object.
     * Indexed arrays (numeric keys) remain as arrays.
     * Associative arrays (string keys) are converted to objects.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function arrayToObject(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        // Empty array stays as array
        if (empty($data)) {
            return $data;
        }

        // Check if this is an indexed array (sequential numeric keys starting from 0)
        // An indexed array has keys exactly matching [0, 1, 2, ..., n-1]
        $keys = array_keys($data);
        $isIndexed = $keys === range(0, count($data) - 1);
        
        if ($isIndexed) {
            // Indexed array - keep as array but recurse into values
            return array_map([$this, 'arrayToObject'], $data);
        }

        // Associative array - convert to object recursively
        $object = new \stdClass();
        foreach ($data as $key => $value) {
            $object->$key = is_array($value) ? $this->arrayToObject($value) : $value;
        }

        return $object;
    }

    /**
     * Get validation rules for the command.
     * Override this method in your command class to provide validation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get custom validation messages.
     * Override this method in your command class to provide custom messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attribute names for validation.
     * Override this method in your command class to provide custom attribute names.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Validate the command data using Laravel's validator.
     * This will throw a ValidationException if validation fails.
     *
     * @return array The validated data
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(): array
    {
        $rules = $this->rules();
        
        if (empty($rules)) {
            return $this->data;
        }

        $factory = \app(ValidationFactory::class);
        $validator = $factory->make(
            $this->data,
            $rules,
            $this->messages(),
            $this->attributes()
        );

        return $validator->validate();
    }

    /**
     * Check if the command data is valid without throwing an exception.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $rules = $this->rules();
        
        if (empty($rules)) {
            return true;
        }

        $factory = \app(ValidationFactory::class);
        $validator = $factory->make(
            $this->data,
            $rules,
            $this->messages(),
            $this->attributes()
        );

        return $validator->passes();
    }

    /**
     * Get validation errors without throwing an exception.
     *
     * @return MessageBag
     */
    public function errors(): MessageBag
    {
        $rules = $this->rules();
        
        if (empty($rules)) {
            return new MessageBag();
        }

        $factory = \app(ValidationFactory::class);
        $validator = $factory->make(
            $this->data,
            $rules,
            $this->messages(),
            $this->attributes()
        );

        return $validator->errors();
    }
}

