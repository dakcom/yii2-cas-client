<?php

/**
 * @license MIT License
 */

namespace daxslab\yii2casclient\cas;

use phpCAS;
use Yii;
use yii\helpers\Url;

/**
 * Wrapper on phpCAS
 *
 * @author François Gannaz <francois.gannaz@poofe.info>
 */
class CasService extends \yii\base\BaseObject
{
    const LOGPATH = '@runtime/logs/cas.log';

    public $host;
    public $port;
    public $path;
    public $profileClass;
    public $userClass;

    /**
     *
     * @var string|boolean If defined, local path to a SSL certificate file,
     *                     or false to disable the certificate validation.
     */
    public $certfile;

    public function init()
    {
        if (!isset($this->host, $this->port, $this->path)) {
            throw new \Exception("Incomplete CAS config. Required: host, port, path.");
        }
        // Force a Yii session to open to prevent phpCas from doing it on its own
        Yii::$app->session->open();
        // Init the phpCAS singleton
        phpCAS::setDebug(Yii::getAlias(self::LOGPATH));
        phpCAS::client(CAS_VERSION_3_0, $this->host, (int) $this->port, $this->path);
        if ($this->certfile) {
            phpCAS::setCasServerCACert($this->certfile);
        } else if ($this->certfile === false) {
            phpCAS::setNoCasServerValidation();
        }
    }

    /**
     * Try to authenticate the current user.
     *
     * @return boolean
     */
    public function forceAuthentication()
    {
        phpCAS::setFixedServiceURL(Url::current([], true));
        if ($this->certfile) {
            phpCAS::setCasServerCACert($this->certfile);
        } else{
            phpCAS::setNoCasServerValidation();
        }

        if (phpCAS::forceAuthentication()){
            $id = $this->getAttributes()['id'];
            if (!$user = $this->userClass::findOne($id))
                $user = new $this->userClass();

            $user->username = $this->getUsername();
            $remoteAttributes = $this->getAttributes();

            if (isset($remoteAttributes['id'])){
                $user->id = $remoteAttributes['id'];
            }

            if (isset($remoteAttributes['email']))
                $user->email = $remoteAttributes['email'];

            $user->password_hash = '';
            $user->auth_key = '';
            $user->save();

            if (!$profile = $this->profileClass::findOne(['user_id' => $user->id]))
                $profile = new $this->profileClass;

            $localAttribures = $this->profileClass::getTableSchema()->columns;
            foreach ($remoteAttributes as $remoteKey => $remoteAttribute){
                foreach ($localAttribures as $localKey => $value) {
                    if ($remoteKey == $localKey){
                        $profile->$localKey = $remoteAttribute;
                    }
                }
            }
            $profile->user_id = $user->id;

            if (isset($localAttribures['created_by']))
                $profile->created_by = $user->id;
            if (isset($localAttribures['updated_by']))
                $profile->updated_by = $user->id;

            $profile->save();
            return $user;
        }

        return false;
    }

    /**
     * Check if the current user is already authenticated.
     *
     * @return boolean
     */
    public function checkAuthentication()
    {
        return phpCAS::checkAuthentication();
    }

    /**
     * Logout on the CAS server. The user is then redirected to $url.
     *
     * @param string $url
     */
    public function logout($url)
    {
        if (phpCAS::isAuthenticated()) {
            phpCAS::logout(['service' => $url]);
        }
    }

    /**
     * Return the username if authenticated by CAS, else the empty string.
     *
     * @return string
     */
    public function getUsername()
    {
        if (phpCAS::isAuthenticated()) {
            return phpCAS::getUser();
        } else {
            return "";
        }
    }

    public function getAttributes(){
        if (phpCAS::isAuthenticated()){
            return phpCAS::getAttributes();
        }
        return '';
    }

    /**
     * Toggle the CAS debug mode that will add more logs into self::LOGPATH.
     *
     * @param boolean $debug
     * @return $this
     */
    public function setDebug($debug = true)
    {
//        phpCAS::setVerbose($debug);
        return $this;
    }
}
