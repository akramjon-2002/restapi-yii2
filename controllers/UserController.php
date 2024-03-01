<?php

namespace app\controllers\api;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use app\models\User;

class UserController extends ActiveController
{
    public $modelClass = 'app\models\User';

    public function behaviors()
    {
        return [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
                'except' => ['login', 'signup'],
            ],
        ];
    }

    public function actionLogin()
    {
        $username = Yii::$app->request->post('username');
        $password = Yii::$app->request->post('password');

        $user = User::findOne(['username' => $username]);

        if ($user && $user->validatePassword($password)) {
            $token = Yii::$app->security->generateRandomString();
            $user->access_token = $token;
            $user->save();

            return [
                'success' => true,
                'token' => $token,
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Неверный логин или пароль.'],
            ];
        }
    }

    public function actionValidate()
    {
        $token = Yii::$app->request->headers['Authorization'];

        $user = User::findOne(['access_token' => $token]);

        if ($user) {
            return [
                'success' => true,
                'user' => $user->attributes,
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Недействительный токен доступа.'],
            ];
        }
    }



    public function actionSignup()
    {
        $model = new User();
        $model->load(Yii::$app->request->post(), '');

        if ($model->validate()) {
            $model->password_hash = Yii::$app->security->generatePasswordHash($model->password);
            $model->generateAuthKey();
            $model->generatePasswordResetToken();
            $model->verification_token = Yii::$app->security->generateRandomString();

            if ($model->save()) {
                return [
                    'success' => true,
                    'message' => 'register success.',
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => $model->errors,
                ];
            }
        } else {
            return [
                'success' => false,
                'errors' => $model->errors,
            ];
        }
    }

}