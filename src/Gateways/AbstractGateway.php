<?php

namespace AhmedTaha\PayBridge\Gateways;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Data\ChargeData;
use AhmedTaha\PayBridge\Data\CustomerData;
use AhmedTaha\PayBridge\Interfaces\GatewayInterface;

abstract class AbstractGateway implements GatewayInterface
{
    protected array $credentials;

    protected mixed $environment;

    public function __construct(?array $credentials = null, $environment = null)
    {
        $this->credentials = $credentials ?? $this->getDefaultCredentials();
        $this->environment = $environment ?? $this->getPaymentEnvironment();
    }

    abstract protected function getDefaultCredentials();

    abstract protected function getPaymentEnvironment();

    abstract public function pay(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData);

    abstract public function verify();
}
