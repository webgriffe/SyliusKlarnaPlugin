<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPlugin\Converter;

use Webgriffe\SyliusKlarnaPlugin\Client\ValueObject\HostedPaymentPage;

interface HostedPaymentPageConverterInterface
{
    public function convert(
        string $confirmationUrl,
        string $notificationUrl,
        string $backUrl,
        string $cancelUrl,
        string $errorUrl,
        string $failureUrl,
        string $paymentSessionUrl,
    ): HostedPaymentPage;
}
