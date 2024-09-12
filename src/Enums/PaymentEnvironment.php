<?php

namespace AhmedTaha\PayBridge\Enums;

enum PaymentEnvironment: string
{
    case TESTING = 'testing';
    case PRODUCTION = 'production';
}
