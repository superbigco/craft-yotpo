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

use craft\commerce\elements\Variant;
use superbig\yotpo\Yotpo;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 *
 * @property int    $id
 * @property float  $totalReviews
 * @property float  $averageScore
 * @property int    $productId
 * @property int    $variantId
 * @property int    $yotpoId
 * @property string $name
 * @property string $url
 */
class YotpoReviewModel extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $productId;
    public $variantId;
    public $yotpoId;
    public $totalReviews;
    public $averageScore;
    public $name;
    public $url;

    // Public Static Methods
    // =========================================================================

    public static function createFromApiResponse($remoteProduct = []): ?YotpoReviewModel
    {
        $map       = [
            'id'            => 'yotpoId',
            'average_score' => 'averageScore',
            'total_reviews' => 'totalReviews',
        ];
        $whitelist = array_merge(array_values($map), [
            'name',
            'url',
        ]);
        $data      = collect($remoteProduct)
            ->mapWithKeys(function($value, $key) use ($map) {
                $newKey = $map[ $key ] ?? $key;

                return [$newKey => $value];
            })
            ->only($whitelist)
            ->toArray();

        $variantSku = $remoteProduct['external_product_id'];
        $variant    = Yotpo::$plugin->productService->getVariantByExternalProductId($variantSku);

        if (!$variant) {
            return null;
        }

        $model = Yotpo::$plugin->productService->getProductByVariantId($variant->id) ?? new self();

        $model
            ->setVariant($variant)
            ->setAttributes($data);

        return $model;
    }

    // Public Methods
    // =========================================================================

    public function setVariant(Variant $variant)
    {
        $product         = $variant->getProduct();
        $this->variantId = $variant->id;
        $this->productId = $product->id;

        return $this;
    }

    public function getBottomLine(): BottomLineModel
    {
        return new BottomLineModel([
            'totalReviews' => $this->totalReviews,
            'averageScore' => $this->averageScore,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['yotpoId', 'productId', 'variantId', 'totalReviews', 'averageScore'], 'number'],
            [['yotpoId', 'productId', 'variantId', 'totalReviews', 'averageScore', 'name', 'url'], 'required'],
        ];
    }
}
