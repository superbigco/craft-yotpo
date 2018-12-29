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

use superbig\yotpo\jobs\CreateOrder;
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
class OrderService extends Component
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
        $job = new CreateOrder([
            'orderId' => $order->id,
        ]);

        Craft::$app->getQueue()->push($job);
    }

    public function createOrder(Order $order)
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
        Yotpo::$plugin->apiService->renderTemplate('_yotpo/product', [
            'product' => $productModel,
        ]);
    }

    public function renderOrderTemplate(OrderModel $orderModel): void
    {
        Yotpo::$plugin->apiService->renderTemplate('_yotpo/order', [
            'order' => $orderModel,
        ]);
    }

    // Private Methods
    // =========================================================================

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
