<?php

namespace App\Services;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class ValidationService
{
    private $errors = [];
    private $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        $this->errors = [];
        return $this;
    }

    public function validate(array $rules): bool
    {
        $this->errors = [];
        
        $validator = $this->buildValidator($rules);
        $isValid = $validator->validate($this->data);
        
        if ($isValid) {
            return true;
        }
        
        try {
            $validator->assert($this->data);
        } catch (ValidationException $e) {
            $this->parseErrors($e);
        }
        
        return false;
    }

    private function buildValidator(array $rules): v
    {
        $validator = null;
        $first = true;
        
        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $rule = $this->parseRuleString($rule);
            }
            
            if ($first) {
                $validator = v::key($field, $rule);
                $first = false;
            } else {
                $validator = $validator->key($field, $rule);
            }
        }
        
        return $validator ?? v::alwaysValid();
    }

    private function parseRuleString(string $rule): v
    {
        $parts = explode('|', $rule);
        $validators = [];
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            if (strpos($part, ':') !== false) {
                [$ruleName, $params] = explode(':', $part, 2);
                $validators[] = $this->applyRuleWithParams($ruleName, $params);
            } else {
                $validators[] = $this->applyRule($part);
            }
        }
        
        if (empty($validators)) {
            return v::alwaysValid();
        }
        
        if (count($validators) === 1) {
            return $validators[0];
        }
        
        return v::allOf(...$validators);
    }

    private function applyRule(string $ruleName): v
    {
        return match($ruleName) {
            'required' => v::notEmpty(),
            'email' => v::email(),
            'numeric' => v::numericVal(),
            'int' => v::intVal(),
            'string' => v::stringType(),
            'array' => v::arrayType(),
            'bool' => v::boolType(),
            'positive' => v::positive(),
            default => v::alwaysValid()
        };
    }

    private function applyRuleWithParams(string $ruleName, string $params): v
    {
        return match($ruleName) {
            'min' => v::min((int)$params),
            'max' => v::max((int)$params),
            'maxLength' => v::length(null, (int)$params),
            'length' => v::length((int)$params),
            'between' => $this->applyBetween($params),
            default => v::alwaysValid()
        };
    }

    private function applyBetween(string $params): v
    {
        [$min, $max] = explode(',', $params);
        return v::between((int)trim($min), (int)trim($max));
    }

    private function parseErrors(ValidationException $e): void
    {
        if (method_exists($e, 'getMessages')) {
            /** @phpstan-ignore-next-line */
            $messages = $e->getMessages();
            
            if (is_array($messages)) {
                foreach ($messages as $field => $message) {
                    if (!isset($this->errors[$field])) {
                        $this->errors[$field] = [];
                    }
                    
                    if (is_array($message)) {
                        $this->errors[$field] = array_merge($this->errors[$field], $message);
                    } else {
                        $this->errors[$field][] = $message;
                    }
                }
                return;
            }
        }
        
        $this->errors['_general'][] = $e->getMessage();
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function getAllErrors(): array
    {
        $allErrors = [];
        foreach ($this->errors as $field => $messages) {
            $allErrors[$field] = $messages[0] ?? '';
        }
        return $allErrors;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }
}

