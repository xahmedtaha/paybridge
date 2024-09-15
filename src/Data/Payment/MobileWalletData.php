<?php

namespace AhmedTaha\PayBridge\Data\Payment;

use AhmedTaha\PayBridge\Enums\PaymentMethod;

class MobileWalletData extends AbstractPaymentData
{
    public function __construct(
        public readonly string|int $walletNumber,
    ) {}

    public function getData(): array
    {
        return [
            'walletNumber' => $this->walletNumber,
        ];
    }
}
