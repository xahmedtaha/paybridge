<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Enums\PaymentMethod;

// Used for when payment data isn't required or applicable (e.g. Hosted/Redirection Checkouts)
class NoPaymentData extends AbstractPaymentData
{
    const METHOD = PaymentMethod::NONE;
    public function getData(): array
    {
        return [];
    }
}
