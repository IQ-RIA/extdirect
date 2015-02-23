<?php

namespace iqria\extdirect\rest;

use yii\web\HttpException;

/**
 * UpdateAction implements the API endpoint for updating a model.
 *
 * @author Stanislav Hudkov <stanislav.hudkov@iqria.com>
 * @version 1.0
 */
class UpdateAction extends \yii\rest\UpdateAction
{
    public function run()
    {
        $attributes = $this->controller->actionParams;

        if (!$attributes['id']) {
            throw new HttpException('Invalid id.');
        }

        /* @var $model ActiveRecord */
        $model = $this->findModel($attributes['id']);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $model->scenario = $this->scenario;
        $model->load($attributes, '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new HttpException(500, 'Failed to update the object for unknown reason.');
        }

        return $model;
    }
}