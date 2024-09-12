<?php

namespace AhmedTaha\PayBridge\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AhmedTaha\PayBridge\PayBridge
 */
class PayBridge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AhmedTaha\PayBridge\PayBridge::class;
    }
}
