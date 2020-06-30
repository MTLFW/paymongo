<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\migrations;

use Craft;
use craft\commerce\paymongo\gateways\Gateway;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

/**
 * Installation Migration
 * @since 1.0
 */
class Install extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Convert any built-in PayMongo gateways to ours
        $this->_convertGateways();

        $this->createTable('{{%paymongo_customers}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'gatewayId' => $this->integer()->notNull(),
            'reference' => $this->string()->notNull(),
            'response' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%paymongo_invoices}}', [
            'id' => $this->primaryKey(),
            'reference' => $this->string(),
            'subscriptionId' => $this->integer()->notNull(),
            'invoiceData' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%paymongo_paymentintents}}', [
            'id' => $this->primaryKey(),
            'reference' => $this->string(),
            'gatewayId' => $this->integer()->notNull(),
            'customerId' => $this->integer()->notNull(),
            'orderId' => $this->integer()->notNull(),
            'intentData' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, '{{%paymongo_customers}}', 'gatewayId', '{{%commerce_gateways}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%paymongo_customers}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%paymongo_invoices}}', 'subscriptionId', '{{%commerce_subscriptions}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%paymongo_paymentintents}}', 'gatewayId', '{{%commerce_gateways}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%paymongo_paymentintents}}', 'customerId', '{{%paymongo_customers}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%paymongo_paymentintents}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', null);

        $this->createIndex(null, '{{%paymongo_customers}}', 'gatewayId', false);
        $this->createIndex(null, '{{%paymongo_customers}}', 'userId', false);
        $this->createIndex(null, '{{%paymongo_invoices}}', 'subscriptionId', false);
        $this->createIndex(null, '{{%paymongo_invoices}}', 'reference', true);
        $this->createIndex(null, '{{%paymongo_paymentintents}}', 'reference', true);
        $this->createIndex(null, '{{%paymongo_paymentintents}}', ['orderId', 'gatewayId', 'customerId'], true);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

        MigrationHelper::dropAllForeignKeysOnTable('{{%paymongo_invoices}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%paymongo_customers}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%paymongo_paymentintents}}', $this);
        $this->dropTable('{{%paymongo_customers}}');
        $this->dropTable('{{%paymongo_invoices}}');
        $this->dropTable('{{%paymongo_paymentintents}}');

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Converts any old school PayMongo gateways to this one
     */
    private function _convertGateways()
    {
        $gateways = (new Query())
            ->select(['id', 'settings'])
            ->where(['type' => 'craft\\commerce\\gateways\\paymongo'])
            ->from(['{{%commerce_gateways}}'])
            ->all();

        $dbConnection = Craft::$app->getDb();

        foreach ($gateways as $gateway) {

            $settings = Json::decodeIfJson($gateway['settings']);

            if ($settings && isset($settings['includeReceiptEmailInRequests'])) {
                $settings['sendReceiptEmail'] = $settings['includeReceiptEmailInRequests'];
                unset($settings['includeReceiptEmailInRequests']);
            } else {
                $settings = [];
            }

            $settings = Json::encode($settings);

            $values = [
                'type' => Gateway::class,
                'settings' => $settings
            ];

            $dbConnection->createCommand()
                ->update('{{%commerce_gateways}}', $values, ['id' => $gateway['id']])
                ->execute();
        }
    }
}
