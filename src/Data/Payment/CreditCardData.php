<?php

namespace AhmedTaha\PayBridge\Data\Payment;

use AhmedTaha\PayBridge\Enums\PaymentMethod;

class CreditCardData extends AbstractPaymentData
{
    public readonly string|int $cardNumber;

    public function __construct(
        string|int $cardNumber,
        public readonly string|int $expiryYear,
        public readonly string|int $expiryMonth,
        public readonly string|int $cvv,
    ) {
        if (is_string($cardNumber)) {
            $this->cardNumber = str_replace(' ', '', $cardNumber);
        }
    }

    public function getData(): array
    {
        return [
            'cardNumber' => $this->cardNumber,
            'expiryYear' => $this->expiryYear,
            'expiryMonth' => $this->expiryMonth,
            'cvv' => $this->cvv,
        ];
    }
}
