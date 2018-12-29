<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\assetbundles\Yotpo;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 */
class YotpoAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@superbig/yotpo/assetbundles/yotpo/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Yotpo.js',
        ];

        $this->css = [
            'css/Yotpo.css',
        ];

        parent::init();
    }
}
