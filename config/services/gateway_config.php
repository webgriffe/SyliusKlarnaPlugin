<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\KlarnaPaymentsApi;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\KlarnaPaymentsGatewayFactory;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_klarna_payments.gateway_factory_builder', GatewayFactoryBuilder::class)
        ->args([
            KlarnaPaymentsGatewayFactory::class,
        ])
        ->tag('payum.gateway_factory_builder', ['factory' => KlarnaPaymentsApi::CODE])
    ;

};
