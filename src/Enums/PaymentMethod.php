<?php

namespace AhmedTaha\PayBridge\Enums;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'cc';
    case MOBILE_WALLET = 'mobileWallet';
    case ANY = 'any'; // Used for when payment data isn't required or applicable (e.g. Hosted/Redirection Checkouts or Callbacks)
    case PAY_AT_FAWRY = 'payAtFawry';
}
