<?php

namespace AhmedTaha\PayBridge\Data\Payment;

use AhmedTaha\PayBridge\Enums\PaymentMethod;

class CreditCardData extends AbstractPaymentData
{
    const METHOD = PaymentMethod::CREDIT_CARD;

    public function __construct(
        protected string|int $cardNumber,
        protected string|int $expiryYear,
        protected string|int $expiryMonth,
        protected string|int $cvv,
    ) {
        if (is_string($this->cardNumber)) {
            $this->cardNumber = str_replace(' ', '', $this->cardNumber);
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
