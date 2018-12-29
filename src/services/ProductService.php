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

use craft\db\Query;
use superbig\yotpo\models\BottomLineModel;
use superbig\yotpo\models\OrderModel;
use superbig\yotpo\models\ProductModel;
use superbig\yotpo\models\YotpoProductModel;
use superbig\yotpo\records\ProductRecord;
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
class ProductService extends Component
{
    // Private Properties
    // =========================================================================

    private $_settings;

    // Public Methods
    // =========================================================================

    public function getProductByVariantId($variantId = null)
    {
        $query = (new Query())
            ->select(['id', 'name', 'url', 'productId', 'variantId', 'totalReviews', 'averageScore', 'yotpoId'])
            ->from(ProductRecord::TABLE_NAME)
            ->where('variantId = :id', [':id' => $variantId])
            ->one();

        if (!$query) {
            return null;
        }

        return new YotpoProductModel($query);
    }

    public function getProducts()
    {

    }

    public function getBottomLineForVariant(Variant $variant)
    {
        $product = $this->getProductByVariantId($variant->id);

        if (!$product) {
            return null;
        }

        return $product->getBottomLine();
    }

    public function syncProducts($remoteProducts = []): void
    {
        collect($remoteProducts)
            ->each(function($remoteProduct) {

            });
    }

    public function getRemoteProducts($getAll = true)
    {
        $getPage = function($page, $perPage = 100) {
            $result = Yotpo::$plugin->apiService->get('/v1/apps/:appKey:/products', [
                'count' => $perPage,
                'page'  => $page,
            ]);

            return $result;
        };

        $page     = $page ?? 1;
        $perPage  = 100;
        $results  = $getPage($page);
        $total    = $results['pagination']['total'] ?? 1;
        $products = [$results['products']];
        $pages    = ceil($total / $perPage);

        if ($getAll && $pages > 1) {
            foreach (range(2, $pages - 1) as $page) {
                $results    = $getPage($page);
                $products[] = $results['products'];
            }
        }

        return array_merge(...$products);
    }

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

    public function getVariantByExternalProductId($id = null): ?Variant
    {
        $variant = Variant::find()->sku($id)->one();

        // If it wasn't found by SKU, find it with ID
        if (!$variant) {
            $variant = Variant::find()->id($id)->one();
        }

        return $variant;
    }

    public function saveProduct(YotpoProductModel $product)
    {
        try {
            if ($product->id) {
                $record = ProductRecord::findOne($product->id);
            }
            else {
                $record = new ProductRecord();
            }

            $record->setAttributes($product->getAttributes(), false);

            //$record->siteId       = Craft::$app->getSites()->getPrimarySite()->id;

            if (!$record->save()) {
                Craft::error(
                    Craft::t('yotpo', 'An error occured when saving Yotpo product: {error}',
                        [
                            'error' => print_r($record->getErrors(), true),
                        ]),
                    'yotpo');

                return false;
            }

        } catch (\Exception $e) {
            Craft::error(
                Craft::t('yotpo', 'An error occured when saving Yotpo record: {error}',
                    [
                        'error' => $e->getMessage(),
                    ]),
                'yotpo');

            return false;
        }

        $product->id = $record->id;

        return true;
    }

    // Private Methods
    // =========================================================================


    public function getSettings(): \superbig\yotpo\models\Settings
    {
        if (!$this->_settings) {
            $this->_settings = Yotpo::$plugin->getSettings();
        }

        return $this->_settings;
    }
}
