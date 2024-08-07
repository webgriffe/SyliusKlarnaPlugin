<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPaymentsPlugin\Converter;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use LogicException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\Enum\AcquiringChannel;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\Enum\Intent;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Amount;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Payment;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Payments\MerchantUrls;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Resolver\PaymentCountryResolverInterface;
use Webmozart\Assert\Assert;

final readonly class PaymentConverter implements PaymentConverterInterface
{
    use CommonOrderConverterTrait;

    public function __construct(
        private PaymentCountryResolverInterface $paymentCountryResolver,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private CacheManager $cacheManager,
        private string $mainImageType,
        private string $imageFilter,
    ) {
    }

    public function convert(
        PaymentInterface $payment,
        ?string $confirmationUrl,
        ?string $notificationUrl,
        ?string $pushUrl,
        ?string $authorizationUrl,
    ): Payment {
        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);
        $billingAddress = $order->getBillingAddress();
        $purchaseCountry = $billingAddress?->getCountryCode();
        Assert::notNull($purchaseCountry, 'Purchase country is required to create a payment on Klarna.');
        $purchaseCurrency = $order->getCurrencyCode();
        Assert::notNull($purchaseCurrency, 'Purchase currency is required to create a payment on Klarna.');
        $paymentCountry = $this->paymentCountryResolver->resolve($payment);
        if ($purchaseCurrency !== $paymentCountry->getCurrency()->value) {
            throw new LogicException(sprintf(
                'Attention! The order currency is "%s", but for the country "%s" Klarna only supports currency
                "%s". Please, change the channel configuration or implement a way to handle currencies change.',
                $purchaseCurrency,
                $purchaseCountry,
                $paymentCountry->getCurrency()->value,
            ));
        }
        $merchantUrls = null;
        if ($confirmationUrl !== null || $notificationUrl !== null || $pushUrl !== null || $authorizationUrl !== null) {
            $merchantUrls = new MerchantUrls(
                $confirmationUrl,
                $notificationUrl,
                $pushUrl,
                $authorizationUrl,
            );
        }

        return new Payment(
            $paymentCountry,
            Amount::fromSyliusAmount($order->getTotal()),
            $this->getOrderLines($order),
            Intent::buy,
            AcquiringChannel::ECOMMERCE,
            $paymentCountry->matchUserLocale($order->getLocaleCode()),
            $merchantUrls,
            $this->getCustomer($order),
            $this->getAddress($billingAddress, $order->getCustomer()),
            $this->getAddress($order->getShippingAddress(), $order->getCustomer()),
            '#' . (string) $order->getNumber(),
            null,
            Amount::fromSyliusAmount($order->getTaxTotal()),
            sprintf('#%s@%s', (string) $order->getId(), (string) $payment->getId()),
        );
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator;
    }

    private function getCacheManager(): CacheManager
    {
        return $this->cacheManager;
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    private function getSchema(): string
    {
        return 'https';
    }

    private function getImageFilter(): string
    {
        return $this->imageFilter;
    }

    private function getMainImageType(): string
    {
        return $this->mainImageType;
    }
}
