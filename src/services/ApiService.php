<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\services;

use superbig\yotpo\models\OrderModel;
use superbig\yotpo\models\ProductModel;
use superbig\yotpo\Yotpo;

use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\helpers\Json;
use craft\web\View;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Craft;
use craft\base\Component;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 */
class ApiService extends Component
{
    public const API_URL = 'https://api.yotpo.com';

    // Private Properties
    // =========================================================================

    private $_settings;

    // Public Methods
    // =========================================================================

    /**
     * @param string $url
     * @param array  $query
     *
     * @return mixed|null
     */
    public function get($url = '', $query = [])
    {
        try {
            $client = new Client($this->getClientConfig());
            $url    = $this->_replaceUrlPlaceholders($url);

            if (!isset($query['utoken'])) {
                $query['utoken'] = $this->getToken();
            }

            dd($query);

            $request = $client->get($url, [
                'query' => $query,
            ]);

            $body = Json::decodeIfJson((string)$request->getBody());

            return $body;

        } catch (BadResponseException $e) {
            Craft::error(
                Craft::t('yotpo', "GET error for {url}: {error}.\n\nRequest: {requestBody}\n\nResponse: {responseBody}", [
                    'url'          => $url,
                    'error'        => $e->getMessage(),
                    'responseBody' => (string)$e->getResponse()->getBody(),
                    'requestBody'  => (string)$e->getRequest()->getBody(),
                ]),
                'yotpo'
            );

            return null;
        }
    }

    /**
     * @param string $url
     * @param array  $payload
     *
     * @return mixed|null
     */
    public function post($url = '', $payload = [])
    {
        try {
            $client  = new Client($this->getClientConfig());
            $url     = $this->_replaceUrlPlaceholders($url);
            $request = $client->post($url, [
                'json' => $payload,
            ]);

            $body = Json::decodeIfJson((string)$request->getBody());

            return $body;
        } catch (BadResponseException $e) {
            Craft::error(
                Craft::t('yotpo', "POST error for {url}: {error}.\n\nRequest: {requestBody}\n\nResponse: {responseBody}", [
                    'url'          => $url,
                    'error'        => $e->getMessage(),
                    'responseBody' => (string)$e->getResponse()->getBody(),
                    'requestBody'  => (string)$e->getRequest()->getBody(),
                ]),
                'yotpo'
            );

            return null;
        }
    }

    /**
     * @param string $url
     * @param array  $payload
     *
     * @return mixed|null
     */
    public function put($url = '', $payload = [])
    {
        try {
            $client  = new Client($this->getClientConfig());
            $request = $client->put($url, [
                'json' => $payload,
            ]);

            $body = Json::decodeIfJson((string)$request->getBody());

            return $body;
        } catch (BadResponseException $e) {
            dd($e->getMessage(), $e->getResponse());

            return null;
        }
    }

    public function getToken($cache = true, $deleteCache = false)
    {
        $duration     = (new \DateInterval('P7D'))->format('s');
        $cacheService = Craft::$app->getCache();
        $getToken     = [$this, '_getTokenRequest'];

        if ($deleteCache) {
            $cacheService->delete('yotpoToken');
        }

        if ($cache) {
            $token = $cacheService->getOrSet('yotpoToken', $getToken, $duration);

            return $token;
        }

        return $getToken();
    }

    /**
     * @param string $template
     * @param array  $data
     *
     */
    public function renderTemplate($template = '', array $data = []): void
    {
        try {
            $view    = Craft::$app->getView();
            $oldMode = $view->getTemplateMode();

            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

            $view->renderTemplate($template, $data);

            $view->setTemplateMode($oldMode);
        } catch (\Twig_Error_Loader $e) {
            // @todo Log error?
        } catch (\Exception $e) {
            // @todo Log error?
        }
    }

    private function _getTokenRequest()
    {
        try {
            $client  = new Client($this->getClientConfig());
            $request = $client->post('/oauth/token', [
                'json' => [
                    'client_id'     => $this->getAppKey(),
                    'client_secret' => $this->getAppSecret(),
                    'grant_type'    => 'client_credentials',
                ],
            ]);

            $body = Json::decodeIfJson((string)$request->getBody());

            return $body['access_token'] ?? false;

        } catch (BadResponseException $e) {
            Craft::error(
                Craft::t('yotpo', "Error when getting token: {error}.\n\nRequest: {requestBody}\n\nResponse: {responseBody}", [
                    'error'        => $e->getMessage(),
                    'responseBody' => (string)$e->getResponse()->getBody(),
                    'requestBody'  => (string)$e->getRequest()->getBody(),
                ]),
                'yotpo'
            );

            return false;
        }
    }

    public function getClientConfig()
    {
        return [
            'base_uri' => static::API_URL,
        ];
    }

    public function getAppKey(): string
    {
        return $this->getSettings()->appKey;
    }

    public function getAppSecret(): string
    {
        return $this->getSettings()->appSecret;
    }

    public function getSettings(): \superbig\yotpo\models\Settings
    {
        if (!$this->_settings) {
            $this->_settings = Yotpo::$plugin->getSettings();
        }

        return $this->_settings;
    }

    private function _replaceUrlPlaceholders($url)
    {
        $placeholders = [
            ':appKey:' => $this->getAppKey(),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $url);
    }
}
