<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\variables;

use craft\commerce\elements\Variant;
use superbig\yotpo\models\BottomLineModel;
use superbig\yotpo\Yotpo;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 */
class YotpoVariable extends Model
{
    // Public Methods
    // =========================================================================

    public function getBottomLine(Variant $variant): ?BottomLineModel
    {
        return Yotpo::$plugin->productService->getBottomLineForVariant($variant);
    }
}
