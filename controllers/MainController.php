<?php

class MainController extends Controller
{
    public function actionIndex()
    {
        if ($_POST) {
            Main::sendForm($_POST);
        }

        $this->view->render('index');
    }
}