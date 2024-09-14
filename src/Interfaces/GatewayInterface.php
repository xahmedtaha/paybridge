<?php

namespace AhmedTaha\PayBridge\Interfaces;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Data\ChargeData;
use AhmedTaha\PayBridge\Data\CustomerData;
use Illuminate\Http\Request;

interface GatewayInterface
{
    public function pay(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData): array;

    public function callback(Request $request);
}
