<?php

class Controller {
    public $model;
    public $view;

    public function __construct()
    {
        $this->view = new View(get_class($this));
    }

    public function actionIndex()
    {
    }
}