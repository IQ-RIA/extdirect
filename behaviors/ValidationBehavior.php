<?php

namespace app\components\behaviors;

use yii\base\Behavior;
use yii\validators\Validator;
use yii\base\InvalidParamException;
use yii\helpers\Inflector;
use iqria\extdirect\exceptions\ExtDirectValidationException;

/**
 * Class ValidationBehavior represents basic input validation for all app's controllers
 * @package app\components
 */
class ValidationBehavior extends Behavior
{
    /**
     * @param array $params the list of parameters (name => value) that should be validated
     * @param array $rules the list of validation rules for each of parameters keys
     * @return bool
     * @throws iqria\extdirect\exceptions\ExtDirectValidationException
     */
    public function validateInput($params, $rules)
    {
        if (is_null($params) || !is_array($params) || empty($params) || empty($rules)) {
            return;
        }

        $errors = [];

        $requiredAttributes = $this->getRequiredAttributes($rules);

        if ($requiredAttributes) {
            foreach ($requiredAttributes as $attr) {
                if (!in_array($attr, array_keys($params))) {
                    $errors[$attr][] = $this->getValidationError($attr, null, 'Missed required attribute {attribute}.');
                }
            }
        }

        foreach ($params as $attr => $value) {
            if (!in_array($attr, array_keys($rules))) {
                continue;
            }

            foreach ($rules[$attr] as $vName => $vAttrs) {
                if (is_array($vAttrs)) {
                    $validator = $this->createValidatorByName($vName, $vAttrs);
                } else {
                    $validator = $this->createValidatorByName($vAttrs);
                }

                if (!$validator->validate($value)) {
                   $errors[$attr][] = $this->getValidationError($attr, $validator);
                }
            }
        }

        if (!$errors) {
            return true;
        }

        throw new ExtDirectValidationException('Input validation errors.', 0, null, $errors);
    }

    /**
     * Creates new Validator instance by name
     * @param string $validatorName the validator name (see built-in validators)
     * @param array $config additional validator attributes
     * @see \yii\validators\Validator to ensure which attributes can be pssed to $config
     * @return Validator
     * @throws InvalidParamException
     */
    private function createValidatorByName($validatorName, $config = [])
    {
        $builtInValidatorsNames = array_keys(Validator::$builtInValidators);

        if (!$validatorName || !in_array(strtolower($validatorName), $builtInValidatorsNames)) {
            throw new InvalidParamException('Invalid validator name passed.');
        }

        $validator = new Validator::$builtInValidators[$validatorName];

        if ($config) {
            foreach ($config as $name => $val) {
                $validator->{$name} = $val;
            }
        }

        return $validator;
    }

    /**
     * Get error message if validation fails
     * @param null|Validator $validator
     * @param string $attribute the attribute that should be validated
     * @param null|string $customMessage the custom error message
     * @return string
     */
    private function getValidationError($attribute, $validator = null, $customMessage = null)
    {
        $message = '';

        if ($validator) {
            $message .= $validator->message;
        } else {
            $message .= $customMessage;
        }

        return str_replace(['{', '}'], ['', ''], strtr($message, [
            '{attribute}' => Inflector::camel2words($attribute)
        ]));
    }


    /**
     * Get required attributes
     * @param array $rules
     * @return array
     */
    private function getRequiredAttributes($rules)
    {
        $requiredAttributes = [];

        foreach ($rules as $k => $rule) {
            if (!is_array($rule)) {
                if ($rule === 'required') {
                    $requiredAttributes[$k] = $rule;
                }
            } else {
                $requiredAttributes[$k] = $this->getRequiredAttributes($rule);
            }
        }

        return array_keys($requiredAttributes);
    }
}
