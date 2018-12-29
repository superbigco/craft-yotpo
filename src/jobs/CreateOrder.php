<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\jobs;

use craft\commerce\elements\Order;
use superbig\yotpo\Yotpo;

use Craft;
use craft\queue\BaseJob;
use yii\base\Exception;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 *
 * @property int $orderId
 */
class CreateOrder extends BaseJob
{
    // Public Properties
    // =========================================================================

    public $orderId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $order = Order::find()->id($this->orderId)->one();

        if (!$order) {
            throw new Exception("Order {$this->orderId} not found");
        }

        Yotpo::$plugin->orderService->createOrder($order);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('yotpo', "Create order {$this->orderId} on Yotpo");
    }
}
