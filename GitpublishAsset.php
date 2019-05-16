<?php

namespace dreamzml\SimpleGitPublish;

use yii\web\AssetBundle;

/**
 * This declares the asset files required by Gii.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class GitpublishAsset extends AssetBundle
{
    public $sourcePath = '@dreamzml/yii2-simplegitpublish/assets';
    public $css = [
        'gitpublish.css',
    ];
    public $js = [
        'tether.min.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
        'dreamzml\SimpleGitPublish\TypeAheadAsset',
    ];
}
