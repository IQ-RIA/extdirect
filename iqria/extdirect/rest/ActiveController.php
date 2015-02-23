<?php

namespace iqria\extdirect\rest;

use Yii;
use yii\web\HttpException;

/**
 * Class ExtDirectActiveController is a customized ActiveController
 *
 * @author Stanislav Hudkov <stanislav.hudkov@iqria.com>
 * @version 1.0
 * @package iqria\extdirect\rest
 */
class ActiveController extends \yii\rest\ActiveController
{
    public function actions()
    {
        return [
            'index' => [
                'class' => 'iqria\extdirect\rest\IndexAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            'view' => [
                'class' => 'iqria\extdirect\rest\ViewAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            'create' => [
                'class' => 'iqria\extdirect\rest\CreateAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->createScenario,
            ],
            'update' => [
                'class' => 'iqria\extdirect\rest\UpdateAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->updateScenario,
            ],
            'delete' => [
                'class' => 'iqria\extdirect\rest\DeleteAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
        ];
    }

    /**
     * All actions support ONLY POST requests
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'index'  => ['POST'],
            'view'   => ['POST'],
            'create' => ['POST'],
            'update' => ['POST'],
            'delete' => ['POST'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function bindActionParams($action, $params)
    {
        if (!is_array($params)) {
            throw new HttpException(500, 'params variable must be an array.');
        }

        return $this->actionParams = $params;
    }
}
