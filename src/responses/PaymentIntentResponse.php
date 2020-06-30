<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\responses;

use craft\commerce\base\RequestResponseInterface;
use craft\commerce\errors\NotImplementedException;

class PaymentIntentResponse implements RequestResponseInterface
{
    /**
     * @var array the response data
     */
    protected $data = [];

    /**
     * Response constructor.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function isSuccessful(): bool
    {
        return array_key_exists('status', $this->data) && in_array($this->data['status'], ['succeeded', 'requires_capture'], true);
    }

    /**
     * @inheritdoc
     */
    public function isProcessing(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isRedirect(): bool
    {

        return array_key_exists('next_action', $this->data) && is_array($this->data['next_action']) && array_key_exists('url', $this->data['next_action']['redirect']);
    }

    /**
     * @inheritdoc
     */
    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    /**
     * @inheritdoc
     */
    public function getRedirectData(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getRedirectUrl(): string
    {
        return $this->data['next_action']['redirect']['url'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getTransactionReference(): string
    {
        if (empty($this->data)) {
            return '';
        }

        return (string)$this->data['id'];
    }

    /**
     * @inheritdoc
     */
    public function getCode(): string
    {
        if (empty($this->data['code'])) {
            if (!empty($this->data['last_payment_error'])) {
                return $this->data['last_payment_error']['failed_code'];
            }

            return '';
        }

        return $this->data['code'];
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        if (empty($this->data['message'])) {
            if (!empty($this->data['last_payment_error'])) {
                if ($this->data['last_payment_error']['failed_code'] === 'payment_intent_authentication_failure') {
                    return 'The provided payment method has failed authentication.';
                }

                return $this->data['last_payment_error']['failed_message'];
            }

            return '';
        }

        return $this->data['message'];
    }

    /**
     * @inheritdoc
     */
    public function redirect()
    {
        throw new NotImplementedException('Redirecting directly is not implemented for this gateway.');
    }

    /**
     * Set processing status.
     *
     * @param bool $status
     */
    public function setProcessing(bool $status)
    {
        $this->_processing = $status;
        
    }


}
