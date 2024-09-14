<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Enums\PaymentMethod;

class MobileWalletData extends AbstractPaymentData
{
    const METHOD = PaymentMethod::MOBILE_WALLET;

    public function __construct(
        protected string|int $walletNumber,
    ) {}

    public function getData(): array
    {
        return [
            'walletNumber' => $this->walletNumber,
        ];
    }
}
