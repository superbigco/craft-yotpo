<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\models;

use craft\commerce\elements\Order;
use modules\yotpomodule\YotpoModule;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 *
 * @property Order $_order
 */
class OrderModel extends Model
{
    // Public Properties
    // =========================================================================

    public $validateData = true;
    public $appKey;
    public $token;
    public $products     = [];
    public $customerName;

    // Private Properties
    // =========================================================================

    private $_order;

    // Public Static Methods
    // =========================================================================

    public static function createFromOrder(Order $order): OrderModel
    {
        $model = new self();
        $model
            ->setOrder($order);

        return $model;
    }


    // Public Methods
    // =========================================================================

    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function setOrder(Order $order)
    {
        $this->_order = $order;

        return $this;
    }

    public function getOrder(): Order
    {
        return $this->_order;
    }

    public function setAppKey(string $key)
    {
        $this->appKey = $key;

        return $this;
    }

    public function setToken(string $value)
    {
        $this->token = $value;

        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setProducts(array $value)
    {
        $this->products = $value;

        return $this;
    }

    public function addProduct(array $value)
    {
        $this->products[] = $value;

        return $this;
    }

    public function getId()
    {
        return $this->getOrder()->getId();
    }

    public function getPayload()
    {
        $order          = $this->getOrder();
        $billingAddress = $order->getBillingAddress();
        $shippingMethod = $order->getShippingMethod();
        $orderDate      = $order->dateOrdered ?? $order->dateUpdated;
        $payload        = [
            'validate_data' => $this->validateData,
            'platform'      => 'general',
            'currency_iso'  => $order->paymentCurrency,
            'order_id'      => $order->getId(),
            'customer_name' => $billingAddress->getFullName(),
            'email'         => $order->getEmail(),
            'utoken'        => $this->getToken(),
            'app_key'       => $this->getAppKey(),
            'order_date'    => $orderDate->format('Y-m-d'),
            'customer'      => [
                'state'   => $billingAddress->getStateText(),
                'country' => $billingAddress->getCountry()->iso,
                'address' => $billingAddress->address1,
                //'phone_number' => $billingAddress->phone,
            ],
            'delivery_type' => $order->shippingMethodHandle ?? null,
            'products'      => $this->products,
        ];

        return collect($payload)
            ->filter()
            ->toArray();
    }


    public function setCouponUsed($value)
    {
        $this->coupon_used = $value;

        return $this;
    }

    public function getAppKey()
    {
        return $this->appKey;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'url', 'image_url', 'product_tags'], 'string'],
            [['name', 'url'], 'required'],
            ['default', 'value' => 'Some Default'],
        ];
    }
}
