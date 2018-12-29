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
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin;
use modules\yotpomodule\YotpoModule;

use Craft;
use craft\base\Model;
use superbig\yotpo\Yotpo;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 *
 * @property Order   $_order
 * @property Variant $_variant
 */
class ProductModel extends Model
{
    // Public Properties
    // =========================================================================

    public $sku;
    public $color;
    public $size;
    public $vendor;
    public $material;
    public $model;
    public $coupon_used;
    public $product_tags;
    public $custom_properties;
    public $name;
    public $value;
    public $url;
    public $image;
    public $description;
    public $price;
    public $specs;

    // Private Properties
    // =========================================================================

    private $_order;
    private $_variant;

    // Public Static Methods
    // =========================================================================

    public static function createFromVariant(Variant $variant): ProductModel
    {
        $model = new self();
        $model
            ->setVariant($variant);

        return $model;
    }

    public static function createFromLineItem(LineItem $lineItem, Order $order): ProductModel
    {
        /** @var Variant $variant */
        $variant = $lineItem->getPurchasable();
        $model   = new self();
        $model
            ->setVariant($variant)
            ->setOrder($order)
            ->setCouponUsed($order->couponCode ?? null);

        return $model;
    }

    // Public Methods
    // =========================================================================

    public function getId()
    {
        $useVariantSkuAsProductId = Yotpo::$plugin->getSettings()->useVariantSkuAsProductId;

        if ($useVariantSkuAsProductId) {
            return $this->sku ?? $this->getVariant()->getSku() ?? $this->getVariant()->id;
        }

        return $this->sku ?? $this->getVariant()->id;
    }

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

    public function setVariant(Variant $variant)
    {
        $this->_variant = $variant;

        return $this;
    }

    public function getVariant(): Variant
    {
        return $this->_variant;
    }

    public function getImageUrl(): string
    {
        return $this->image;
    }

    public function setImageUrl(string $url)
    {
        $this->image = $url;

        return $this;
    }

    public function setTag(string $tag)
    {
        $this->product_tags = $tag;

        return $this;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function setColor(string $value)
    {
        $this->color = $value;

        return $this;
    }

    public function setSize(string $value)
    {
        $this->size = $value;

        return $this;
    }

    public function setVendor(string $value)
    {
        $this->vendor = $value;

        return $this;
    }

    public function setMaterial(string $value)
    {
        $this->material = $value;

        return $this;
    }

    public function setModel(string $value)
    {
        $this->model = $value;

        return $this;
    }

    public function setCouponUsed($value)
    {
        $this->coupon_used = $value;

        return $this;
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function getPayload()
    {
        $variant = $this->getVariant();
        $product = $variant->getProduct();
        $payload = [
            'color'        => $this->color,
            'size'         => $this->size,
            'vendor'       => $this->vendor,
            'material'     => $this->material,
            'model'        => $this->model,
            'coupon_used'  => $this->coupon_used,
            'product_tags' => $this->product_tags,
            // @todo Support 'custom_properties' => [],
            'name'         => $variant->title ?? $product->title,
            'url'          => $variant->getUrl(),
            'image_url'    => $this->getImageUrl(),
            'description'  => $variant->getDescription(),
            'price'        => $variant->getPrice(),
            // @todo Support 'specs'        => null,
        ];

        // @todo Yotpo will ignore local test domain but won't return error response - super annoying
        // @todo Add note in readme about this
        $payload['url'] = str_replace('https://craft3.dev/', 'https://superbig.co/', $payload['url']);

        return collect($payload)
            ->filter()
            ->toArray();
    }

    public function getCreatePayload()
    {
        $payload                = $this->getPayload();
        $payload['image_url']   = $payload['image'];
        $payload['blacklisted'] = false;
        $payload['currency']    = Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrency()->iso;

        // @todo Yotpo will ignore local test domain but won't return error response - super annoying
        // @todo Add note in readme about this
        $payload['url'] = str_replace('https://craft3.dev', 'https://superbig.co/', $payload['url']);

        return collect($payload)
            ->only(['name', 'url', 'image_url', 'description', 'currency', 'price', 'product_tags', 'blacklisted'])
            ->filter()
            ->toArray();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'url', 'image_url', 'product_tags'], 'string'],
            [['name', 'url'], 'required'],
        ];
    }
}
