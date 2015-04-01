<?php

namespace app\components\exceptions;

/**
 * Class ExtDirectValidationException represents validation exception
 * @package app\components\exceptions
 */
class ExtDirectValidationException extends \Exception
{
    /**
     * @var array validation errors
     */
    public $errors = [];

    /**
     * @inheritdoc
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null, $errors = [])
    {
        $this->setErrors($errors);
        parent::__construct($message, $code, $previous);
    }

    /**
     * Set validation errors
     * @param array $errors
     */
    public function setErrors($errors = [])
    {
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
