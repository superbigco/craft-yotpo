<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\utilities;

use superbig\yotpo\Yotpo;
use superbig\yotpo\assetbundles\yotpoutilityutility\YotpoUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * Yotpo Utility
 *
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 */
class YotpoUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('yotpo', 'YotpoUtility');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'yotpo-yotpo-utility';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@superbig/yotpo/assetbundles/yotpoutilityutility/dist/img/YotpoUtility-icon.svg");
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(YotpoUtilityUtilityAsset::class);

        $someVar = 'Have a nice day!';
        return Craft::$app->getView()->renderTemplate(
            'yotpo/_components/utilities/YotpoUtility_content',
            [
                'someVar' => $someVar
            ]
        );
    }
}
