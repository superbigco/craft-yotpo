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
 *
 * @property float $totalReviews
 * @property float $averageScore
 */
class BottomLineModel extends Model
{
    // Public Properties
    // =========================================================================

    public $totalReviews = 0;
    public $averageScore = 0;

    // Public Methods
    // =========================================================================

    public function hasReviews(): bool
    {
        return $this->totalReviews > 0;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['totalReviews', 'averageScore'], 'number'],
            [['totalReviews', 'averageScore'], 'required'],
        ];
    }
}
