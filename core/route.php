<?php
use helpers\StringHelper;

class Route
{
    private static $controllerName = 'Main';
    private static $actionName     = 'index';

    public static function init()
    {
        $routes = explode('/', $_SERVER['REQUEST_URI']);

        /** Get controller name */
        if (!empty($routes[1]) ) {
            self::$controllerName = StringHelper::webPathToString($routes[1]);
        }

        /** Get action name */
        if (!empty($routes[2]) ) {
            self::$actionName = StringHelper::webPathToString($routes[2]);
        }

        /** Include model file */
        $model_path = "models/" . self::$controllerName . '.php';

        if (file_exists($model_path)) {
            include $model_path;
        }

        /** Add prefixes */
        self::$controllerName = self::$controllerName . 'Controller';
        self::$actionName     = 'action'.self::$actionName;

        /** Include controller file */
        $controller_path = 'controllers/' . self::$controllerName . '.php';

        if(file_exists($controller_path)) {
            include $controller_path;
        } else {
            throw new Exception('Can\'t find page!');
        }

        /** Create controller object */
        $controller = new self::$controllerName();
        $action     = self::$actionName;

        if(method_exists($controller, $action))  {
            $controller->$action();
        } else {
            throw new Exception('Can\'t find page!');
        }
    }
}