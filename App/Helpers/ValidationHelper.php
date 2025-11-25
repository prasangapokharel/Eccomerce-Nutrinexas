<?php
namespace App\Helpers;

/**
* Validation Helper
* Provides validation functionality
*/
class ValidationHelper
{
   private $errors = [];
   private $data = [];

   /**
    * Constructor
    *
    * @param array $data
    */
   public function __construct($data = [])
   {
       $this->data = $data;
   }

   /**
    * Set data to validate
    *
    * @param array $data
    * @return ValidationHelper
    */
   public function setData($data)
   {
       $this->data = $data;
       return $this;
   }

   /**
    * Validate required fields
    *
    * @param array $fields
    * @param string $message
    * @return ValidationHelper
    */
   public function required($fields, $message = '{field} is required')
   {
       foreach ($fields as $field) {
           if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
               $this->addError($field, str_replace('{field}', $this->formatFieldName($field), $message));
           }
       }
       
       return $this;
   }

   /**
    * Validate email
    *
    * @param string $field
    * @param string $message
    * @return ValidationHelper
    */
   public function email($field, $message = '{field} must be a valid email address')
   {
       if (isset($this->data[$field]) && !empty($this->data[$field])) {
           if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
               $this->addError($field, str_replace('{field}', $this->formatFieldName($field), $message));
           }
       }
       
       return $this;
   }

   /**
    * Validate minimum length
    *
    * @param string $field
    * @param int $length
    * @param string $message
    * @return ValidationHelper
    */
   public function minLength($field, $length, $message = '{field} must be at least {length} characters')
   {
       if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
           $message = str_replace('{field}', $this->formatFieldName($field), $message);
           $message = str_replace('{length}', $length, $message);
           $this->addError($field, $message);
       }
       
       return $this;
   }

   /**
    * Validate maximum length
    *
    * @param string $field
    * @param int $length
    * @param string $message
    * @return ValidationHelper
    */
   public function maxLength($field, $length, $message = '{field} must not exceed {length} characters')
   {
       if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
           $message = str_replace('{field}', $this->formatFieldName($field), $message);
           $message = str_replace('{length}', $length, $message);
           $this->addError($field, $message);
       }
       
       return $this;
   }

   /**
    * Validate numeric field
    *
    * @param string $field
    * @param string $message
    * @return ValidationHelper
    */
   public function numeric($field, $message = '{field} must be a number')
   {
       if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
           $this->addError($field, str_replace('{field}', $this->formatFieldName($field), $message));
       }
       
       return $this;
   }

   /**
    * Validate field matches another field
    *
    * @param string $field
    * @param string $matchField
    * @param string $message
    * @return ValidationHelper
    */
   public function matches($field, $matchField, $message = '{field} must match {matchField}')
   {
       if (isset($this->data[$field]) && isset($this->data[$matchField])) {
           if ($this->data[$field] !== $this->data[$matchField]) {
               $message = str_replace('{field}', $this->formatFieldName($field), $message);
               $message = str_replace('{matchField}', $this->formatFieldName($matchField), $message);
               $this->addError($field, $message);
           }
       }
       
       return $this;
   }

   /**
    * Validate field with custom function
    *
    * @param string $field
    * @param callable $callback
    * @param string $message
    * @return ValidationHelper
    */
   public function custom($field, $callback, $message = '{field} is invalid')
   {
       if (isset($this->data[$field])) {
           if (!$callback($this->data[$field])) {
               $this->addError($field, str_replace('{field}', $this->formatFieldName($field), $message));
           }
       }
       
       return $this;
   }

   /**
    * Check if validation passed
    *
    * @return bool
    */
   public function passes()
   {
       return empty($this->errors);
   }

   /**
    * Check if validation failed
    *
    * @return bool
    */
   public function fails()
   {
       return !$this->passes();
   }

   /**
    * Get all errors
    *
    * @return array
    */
   public function getErrors()
   {
       return $this->errors;
   }

   /**
    * Get first error for a field
    *
    * @param string $field
    * @return string|null
    */
   public function getError($field)
   {
       return $this->errors[$field][0] ?? null;
   }

   /**
    * Add an error
    *
    * @param string $field
    * @param string $message
    * @return void
    */
   public function addError($field, $message)
   {
       if (!isset($this->errors[$field])) {
           $this->errors[$field] = [];
       }
       
       $this->errors[$field][] = $message;
   }

   /**
    * Format field name for error messages
    *
    * @param string $field
    * @return string
    */
   private function formatFieldName($field)
   {
       return ucfirst(str_replace('_', ' ', $field));
   }
}
