<?php

namespace craft\commerce\paymongo\util;

class ObjectTypes
{
    /**
     * @var array Mapping from object types to resource classes
     */
    const mapping = [        
        \craft\commerce\paymongo\PaymentIntent::OBJECT_NAME =>  \craft\commerce\paymongo\PaymentIntent::class,        
    ];
}
