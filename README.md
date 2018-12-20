yii2-auth-cas
=============

Yii2 library for authentication by CAS,
using the library [phpCAS](https://wiki.jasig.org/display/CASC/phpCAS).

Usage
-----

1. Add this to the project with `composer require poofe/yii2casclient`

2. Configure the Yii2 application, e.g. in `backend/config/main.php` :

    ```
    return [
        ...
        'session' => [
            'class' => 'poofe\yii2casclient\cas\Session',
            ...
        ],
        'modules' => [
            'cas' => [
                'class' => 'poofe\yii2casclient\cas\CasModule',
                'config' => [
                    'host' => 'ssoserver.example.com',
                    'port' => '443',
                    'path' => '/idp/profile/cas',
                    // optional parameters
                    'certfile' => false, // empty, or path to a SSL cert, or false to ignore certs
                    'debug' => true, // will add many logs into X/runtime/logs/cas.log
                ],
            ],
    ```

3. Add actions that use this CAS module, e.g. in `SiteController` :

    ```
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        return $this->redirect(['/cas/auth/login']);
    }

    public function actionLogout()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/cas/auth/logout']);
        }
        return $this->goHome();
    }
    ```


Notes
-----

The `user` component that implements `yii\web\IdentityInterface`
will be used to fetch the local profile after querying the CAS server.
It means that if `User` is the App component and CAS returns a username of "bibendum",
the authentication will be successful if and only if
the result of `User::findIdentity("bibendum")` is not null.

The action path '/cas/auth/login' starts with the alias of the module,
as defined in the application configuration, e.g.
`'cas'` in `'modules' => [ 'cas' => [ ... ] ]`.