<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Interfaces\DataInterface;

abstract class AbstractPaymentData implements DataInterface
{
    public mixed $paymentMethod;

    abstract public function getData(): array;
}
