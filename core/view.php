<?php

use helpers\StringHelper;

/**
 * Class View
 */
class View
{
    private $controllerFolder;

    /**
     * View constructor.
     *
     * @param string $controller
     */
    public function __construct(string $controller)
    {
        $this->controllerFolder = StringHelper::stringToWeb($controller);
        $this->controllerFolder = str_replace('-controller', '', $this->controllerFolder);
    }

    /**
     * @param string $template_view
     * @param null   $data
     */
    public function render(string $template_view, $data = null)
    {
        if (is_array($data)) {
            /** Convert array to vars */
            extract($data);
        }

        include 'views/' . $this->controllerFolder . '/' . $template_view . '.php';
    }
}