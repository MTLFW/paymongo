<?php

namespace craft\commerce\paymongo\traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use craft\commerce\paymongo\Exceptions\BadRequestException;
use craft\commerce\paymongo\Exceptions\NotFoundException;
use craft\commerce\paymongo\Exceptions\PaymentErrorException;
use craft\commerce\paymongo\extensions\PaymentIntent;
use craft\commerce\paymongo\extensions\Webhook;
use craft\commerce\paymongo\PaymentIntentResponse;

trait Request
{
    protected $data;
    protected $options;

    /**
     * Request a create to API.
     *
     * @param array $payload
     * @return Model
     */
    public function create($payload,$apikey)
    { 
        $this->method = 'POST';
        $this->payload = $this->convertPayloadAmountsToInteger($payload);
        $this->formRequestData();
      
        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
            'auth' => [$apikey, ''],
            'json' => $this->data,
        ]);
     
        return $this->request();
    }

    /**
     * Request a create to API.
     *
     * @param array $payload
     * @return Model
     */
    public function find($clientKey,$paymentIntentReference,$apikey)
    { 
        $this->method = 'GET';
        $this->payload = '';
        $this->apiUrl = $this->apiUrl.$paymentIntentReference.'?'. $clientKey;

        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
            'auth' =>  [$apikey, ''],
        ]);

        return $this->request();
    }

    /**
     * Request a get all to API.
     *
     * @return Model
     */
    public function all()
    {
        $this->method = 'GET';

        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
            'auth' => [config('paymongo.secret_key'), ''],
        ]);

        return $this->request();
    }

    /**
     * Enables the webhook.
     *
     * @param Webhook $webhook
     * @return Model
     */
    public function enable(Webhook $webhook)
    {
        $this->method = 'POST';
        $this->apiUrl = $this->apiUrl.$webhook->getId().'/enable';

        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth' => [config('paymongo.secret_key'), ''],
        ]);

        return $this->request();
    }

    /**
     * Disables the webhook.
     *
     * @param Webhook $webhook
     * @return Model
     */
    public function disable(Webhook $webhook)
    {
        $this->method = 'POST';
        $this->apiUrl = $this->apiUrl.$webhook->getId().'/disable';

        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth' => [config('paymongo.secret_key'), ''],
        ]);

        return $this->request();
    }

    /**
     * Updates the webhook.
     *
     * @param Webhook $webhook
     * @param array $payload
     * @return Model
     */
    public function update(Webhook $webhook, array $payload)
    {
        $this->method = 'PUT';
        $this->payload = $this->convertPayloadAmountsToInteger($payload);
        $this->apiUrl = $this->apiUrl.$webhook->getId();

        $this->formRequestData();
        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth' => [config('paymongo.secret_key'), ''],
            'json' => $this->data,
        ]);

        return $this->request();
    }

    /**
     * Cancels the payment intent.
     *
     * @param PaymentIntent $intent
     * @return Model
     */
    public function cancel(PaymentIntent $intent)
    {
        $this->method = 'POST';
        $this->apiUrl = $this->apiUrl.$intent->getId().'/cancel';

        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth' => [config('paymongo.secret_key'), ''],
        ]);

        return $this->request();
    }

    /**
     * Attach the payment method in the payment intent.
     *
     * @param PaymentIntent $intent
     * @param string $paymentMethodId
     * @return Model
     */
    public function attach($payload, $paymentIntentId,$apikey)
    {
        $this->method = 'POST';
        $this->apiUrl = $this->apiUrl.$paymentIntentId.'/attach';
        $this->payload = $payload;

        $this->formRequestData();
        $this->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => $this->data,
            'auth' => [$apikey, ''],
        ]);

        return $this->request();
    }

    /**
     * Send request to API.
     *
     * @return mixed|Throwable
     */
    protected function request()
    {
        $client = new Client();
 
        try {
            $response = $client->request($this->method, $this->apiUrl, $this->options);            
            $array = $this->parseToArray((string) $response->getBody());
            return  $array; 
            return $this->setReturnModel($array);
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            if ($e->getCode() === 400) {
                throw new BadRequestException($response['errors'][0]['detail'], $e->getCode());
            } elseif ($e->getCode() === 402) {
                throw new PaymentErrorException($response['errors'][0]['detail'], $e->getCode());
            } elseif ($e->getCode() === 404) {
                throw new NotFoundException($response['errors'][0]['detail'], $e->getCode());
            }
        }
    }

    /**
     * Sets the data to add data wrapper of the payload.
     *
     * @return void
     */
    protected function formRequestData()
    { 
        $this->data = [
            'data' => [
                'attributes' => $this->payload,
            ],
        ];
    }

    protected function parseToArray($jsonString)
    {
        return json_decode($jsonString, true);
    }

    protected function setReturnModel($array)
    {
        return (new $this->returnModel)->setData($array['data']);
    }

    protected function setOptions($options)
    {
        $this->options = $options;
    }

    protected function convertPayloadAmountsToInteger($payload)
    {
        if (isset($payload['amount'])) {
            $payload['amount'] *= 100;
        }

        return $payload;
    }
}
