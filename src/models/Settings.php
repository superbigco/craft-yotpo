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

use superbig\yotpo\Yotpo;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $appKey                   = '';
    public $appSecret                = '';
    public $useVariantSkuAsProductId = true;

    // Public Methods
    // =========================================================================

    public function isValidConnection(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appKey', 'appSecret'], 'string'],
            [['appKey', 'appSecret'], 'required'],
        ];
    }
}
