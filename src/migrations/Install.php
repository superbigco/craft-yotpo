<?php
/**
 * Yotpo plugin for Craft CMS 3.x
 *
 * Integrate Craft Commerce with Yotpo.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\yotpo\migrations;

use superbig\yotpo\records\BottomLineRecord;
use superbig\yotpo\records\ProductRecord;
use superbig\yotpo\records\ReviewRecord;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    Superbig
 * @package   Yotpo
 * @since     1.0.0
 */
class Install extends Migration
{
    const CASCADE  = 'CASCADE';
    const SET_NULL = 'SET_NULL';

    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            //$this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated  = false;
        $defaultColumns = [
            'id'          => $this->primaryKey(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'         => $this->uid(),
            //'siteId'      => $this->integer()->notNull(),
        ];
        $tables         = [
            ProductRecord::TABLE_NAME => [
                'defaultColumns'   => true,
                'productId'        => $this->integer()->notNull(),
                'variantId'        => $this->integer()->notNull(),
                'yotpoId'          => $this->integer()->notNull(),
                'totalReviews'     => $this->integer()->null()->defaultValue(0),
                'averageScore'     => $this->float(2)->null()->defaultValue(0),
                'starDistribution' => $this->text()->defaultValue(null),
                'name'             => $this->string(),
                'url'              => $this->string(),
            ],
            ReviewRecord::TABLE_NAME  => [
                'defaultColumns' => true,
                'productId'      => $this->integer()->notNull(),
                'variantId'      => $this->integer()->notNull(),
                'yotpoProductId' => $this->integer()->notNull(),
                'score'          => $this->integer()->null()->defaultValue(0),
                'votesUp'        => $this->integer()->null()->defaultValue(0),
                'votesDown'      => $this->integer()->null()->defaultValue(0),
                'verifiedBuyer'  => $this->boolean()->defaultValue(true),
                'sentiment'      => $this->float(6)->null()->defaultValue(1),
                'title'          => $this->string(),
                'content'        => $this->string(),
                'user'           => $this->text()->defaultValue(null),
            ],
        ];

        foreach ($tables as $tableName => $settings) {
            $tableSchema = Craft::$app->db->schema->getTableSchema($tableName);
            if ($tableSchema === null) {
                $tablesCreated = true;

                if (isset($settings['defaultColumns'])) {
                    unset($settings['defaultColumns']);

                    $settings = array_merge($defaultColumns, $settings);
                }

                $this->createTable($tableName, $settings);
            }
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%yotpo_yotporecord}}',
                'some_field',
                true
            ),
            '{{%yotpo_yotporecord}}',
            'some_field',
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $foreignKeys = [
            ProductRecord::TABLE_NAME => [
                [
                    'column'       => 'productId',
                    'targetTable'  => 'elements',
                    'targetColumn' => 'id',
                    'onDelete'     => self::CASCADE,
                    'onUpdate'     => self::CASCADE,

                ],
                [
                    'column'       => 'variantId',
                    'targetTable'  => 'elements',
                    'targetColumn' => 'id',
                    'onDelete'     => self::CASCADE,
                    'onUpdate'     => self::CASCADE,

                ],
            ],
            ReviewRecord::TABLE_NAME  => [
                [
                    'column'       => 'productId',
                    'targetTable'  => 'elements',
                    'targetColumn' => 'id',
                    'onDelete'     => self::CASCADE,
                    'onUpdate'     => self::CASCADE,

                ],
                [
                    'column'       => 'variantId',
                    'targetTable'  => 'elements',
                    'targetColumn' => 'id',
                    'onDelete'     => self::CASCADE,
                    'onUpdate'     => self::CASCADE,

                ],
            ],
        ];

        foreach ($foreignKeys as $tableName => $keys) {
            foreach ($keys as $keySettings) {
                $column       = $keySettings['column'];
                $targetTable  = $keySettings['targetTable'];
                $targetColumn = $keySettings['targetColumn'];
                $onDelete     = $keySettings['onDelete'] ?? self::CASCADE;
                $onUpdate     = $keySettings['onUpdate'] ?? self::CASCADE;

                $this->addForeignKey(
                    $this->db->getForeignKeyName($tableName, $column),
                    $tableName,
                    $column,
                    "{{%$targetTable}}",
                    $targetColumn,
                    $onDelete,
                    $onUpdate
                );
            }
        }
    }

    /**
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists(ProductRecord::TABLE_NAME);
        $this->dropTableIfExists(ReviewRecord::TABLE_NAME);
        $this->dropTableIfExists(BottomLineRecord::TABLE_NAME);
    }
}
