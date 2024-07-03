<?php

namespace app\controllers;

use app\models\form\ProductForm;
use app\models\Product;
use Yii;
use yii\rest\Controller;

class ProductController extends controller
{
    public function actionIndex()
    {
        $products = Product::find()->all();
        return $products;
    }

    // public function actionCreate()
    // {
    //     $product = new Product();
    //     $product->username

    //     $user->username = Yii::$app->request->post('username');
    //     $user->password_hash = Yii::$app->getSecurity()->generatePasswordHash(Yii::$app->request->post('password'));
    //     $user->age = Yii::$app->request->post('age');

    //     $user->save();
    // }
}