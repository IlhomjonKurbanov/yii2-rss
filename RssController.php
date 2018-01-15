<?php

namespace lan143\yii2_rss;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class RssController
 * @package lan143\yii2_rss
 */
class RssController extends Controller
{
    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex(): string
    {
        /** @var Rss $module */
        $module = $this->module;

        Yii::$app->response->format = Response::FORMAT_RAW;

        $headers = Yii::$app->response->headers;
        $headers->add('Content-Type', 'application/xml');

        return $module->getRssFeed();
    }
}