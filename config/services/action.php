<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusKlarnaPlugin\Payum\Action\Api\CreateHostedPaymentPageSessionAction;
use Webgriffe\SyliusKlarnaPlugin\Payum\Action\Api\CreatePaymentSessionAction;
use Webgriffe\SyliusKlarnaPlugin\Payum\Action\CaptureAction;
use Webgriffe\SyliusKlarnaPlugin\Payum\Action\ConvertSyliusPaymentToKlarnaHostedPaymentPageAction;
use Webgriffe\SyliusKlarnaPlugin\Payum\Action\ConvertSyliusPaymentToKlarnaPaymentAction;
use Webgriffe\SyliusKlarnaPlugin\Payum\Action\StatusAction;
use Webgriffe\SyliusKlarnaPlugin\Payum\KlarnaPaymentsApi;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_klarna.payum.action.capture', CaptureAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_klarna.client')
        ])
        ->tag('payum.action', ['factory' => KlarnaPaymentsApi::CODE, 'alias' => 'payum.action.capture'])
    ;

    $services->set('webgriffe_sylius_klarna.payum.action.status', StatusAction::class)
        ->public()
    ;

    $services->set('webgriffe_sylius_klarna.payum.action.convert_sylius_payment_to_klarna_payment', ConvertSyliusPaymentToKlarnaPaymentAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_klarna.converter.payment'),
        ])
        ->tag('payum.action', ['factory' => KlarnaPaymentsApi::CODE, 'alias' => 'payum.action.convert_sylius_payment_to_klarna_payment'])
    ;

    $services->set('webgriffe_sylius_klarna.payum.action.convert_sylius_payment_to_klarna_hosted_payment_page', ConvertSyliusPaymentToKlarnaHostedPaymentPageAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_klarna.converter.hosted_payment_page'),
        ])
        ->tag('payum.action', ['factory' => KlarnaPaymentsApi::CODE, 'alias' => 'payum.action.convert_sylius_payment_to_klarna_hosted_payment_page'])
    ;

    $services->set('webgriffe_sylius_klarna.payum.action.api.create_payment_session', CreatePaymentSessionAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_klarna.client'),
        ])
        ->tag('payum.action', ['factory' => KlarnaPaymentsApi::CODE, 'alias' => 'payum.action.api.create_payment_session'])
    ;

    $services->set('webgriffe_sylius_klarna.payum.action.api.create_hosted_payment_page_session', CreateHostedPaymentPageSessionAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_klarna.client'),
        ])
        ->tag('payum.action', ['factory' => KlarnaPaymentsApi::CODE, 'alias' => 'payum.action.api.create_hosted_payment_page_session'])
    ;
};
