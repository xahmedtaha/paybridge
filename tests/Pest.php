<?php

use AhmedTaha\PayBridge\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

pest()->group('Gateways')->in('Gateways');

pest()->group('FawryPay')->in('Gateways/FawryPay');
