<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\console\controllers;

use craft\base\Element;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use superbig\yotpo\models\ProductModel;
use superbig\yotpo\models\YotpoProductModel;
use superbig\yotpo\Yotpo;

use Craft;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Default Command
 *
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Transfer all products to Yotpo
     *
     * @return mixed
     */
    public function actionSyncProducts()
    {
        $products = [];
        $variants = Variant::find()->status(Element::STATUS_ENABLED)->all();
        $total    = Variant::find()->status(Element::STATUS_ENABLED)->count();
        $current  = 0;
        $perPage  = 100;
        $pages    = round(floor($total / $perPage));

        Console::startProgress($current, $total);

        foreach ($variants as $variant) {
            $current++;
            Console::updateProgress($current, $total);

            $product = ProductModel::createFromVariant($variant);

            Yotpo::$plugin->productService->renderProductTemplate($product);

            $products[ $product->getId() ] = $product;
        }

        Console::endProgress();

        $result = Yotpo::$plugin->productService->syncProductsBatch($products);

        if (!$result) {
            $this->stdout("Failed to send result\n");
        }

        var_dump($result);

        return ExitCode::OK;
    }

    public function actionSyncOrder()
    {
        $order = Order::find()->id([3754])->one();

        Yotpo::$plugin->orderService->onOrderComplete($order);
    }

    /**
     * Handle yotpo-module/default/do-something console commands
     *
     * @return mixed
     */
    public function actionGetProducts()
    {
        $result   = Yotpo::$plugin->productService->getRemoteProducts();
        $products = collect($result)
            ->map(function($remoteProduct) {
                return YotpoProductModel::createFromApiResponse($remoteProduct);
            })
            ->filter()
            ->each([Yotpo::$plugin->productService, 'saveProduct']);

        $this->write("Saved products");
        //dd($products);
    }

    /**
     * Handle yotpo-module/default/do-something console commands
     *
     * @return mixed
     */
    public function actionGetReviews()
    {
        $result = Yotpo::$plugin->reviewService->getRemoteReviews();

        $reviews = $result['reviews'] ?? null;

        dd($reviews);
    }

    /**
     * Handle yotpo-module/default/do-something console commands
     *
     * @return mixed
     */
    public function actionGetOrders()
    {
        $result = Yotpo::$plugin->productService->getOrders();

        dd($result);
    }

    public function write($text = '', $indent = false)
    {
        $this->stdout("$text\n");
    }
}
