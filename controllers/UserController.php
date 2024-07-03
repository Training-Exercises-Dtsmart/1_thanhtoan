<?php


namespace app\controllers;

use Yii;
use app\models\User;
use app\models\Users;

use yii\rest\Controller;
use app\models\form\UserForm;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class UserController extends Controller
{



    // Test CRUD User batch generation
    public function actionIndexBatch()
    {
        $listUser = User::find()->active()->all();
        return $listUser;
    }

    public function actionCreateBatch()
    {
        $userForm = new UserForm();
        $userForm->load(Yii::$app->request->post());
        if ($userForm->validate()) {
            $userForm->save();
            return $userForm;
        } else {
            return $userForm->getErrors();
        }
    }

    public function actionDeleteBatch($user_id)
    {
        $user = User::findOne($user_id);
        if ($user) {
            if ($user->delete()) {
                return ['status' => true, 'data' => ['now' => date('d/m/Y')], 'message' => 'User deleted success'];
            } else {
                return ['status' => false, 'data' => ['now' => date('d/m/Y')], 'message' => 'Delete Failed!'];
            }
        } else {
            throw new NotFoundHttpException('User not found.');
        }
    }

    public function actionUpdateBatch($user_id)
    {
        $user = User::findOne($user_id);
        if ($user === null) {
            throw new NotFoundHttpException('User not found.');
        }

        $user->load(Yii::$app->request->post());
        if ($user->save()) {
            return [
                'status' => 'success', 'now' => date('d/n/Y'), 'message' => 'User updated successfully.', 'user' => $user
            ];
        } else {
            throw new ServerErrorHttpException('Failed to update the user.');
        }
    }

    //end


    public function actionIndex()
    {
        $listUser = Users::find()->orderBy("id")->all();
        return ['status' => true, 'data' => ['ListUser' => $listUser, 'now' => date('d/m/Y')], 'message' => 'success'];
    }




    public function actionCreate()
    {

        $user = new Users();
        // $user->username = 'thinh';
        // $user->password_hash = 'ddsadadsadsd321';
        // $user->age = '21';

        $user->username = Yii::$app->request->post('username');
        $user->email = Yii::$app->request->post('email');

        $user->password_hash = Yii::$app->getSecurity()->generatePasswordHash(Yii::$app->request->post('password'));
        $user->save();
    }

    // public function actionDelete($id)
    // {
    //     $user = Users::findOne($id);
    //     if ($user->delete()) {
    //         return ['status' => true, 'data' => ['now' => date('d/m/Y')], 'message' => 'success'];
    //     }
    // }

    public function actionLogin()
    {
        $dataRequest = Yii::$app->request->post();
        if (isset($dataRequest['username']) && $dataRequest['password']) {
            $userAccount = 'toan';
            $passwordAccount = md5(123);
            if ($dataRequest['username'] === $userAccount && md5($dataRequest['password']) === $passwordAccount) {
                return [
                    'status' => true,
                    'data' => [
                        'now' => date('d/m/Y')
                    ],
                    'message' => 'success'
                ];
            } else {
                return [
                    'status' => false,
                    'data' => [
                        'now' => date('d/m/Y')
                    ],
                    'message' => 'Invalid username or password'
                ];
            }
        } else {
            return [
                'status' => false,
                'data' => [
                    'now' => date('d/m/Y')
                ],
                'message' => 'empty input'
            ];
        }
    }
}
