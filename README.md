Gii Extension for Yii 2
========================

This extension provides a Web-based code generator, called Gii, for [Yii framework 2.0](http://www.yiiframework.com) applications.
You can use Gii to quickly generate models, forms, modules, CRUD, etc.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii2-gii/v/stable.png)](https://packagist.org/packages/yiisoft/yii2-gii)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii2-gii/downloads.png)](https://packagist.org/packages/yiisoft/yii2-gii)
[![Build Status](https://travis-ci.org/yiisoft/yii2-gii.svg?branch=master)](https://travis-ci.org/yiisoft/yii2-gii)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --dev --prefer-dist dreamzml/yii2-gii
```

or add

```
"dreamzml/yii2-gii": "*"
```

to the require-dev section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply modify your application configuration as follows:

```php
return [
    'bootstrap' => ['gii'],
    'modules' => [
        'gitpublish' => [
            'class' => 'dreamzml\SimpleGitPublish\Module'
        ],
    ],
    // ...
];
```

You can then access Gii through the following URL:

```
http://localhost/path/to/index.php?r=gitpublish
```

or if you have enabled pretty URLs, you may use the following URL:

```
http://localhost/path/to/index.php/gitpublish
```
