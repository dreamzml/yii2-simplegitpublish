<?php

namespace dreamzml\SimpleGitPublish;

use Yii;
use yii\base\BootstrapInterface;

/**
 * simplegitpublish module definition class
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
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
    public function bootstrap($app)
    {
        $app->getUrlManager()->addRules([
                ['class' => 'yii\web\UrlRule', 'pattern' => $this->id, 'route' => $this->id . '/default/index'],
                ['class' => 'yii\web\UrlRule', 'pattern' => $this->id . '/<id:\w+>', 'route' => $this->id . '/default/view'],
                ['class' => 'yii\web\UrlRule', 'pattern' => $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>', 'route' => $this->id . '/<controller>/<action>'],
            ], false);
    }
}
