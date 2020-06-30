<?php
/**
 * @link https://craftcms.com/
 */

namespace craft\commerce\paymongo\migrations;

use craft\db\Migration;

/**
 * m190502_153000_payment_intents migration.
 */
class m190502_153000_payment_intents extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
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

        $this->addForeignKey(null, '{{%paymongo_paymentintents}}', 'gatewayId', '{{%commerce_gateways}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%paymongo_paymentintents}}', 'customerId', '{{%paymongo_customers}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%paymongo_paymentintents}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', null);

        $this->createIndex(null, '{{%paymongo_paymentintents}}', 'reference', true);
        $this->createIndex(null, '{{%paymongo_paymentintents}}', ['orderId', 'gatewayId', 'customerId'], true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190502_153000_payment_intents cannot be reverted.\n";
        return false;
    }
}
