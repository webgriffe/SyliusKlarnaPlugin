<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPlugin\Resolver;

use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusKlarnaPlugin\Client\ValueObject\PaymentCountry;

interface PaymentCountryResolverInterface
{
    /**
     * Return the mapping in URL https://docs.klarna.com/klarna-payments/before-you-start/data-requirements/puchase-countries-currencies-locales/
     *
     * @return array<string, PaymentCountry>
     */
    public function getDefaultDataMapping(): array;

    public function resolve(PaymentInterface $payment): PaymentCountry;
}
