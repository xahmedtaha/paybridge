<?php

namespace AhmedTaha\PayBridge\Enums;

use AhmedTaha\PayBridge\Gateways\FawryPayGateway;

enum PaymentGateway: string
{
    case FawryPay = FawryPayGateway::class;
}
