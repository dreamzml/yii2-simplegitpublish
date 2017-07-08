Simple Git publish 
========================

This Module Extension for Yii 2, Small teams, multi project development, testing model

For license information check the [LICENSE](LICENSE.md)-file.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require dreamzml/yii2-simplegitpublish --prefer-dist
```

or add

```
"dreamzml/yii2-simplegitpublish": "*"
```

to the require-dev section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply modify your application configuration as follows:

```php
return [
    'modules' => [
        'gitpublish' => [
            'class' => 'dreamzml\SimpleGitPublish\Module',            
            'allowedIPs' => ['127.0.0.1', '::1'],            // if set ['*'] allow all ip
            'monitors'   => [
                    'MASTER'=>'123456',    // MASTER allow push to master branch     
                    'TESTER'=>'111', 
                ], //allow users, if set * allow all user
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
