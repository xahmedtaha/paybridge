<?php

namespace AhmedTaha\PayBridge\Data\Payment;

use AhmedTaha\PayBridge\Enums\PaymentMethod;

// Used for when payment data isn't required or applicable (e.g. Hosted/Redirection Checkouts)
class EmptyPaymentData extends AbstractPaymentData
{
    public function getData(): array
    {
        return [];
    }
}
