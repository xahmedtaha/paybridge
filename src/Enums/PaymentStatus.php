<?php

namespace AhmedTaha\PayBridge\Enums;

enum PaymentStatus: string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';
    case PENDING = 'pending';
}
