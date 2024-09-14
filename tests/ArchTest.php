<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('it has only enums in the right namespace')
    ->expect('AhmedTaha\PayBridge\Enums')
    ->toBeStringBackedEnums();

arch('it has only interfaces in the right namespace')
    ->expect('AhmedTaha\PayBridge\Interfaces')
    ->toBeInterfaces();

arch('it has only data classes in the right namespace')
    ->expect('AhmedTaha\PayBridge\Data')
    ->toBeClasses()
    ->toImplement(\AhmedTaha\PayBridge\Interfaces\DataInterface::class);

arch('payment data classes extend from the AbstractPaymentData class')
    ->expect('AhmedTaha\PayBridge\Data\Payment')
    ->toBeClasses()
    ->toImplement(\AhmedTaha\PayBridge\Interfaces\DataInterface::class)
    ->toExtend(\AhmedTaha\PayBridge\Data\Payment\AbstractPaymentData::class);

arch('gateways extend from the AbstractGateway class')
    ->expect('AhmedTaha\PayBridge\Gateways')
    ->toBeClasses()
    ->toImplement(\AhmedTaha\PayBridge\Interfaces\GatewayInterface::class)
    ->toExtend(\AhmedTaha\PayBridge\Gateways\AbstractGateway::class);

arch('php preset')->preset()->php();
arch('security preset')->preset()->security();
