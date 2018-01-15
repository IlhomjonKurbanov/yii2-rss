<?php

namespace lan143\yii2_rss;

use DateTime;
use DOMDocument;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\caching\Cache;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class Rss
 * @package lan143\yii2_rss
 */
class Rss extends Module
{
    /**
     * @var string
     */
    public $controllerNamespace = 'lan143\yii2_rss';

    /**
     * @var int
     */
    public $cacheExpire = 900;

    /**
     * @var Cache|string
     */
    public $cacheProvider = 'cache';

    /**
     * @var string
     */
    public $cacheKey = 'rss';

    /**
     * @var array
     */
    public $channels = [];

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if (is_string($this->cacheProvider)) {
            $this->cacheProvider = Yii::$app->get($this->cacheProvider);
        }

        if (!$this->cacheProvider instanceof Cache) {
            throw new InvalidConfigException('Invalid `cacheKey` parameter was specified.');
        }
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getRssFeed(): string
    {
        if (!($xml = $this->cacheProvider->get($this->cacheKey))) {
            $xml = $this->buildRssFeed();

            $this->cacheProvider->set($this->cacheKey, $xml, $this->cacheExpire);
        }

        return $xml;
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->cacheProvider->delete($this->cacheKey);
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    protected function buildRssFeed(): string
    {
        $channels = [];

        foreach ($this->channels as $channel) {
            if (is_array($channel['model'])) {
                $model = Yii::createObject([
                    'class' => $channel['model']['class']
                ]);

                if (isset($channel['model']['behaviors'])) {
                    $model->attachBehaviors($channel['behaviors']);
                }
            } elseif (is_string($channel['model'])) {
                $model = Yii::createObject([
                    'class' => $channel['model'],
                ]);
            } else {
                throw new InvalidConfigException('You must set model variable or unsupported model type');
            }

            $channels[] = [
                'title' => ArrayHelper::getValue($channel, 'title', Yii::$app->name),
                'link' => ArrayHelper::getValue($channel, 'link', Url::home()),
                'description' => ArrayHelper::getValue($channel, 'description'),
                'language' => ArrayHelper::getValue($channel, 'language', Yii::$app->language),
                'lastBuildDate' => (new DateTime())->format(DateTime::RFC822),
                'generator' => 'lan143\yii2-rss',
                'items' => $model->generateItems(),
            ];
        }

        $xml = $this->buildRssXml($channels);

        return $xml;
    }

    /**
     * @param array $channels
     * @return string
     */
    protected function buildRssXml(array $channels): string
    {
        $doc = new DOMDocument("1.0", "utf-8");

        $root = $doc->createElement("rss");
        $root->setAttribute('version', '2.0');
        $doc->appendChild($root);

        foreach ($channels as $channel) {
            $channelNode = $doc->createElement("channel");
            $root->appendChild($channelNode);

            $titleNode = $doc->createElement("title", $channel['title']);
            $channelNode->appendChild($titleNode);

            $linkNode = $doc->createElement("link", $channel['link']);
            $channelNode->appendChild($linkNode);

            $descriptionNode = $doc->createElement("description", $channel['description']);
            $channelNode->appendChild($descriptionNode);

            $languageNode = $doc->createElement("language", $channel['language']);
            $channelNode->appendChild($languageNode);

            $lastBuildDateNode = $doc->createElement("lastBuildDate", $channel['lastBuildDate']);
            $channelNode->appendChild($lastBuildDateNode);

            $generatorNode = $doc->createElement("generator", $channel['generator']);
            $channelNode->appendChild($generatorNode);

            foreach ($channel['items'] as $item) {
                $itemNode = $doc->createElement("item");
                $channelNode->appendChild($itemNode);

                $itemTitleNode = $doc->createElement("title", $item['title']);
                $itemNode->appendChild($itemTitleNode);

                $itemLinkNode = $doc->createElement("link", $item['link']);
                $itemNode->appendChild($itemLinkNode);

                $itemDescriptionNode = $doc->createElement("description", $item['description']);
                $itemNode->appendChild($itemDescriptionNode);

                $itemPubDateNode = $doc->createElement("pubDate", $item['pubDate']);
                $itemNode->appendChild($itemPubDateNode);
            }
        }

        return $doc->saveXML();
    }
}