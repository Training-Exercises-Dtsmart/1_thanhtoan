<?php

namespace app\modules\controllers;

// use yii\web\Controller;
use app\controllers\Controller;

/**
 * Default controller for the `modules` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex(): string
    {
        // return $this->render('index');
        return "default";
    }
}
