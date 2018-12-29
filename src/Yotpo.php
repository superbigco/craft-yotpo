<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo;

use craft\commerce\elements\Order;
use craft\web\twig\variables\CraftVariable;
use superbig\yotpo\services\ApiService;
use superbig\yotpo\services\BottomLineService;
use superbig\yotpo\services\OrderService;
use superbig\yotpo\services\ProductService;
use superbig\yotpo\services\ReviewService;
use superbig\yotpo\services\YotpoService;
use superbig\yotpo\models\Settings;
use superbig\yotpo\utilities\YotpoUtility as YotpoUtilityUtility;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\services\Utilities;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use superbig\yotpo\variables\YotpoVariable;
use yii\base\Event;

/**
 * Class Yotpo
 *
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 *
 * @property  YotpoService      $yotpoService
 * @property  ProductService    $productService
 * @property  ApiService        $apiService
 * @property  OrderService      $orderService
 * @property  ReviewService     $reviewService
 *
 * @method  Settings getSettings()
 */
class Yotpo extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Yotpo
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'superbig\yotpo\console\controllers';
        }

        $this->setComponents([
            'yotpoService'   => YotpoService::class,
            'productService' => ProductService::class,
            'apiService'     => ApiService::class,
            'orderService'   => OrderService::class,
            'reviewService'  => ReviewService::class,
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'yotpo/default';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'yotpo/default/do-something';
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = YotpoUtilityUtility::class;
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('yotpo', YotpoVariable::class);
            }
        );

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            Event::on(Order::class, Order::EVENT_AFTER_ORDER_PAID, function(Event $e) {
                // @var Order $order
                $order = $e->sender;

                self::$plugin->orderService->onOrderComplete($order);
            });
        }

        Craft::info(
            Craft::t(
                'yotpo',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'yotpo/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
