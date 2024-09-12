<?php

namespace AhmedTaha\PayBridge\Interfaces;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Data\ChargeData;
use AhmedTaha\PayBridge\Data\CustomerData;

interface GatewayInterface
{
    public function pay(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData);

    public function callback();
}
