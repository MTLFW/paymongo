<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\models;

use Craft;
use craft\commerce\base\GatewayInterface;
use craft\commerce\base\Model;
use craft\commerce\Plugin as Commerce;
use craft\commerce\paymongo\records\Customer as CustomerRecord;
use craft\elements\User;

/**
 * PayMongo customer model
 *
 * @property GatewayInterface $gateway
 * @property User $user
 *
 * @since 2.0
 */
class Customer extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int Customer ID
     */
    public $id;

    /**
     * @var int The user ID
     */
    public $userId;

    /**
     * @var int The gateway ID.
     */
    public $gatewayId;

    /**
     * @var string Reference
     */
    public $reference;

    /**
     * @var string Response data
     */
    public $response;

    /**
     * @var User|null $_user
     */
    private $_user;

    /**
     * @var GatewayInterface|null $_user
     */
    private $_gateway;

    // Public Methods
    // =========================================================================

    /**
     * Returns the customer identifier
     *
     * @return string
     */
    public function __toString()
    {
        return $this->reference;
    }

    /**
     * Returns the user element associated with this customer.
     *
     * @return User|null
     */
    public function getUser()
    {
        if (null === $this->_user) {
            $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
        }

        return $this->_user;
    }

    /**
     * Returns the gateway associated with this customer.
     *
     * @return GatewayInterface|null
     */
    public function getGateway()
    {
        if (null === $this->_gateway) {
            $this->_gateway = Commerce::getInstance()->getGateways()->getGatewayById($this->gatewayId);
        }

        return $this->_gateway;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {

        return [
            [['reference'], 'unique', 'targetAttribute' => ['gatewayId', 'reference'], 'targetClass' => CustomerRecord::class],
            [['gatewayId', 'userId', 'reference'], 'required']
        ];
    }
}
