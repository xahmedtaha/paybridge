<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Interfaces\DataInterface;

abstract class AbstractPaymentData implements DataInterface
{
    const METHOD = null;

    abstract public function getData(): array;
}
