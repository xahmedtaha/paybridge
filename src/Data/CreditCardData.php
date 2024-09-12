<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Enums\PaymentMethod;

class CreditCardData extends AbstractPaymentData
{
    public mixed $paymentMethod = PaymentMethod::CREDIT_CARD;

    public function __construct(
        public string|int $cardNumber,
        public string|int $expiryYear,
        public string|int $expiryMonth,
        public string|int $cvv,
    ) {}

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
