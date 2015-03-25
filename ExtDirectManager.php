<?php

namespace iqria\extdirect;

use yii;
use yii\base\Component;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class ExtDirectManager provides Yii2 implementation of Ext.Direct
 *
 * @author Stanislav Hudkov <stanislav.hudkov@iqria.com>
 * @version 1.0
 * @package iqria\extdirect
 */
class ExtDirectManager extends Component
{
    const API_JSON = 'json';

    const API_JS = 'js';

    /**
     * Set of classes that should be accessed directly from client side
     * @var array
     */
    public $directClasses = [];

    /**
     * @var string The unique id of the provider (defaults to an auto-assigned id).
     * You should assign an id if you need to be able to access the provider later and you do not have an object reference available
     */
    public $id = '';

    /**
     * @var string The url to connect to the Ext.direct.Manager server-side router.
     */
    public $url;

    /**
     * @var string Namespace for the Remoting Provider (defaults to Ext.global).
     * Explicitly specify the namespace Object, or specify a String to have a namespace created implicitly.
     */
    public $namespace = 'API';

    /**
     * @var string Object literal defining the server side actions and methods.
     */
    public $actions = [];

    /**
     * @var string API descriptor
     */
    public $descriptor = 'API.desc';

    /**
     * @var bool debug mode
     */
    public $debug = false;
    /**
     * @inheritdoc
     */
    public function init()
    {
        //@todo validate public properties that set up from config before launch

        parent::init();
    }

    /**
     * @param array $classList
     */
    public function setDirectClasses(array $classList)
    {
        $this->directClasses = $classList;
    }

    /**
     * @return array
     */
    public function getDirectClasses()
    {
        return $this->directClasses;
    }

    /**
     * Get all controller's actions annotated with '@direct' + default rest actions
     * @return array
     * @throws \yii\web\HttpException
     */
    public function getActionsList()
    {
        if (!$this->directClasses) {
            throw new HttpException(500, 'Please provide direct classes.');
        }

        $actions = [];

        foreach ($this->directClasses as $class) {
            $reflection = new \ReflectionClass($class);
            $reflectionMethods = $reflection->getMethods();
            $className = $reflection->getShortName();
            $actionsAnnotated = $this->getAnnotatedActions($className, $reflectionMethods);
            $actionsDefault = $this->getDefaultActions($class);
            $actions = array_merge($actions, ArrayHelper::merge($actionsDefault, $actionsAnnotated));
        }

        return $actions;
    }

    /**
     * Get actions annotated with @direct annotation
     * @param string $className
     * @param array $methods
     * @return array
     */
    private function getAnnotatedActions($className, array $methods)
    {
        $annotatedActions = [];

        foreach ($methods as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            if ($method->isStatic()) {
                continue;
            }

            $actionNameChunks = explode('action', $method->name);

            if ($method->name === 'actions' || count($actionNameChunks) !== 2) {
                continue;
            }

            $docBlock = $method->getDocComment();
            preg_match('/@direct/', $docBlock, $annotation);

            if (!empty($annotation[0])) {
                $annotatedActions[$this->getControllerName($className)][] = [
                    'name' => lcfirst($actionNameChunks[1]),
                    'len' => $this->getParamsNumber($method)
                ];
            }
        }

        return $annotatedActions;
    }

    /**
     * Get rest standalone actions
     * @param string $class
     * @return array
     */
    private function getDefaultActions($class)
    {
        $className = (new \ReflectionClass($class))->getShortName();
        $actions = (new $class(lcfirst($className), false))->actions();

        $actionsPrepared = [];

        foreach ($actions as $name => $action) {
            $reflection = new \ReflectionClass($action['class']);
            $method = $reflection->getMethod('run');
            $actionsPrepared[$this->getControllerName($className)][] = [
                'name' => $name,
                'len' => $this->getParamsNumber($method)
            ];
        }

        return $actionsPrepared;
    }
    
    /**
     * Get number of parameters
     * @param \ReflectionMethod $method
     * @return int
     */
    private function getParamsNumber($method)
    {
        return $method->getNumberOfRequiredParameters() ?
               $method->getNumberOfRequiredParameters() :
               $method->getNumberOfParameters();
    }

    /**
     * Handle which api should be returned
     * @param $apiType
     * @return string
     * @throws \yii\web\HttpException
     */
    public function getApi($apiType)
    {
        if (strcmp($apiType, static::API_JSON) === 0) {
            return $this->getApiJson();
        } elseif (strcmp($apiType, static::API_JS) === 0) {
            return $this->getApiJs();
        } else {
            throw new HttpException(500, 'Wrong API type.');
        }
    }

    /**
     * Get API as array
     * @return array
     */
    public function getApiArray()
    {
        $api = [
            'url'        => $this->getApiUrl(),
            'type'       => 'remoting',
            'namespace'  => $this->namespace,
            'actions'    => $this->getActionsList()
        ];

        if ($this->id) {
            $api['id'] = $this->id;
        }

        return $api;
    }

    /**
     * Get API as javascript
     */
    public function getApiJs()
    {
        $apiJson = $this->getApiJson();

        $jsTemplate = <<<JAVASCRIPT

$this->namespace = {};
$this->descriptor = $apiJson;

JAVASCRIPT;

        Yii::$app->response->headers->add('Content-Type', 'application/javascript');
        return $jsTemplate;
    }

    /**
     * Get API as JSON
     * @return string
     */
    public function getApiJson()
    {
        Yii::$app->response->headers->add('Content-Type', 'application/json');
        return Json::encode($this->getApiArray());
    }

    /**
     * Get API endpoint
     * @return string
     */
    public function getApiUrl()
    {
        return empty($this->url) ? Yii::$app->request->url : $this->url;
    }

    /**
     * Get controller name from class name. Ex: ProductController -> product
     * @param $className
     * @return string
     * @throws \yii\web\HttpException
     */
    private function getControllerName($className)
    {
        $chunks = explode('Controller', $className);

        if (count($chunks) === 2) {
            return $chunks[0];
        }

        throw new HttpException(500, 'Invalid controller name.');
    }

    /**
     * Run single action or batch and return result
     * @param array $requestBody
     * @return array
     */
    public function processRequest($requestBody)
    {
        $response = [];

        if (isset($requestBody[0]) && is_array($requestBody[0])) {
            foreach ($requestBody as $req) {
                $route = $req['action'] . '/' . $req['method'];
                $response[] = $this->runAction($route, $req);
            }
        } else {
            $response = $this->runAction($requestBody['action'] . '/' . $requestBody['method'], $requestBody);
        }

        return $response;
    }

    /**
     * Run single action and return its result
     * @param string $route
     * @param array $params
     * @return array
     * @throws yii\web\UnauthorizedHttpException
     */
    private function runAction($route, $params)
    {
        $route = substr(strtolower(preg_replace("/[A-Z]/", '-\\0', $route)), 1);

        $response = [
            'type'    => 'rpc',
            'tid'     => $params['tid'],
            'action'  => $params['action'],
            'method'  => $params['method'],
        ];

        try {
            $params = is_null($params['data']) ? [] : $params['data'];
            if (isset($params[0]) && is_array($params[0]) && count($params) === 1) {
                $params = $params[0];
            }
            $routeInfo = Yii::$app->createController($route);
            $response['result'] = $routeInfo[0]->runAction($routeInfo[1], $params);
        } catch (\Exception $e) {
            if ($e instanceof yii\web\UnauthorizedHttpException) {
                throw $e;
            } else {
                $response['result'] = 'exception';
                if ($this->debug) {
                    $response['result'] = [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ];
                }
            }
        }

        return $response;
    }
}
