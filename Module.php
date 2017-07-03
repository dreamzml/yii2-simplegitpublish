<?php

namespace dreamzml\SimpleGitPublish;

use Yii;
use yii\base\BootstrapInterface;

/**
 * simplegitpublish module definition class
 */
class Module extends \yii\base\Module
{
    public $allowedIPs = ['127.0.0.1', '::1'];
    
    public $monitors = ['admin'=>'123456'];
    
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'dreamzml\SimpleGitPublish\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        //add bootstrop alias
        $packDir = dirname(__DIR__);
        Yii::setAlias('@dreamzml', $packDir);
        // custom initialization code goes here
    }
    
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        
        if (Yii::$app instanceof \yii\web\Application && !$this->checkAccess()) {
            throw new ForbiddenHttpException('You are not allowed to access this page.');
        }
        
        if (!$this->checkAuthor()) {
            Header("WWW-Authenticate: Basic realm=\"MEMC Login\"");
            Header("HTTP/1.0 401 Unauthorized");
            echo "<html><body><h1>Rejected!</h1><big>Wrong Username or Password!</big></body></html>";
            exit;
        }
        
        return true;
    }
    
    /**
     * @return int whether the module can be accessed by the current user
     */
    protected function checkAccess()
    {
        $ip = Yii::$app->getRequest()->getUserIP();
        foreach ($this->allowedIPs as $filter) {
            if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))) {
                return true;
            }
        }
        Yii::warning('Access to Gii is denied due to IP address restriction. The requested IP is ' . $ip, __METHOD__);
        
        return false;
    }
    /**
     * 验证防客权限
     * @param $user
     * @param $password
     *
     * @return bool
     */
    public function checkAuthor(){
        if($this->monitors==='*')
            return true;
        
        if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
            return false;
        
        $user = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        return $this->monitors[$user] && $this->monitors[$user]==$password;
    }
}
