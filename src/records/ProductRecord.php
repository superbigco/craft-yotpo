<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\records;

use superbig\yotpo\Yotpo;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 *
 * @property int   $id
 * @property int   $yotpoId
 * @property int   $totalReviews
 * @property float $averageScore
 */
class ProductRecord extends ActiveRecord
{
    const TABLE_NAME = '{{%yotpo_products}}';

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return static::TABLE_NAME;
    }
}
