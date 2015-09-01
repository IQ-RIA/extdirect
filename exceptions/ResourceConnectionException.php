<?php

namespace iqria\extdirect\exceptions;

use yii\web\HttpException;

/**
 * Class ResourceConnectionException represents exception that describes connection error to some resource such as database or remote API
 * @package app\components\db
 */
class ResourceConnectionException extends HttpException
{
}
