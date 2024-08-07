<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject;

use JsonSerializable;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\Enum\AcquiringChannel;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\Enum\Intent;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\Enum\Locale;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Payments\MerchantUrls;

final readonly class Payment implements JsonSerializable
{
    /**
     * @param OrderLine[] $orderLines
     * @param string[]|null $customPaymentMethodIds
     * @param array<string, string>|null $options
     */
    public function __construct(
        private PaymentCountry $paymentCountry,
        private Amount $orderAmount,
        private array $orderLines,
        private Intent $intent = Intent::buy,
        private AcquiringChannel $acquiringChannel = AcquiringChannel::ECOMMERCE,
        private Locale $locale = Locale::EnglishUnitedStates,
        private ?MerchantUrls $merchantUrls = null,
        private ?Customer $customer = null,
        private ?Address $billingAddress = null,
        private ?Address $shippingAddress = null,
        private ?string $merchantReference1 = null,
        private ?string $merchantReference2 = null,
        private ?Amount $orderTaxAmount = null,
        private ?string $merchantData = null,
        private ?Attachment $attachment = null,
        private ?array $customPaymentMethodIds = null,
        private ?string $design = null,
        private ?array $options = null,
    ) {
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getPaymentCountry(): PaymentCountry
    {
        return $this->paymentCountry;
    }

    public function getOrderAmount(): Amount
    {
        return $this->orderAmount;
    }

    /**
     * @return OrderLine[]
     */
    public function getOrderLines(): array
    {
        return $this->orderLines;
    }

    public function getIntent(): Intent
    {
        return $this->intent;
    }

    public function getMerchantUrls(): ?MerchantUrls
    {
        return $this->merchantUrls;
    }

    public function getAcquiringChannel(): AcquiringChannel
    {
        return $this->acquiringChannel;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function getMerchantReference1(): ?string
    {
        return $this->merchantReference1;
    }

    public function getMerchantReference2(): ?string
    {
        return $this->merchantReference2;
    }

    public function getOrderTaxAmount(): ?Amount
    {
        return $this->orderTaxAmount;
    }

    public function getMerchantData(): ?string
    {
        return $this->merchantData;
    }

    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    /**
     * @return string[]|null
     */
    public function getCustomPaymentMethodIds(): ?array
    {
        return $this->customPaymentMethodIds;
    }

    public function getDesign(): ?string
    {
        return $this->design;
    }

    /**
     * @return array<string, string>|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function jsonSerialize(): array
    {
        $payload = [
            'acquiring_channel' => $this->getAcquiringChannel()->value,
            'attachment' => $this->getAttachment(),
            'billing_address' => $this->getBillingAddress(),
            'custom_payment_method_ids' => $this->getCustomPaymentMethodIds(),
            'customer' => $this->getCustomer(),
            'design' => $this->getDesign(),
            'locale' => $this->getLocale()->value,
            'merchant_data' => $this->getMerchantData(),
            'merchant_reference1' => $this->getMerchantReference1(),
            'merchant_reference2' => $this->getMerchantReference2(),
            'merchant_urls' => $this->getMerchantUrls(),
            'options' => $this->getOptions(),
            'order_amount' => $this->getOrderAmount()->getISO4217Amount(),
            'order_lines' => $this->getOrderLines(),
            'order_tax_amount' => $this->getOrderTaxAmount()?->getISO4217Amount(),
            'purchase_country' => $this->getPaymentCountry()->getCountry()->value,
            'purchase_currency' => $this->getPaymentCountry()->getCurrency()->value,
            'shipping_address' => $this->getShippingAddress(),
            'intent' => $this->getIntent()->value,
        ];

        return array_filter($payload, static fn ($value) => $value !== null);
    }
}
