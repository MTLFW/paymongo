<?php

namespace craft\commerce\paymongo;


class PaymentIntent extends ApiResource
{
    const OBJECT_NAME = 'payment_intent';

    //  use ApiOperations\All;
    //  use ApiOperations\Create;
    //  use ApiOperations\Retrieve;
    //  use ApiOperations\Update;

    const STATUS_CANCELED = 'canceled';
    const STATUS_PROCESSING = 'processing';
    const STATUS_REQUIRES_ACTION = 'requires_action';
    const STATUS_REQUIRES_CAPTURE = 'requires_capture';
    const STATUS_REQUIRES_CONFIRMATION = 'requires_confirmation';
    const STATUS_REQUIRES_PAYMENT_METHOD = 'requires_payment_method';
    const STATUS_SUCCEEDED = 'succeeded';

    public function constructFromIntent($resp, $opts){
        return  PaymongoObject::constructFrom($resp, $opts);
    }
   
}
