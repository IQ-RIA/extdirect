<?php

namespace iqria\extdirect\rest;

use yii;
use yii\helpers\Url;
use yii\web\HttpException;

/**
 * CreateAction implements the API endpoint for creating a new model from the given data.
 * However the core action uses bodyParams() to get the request this action supports rawBody()
 *
 * @author Stanislav Hudkov <stanislav.hudkov@iqria.com>
 * @version 1.0
 */
class CreateAction extends \yii\rest\CreateAction
{
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);

        $model->load($this->controller->actionParams, '');
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
        } elseif (!$model->hasErrors()) {
            throw new HttpException(500, 'Failed to create the object for unknown reason.');
        }

        return $model;
    }
}
