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
class YotpoService extends Component
{
    public const API_URL = 'https://api.yotpo.com';

    // Private Properties
    // =========================================================================

    private $_settings;

    // Public Methods
    // =========================================================================

    /**
     * @param ProductModel[] $productModels
     *
     * @return mixed|null
     */
    public function syncProductsBatch(array $productModels = [], $update = false)
    {
        $products = collect($productModels)
            ->mapWithKeys(function(ProductModel $product) {
                return [$product->getId() => $product->getCreatePayload()];
            })
            ->toArray();

        $payload = [
            'validate_data' => false,
            'utoken'        => $this->_getToken(),
            'products'      => $products,
        ];

        if ($update) {
            return $this->put("/apps/{$this->getAppKey()}/products/mass_update", $payload);
        }

        return $this->post("/apps/{$this->getAppKey()}/products/mass_create", $payload);
    }

    public function getProducts($page = null)
    {
        $page    = $page ?? 1;
        $perPage = 100;
        $result  = $this->get("/v1/apps/{$this->getAppKey()}/products", [
            'utoken' => $this->_getToken(),
            'count'  => $perPage,
            'page'   => $page,
        ]);

        return $result;
    }

    public function getOrders($page = null)
    {
        $page    = $page ?? 1;
        $perPage = 100;
        $result  = $this->get("/apps/{$this->getAppKey()}/purchases", [
            'utoken' => $this->_getToken(),
            'count'  => $perPage,
            'page'   => $page,
        ]);

        return $result;
    }

    public function onOrderComplete(Order $order)
    {
        $orderModel = OrderModel::createFromOrder($order);
        $orderModel
            ->setAppKey($this->getAppKey())
            ->setToken($this->_getToken())
            ->setProducts($this->getProductsFromOrder($order));

        $request = $this->post("/apps/{$this->getAppKey()}/purchases/", $orderModel->getPayload());

        // @todo save request and response
    }

    public function getProductsFromOrder(Order $order): array
    {
        $products = [];

        // @todo Add transformer config
        // @todo Add tags
        // @todo Configure fields to map
        foreach ($order->getLineItems() as $lineItem) {
            $productModel = ProductModel::createFromLineItem($lineItem, $order);

            // Allow products to be modified in Twig
            $this->renderProductTemplate($productModel);

            $products[ $productModel->getId() ] = $productModel->getPayload();
        }

        return $products;
    }

    public function renderProductTemplate(ProductModel $productModel): void
    {
        $this->_renderTemplate('_yotpo/product', [
            'product' => $productModel,
        ]);
    }

    public function renderOrderTemplate(OrderModel $orderModel): void
    {
        $this->_renderTemplate('_yotpo/order', [
            'order' => $orderModel,
        ]);
    }

    /**
     * @param string $url
     * @param array  $query
     *
     * @return mixed|null
     */
    public function get($url = '', $query = [])
    {
        try {
            $client  = new Client($this->getClientConfig());
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

    // Private Methods
    // =========================================================================

    /**
     * @param string $template
     * @param array  $data
     *
     */
    private function _renderTemplate($template = '', array $data = []): void
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

    private function _getToken($cache = true, $deleteCache = false)
    {
        $appKey       = $this->getAppKey();
        $secret       = $this->getAppSecret();
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

    public function _getTokenRequest()
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

    public function getAppKey()
    {
        return $this->getSettings()->appKey;
    }

    public function getAppSecret()
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
}
