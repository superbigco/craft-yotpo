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
 */
class ReviewRecord extends ActiveRecord
{
    const TABLE_NAME = '{{%yotpo_reviews}}';

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
