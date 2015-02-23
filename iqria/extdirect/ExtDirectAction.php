<?php

namespace iqria\extdirect;

use yii;
use yii\base\Action;
use yii\helpers\Json;
use yii\web\Response;

/**
 * Class ExtDirectAction returns server's API and handles the request
 *
 * @author Stanislav Hudkov <stanislav.hudkov@iqria.com>
 * @version 1.0
 * @package iqria\extdirect
 */
class ExtDirectAction extends Action
{
    protected $requestBody;

    /**
     * Attach response behavior to controller where the action is.
     *    - raw text in case of getting API
     *    - json when the data need to be processed
     */
    public function init()
    {
        $this->requestBody = Json::decode(Yii::$app->request->getRawBody());

        if (!$this->controller->getBehavior('responseFormatter')) {
            $this->controller->attachBehavior('responseFormatter', [
                'class' => 'yii\filters\ContentNegotiator',
                    'formats' => [
                        !$this->requestBody ? Response::FORMAT_RAW : Response::FORMAT_JSON,
                    ]
            ]);
        }

        parent::init();
    }

    /**
     * @param string $api js|json are allowed parameters
     * @see ExtDirectManager API constants
     * @return mixed
     */
    public function run($api = null)
    {
        if (!$this->requestBody) {
            return Yii::$app->extDirect->getApi($api);
        } else {
            return $this->processRequest($this->requestBody);
        }
    }

    protected function processRequest($body)
    {
        return Yii::$app->extDirect->processRequest($body);
    }
}