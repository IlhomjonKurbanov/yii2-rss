Yii2 RSS
==========================
Yii2 module for automatically generating RSS 2.0 Feed.

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require "lan143/yii2-rss" "*"
```

or add

```json
"lan143/yii2-rss": "*"
```

to the `require` section of your application's `composer.json` file and run `composer update`.

Configuration
-------------

* Configure the `cache` component of your application's configuration file, for example:

```php
'components' => [
    'cache' => [
        'class' => 'yii\caching\FileCache',
    ],
]
```


* Add a new module in `modules` section of your application's configuration file, for example:

```php
'modules' => [
    'rss' => [
        'class' => \lan143\yii2-rss\Rss::class,
        'channels' => [
            // one model per channel
            [
                'model' => \app\models\Records::class,
            ],
            // or configuration for creating a behavior
            [
                'title' => 'Liftoff News', // not required, default Application name 
                'link' => 'http://liftoff.msfc.nasa.gov/', // not required, default Url::home
                'description' => 'Liftoff to Space Exploration.', // default empty
                'language' => 'en-us', // not required, default Application language
                'model' => [
                    'class' => \app\models\Records::class,
                    'behaviors' => [
                        'rss' => [
                            'class' => \lan143\yii2-rss\RssBehavior::class,
                            'scope' => function (\yii\db\ActiveQuery $query) {
                                $query->orderBy(['created_at' => SORT_DESC]);
                            },
                            'dataClosure' => function (\app\models\Records $model) {
                                return [
                                    'title' => $model->title,
                                    'link' => \yii\helpers\Url::to(['records/view', 'id' => $model->id], true),
                                    'description' => $model->description,
                                    'pubDate' => (new \DateTime($this->created_at))->format(\DateTime::RFC822),
                                ];
                            }
                        ],
                    ],
                ],
            ],
        ],
        'cacheExpire' => 1, // 1 second. Default is 15 minutes
    ],
],
```

* Add behavior in the AR models, for example:

```php
use DateTime;
use lan143\yii2-rss\RssBehavior;
use yii\db\ActiveQuery;
use yii\helpers\Url;

public function behaviors()
{
    return [
        'rss' => [
            'class' => RssBehavior::class,
            'scope' => function (ActiveQuery $query) {
                $query->orderBy(['created_at' => SORT_DESC]);
            },
            'dataClosure' => function (self $model) {
                return [
                    'title' => $model->title,
                    'link' => Url::to(['records/view', 'id' => $model->id], true),
                    'description' => $model->description,
                    'pubDate' => (new DateTime($this->created_at))->format(DateTime::RFC822),
                ];
            }
        ],
    ];
}
```


* Add a new rule for `urlManager` of your application's configuration file, for example:

```php
'urlManager' => [
    'rules' => [
        ['pattern' => 'rss', 'route' => 'rss/rss/index', 'suffix' => '.xml'],
    ],
],
```