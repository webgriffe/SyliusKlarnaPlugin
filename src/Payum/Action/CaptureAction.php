<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ClientInterface;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\ApiContext;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Authorization;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\HostedPaymentPage;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Payment;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Response\HostedPaymentPageSession;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Response\PaymentSession;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Controller\PaymentController;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Model\PaymentDetails;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\KlarnaPaymentsApi;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\Request\Api\CreateHostedPaymentPageSession;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\Request\Api\CreatePaymentSession;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\Request\ConvertSyliusPaymentToKlarnaHostedPaymentPage;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Payum\Request\ConvertSyliusPaymentToKlarnaPayment;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress TypeDoesNotContainType
 *
 * @psalm-import-type StoredPaymentDetails from PaymentDetails
 */
final class CaptureAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait, GenericTokenFactoryAwareTrait, ApiAwareTrait;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {
        $this->apiClass = KlarnaPaymentsApi::class;
    }

    /**
     * This action is invoked by two main entry points:
     *  - Starting the payment.
     *      Assuming that the payment details are empty because it is the first attempt to pay, we proceed by creating
     *      the Klarna Payment Session. This session should still be opened during all the checkout on the gateway.
     *  - Returning after Klarna checkout.
     *      We should follow this case by catching any query parameters on the request
     *
     * @param Capture|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Capture::class);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        /** @var string|int $paymentId */
        $paymentId = $payment->getId();
        $this->logger->info(sprintf(
            'Start capture action for Sylius payment with ID "%s".',
            $paymentId,
        ));

        $captureToken = $request->getToken();
        Assert::isInstanceOf($captureToken, TokenInterface::class);

        $klarnaPaymentsApi = $this->api;
        Assert::isInstanceOf($klarnaPaymentsApi, KlarnaPaymentsApi::class);

        $storedPaymentDetails = $payment->getDetails();
        if ($storedPaymentDetails === []) {
            $paymentDetails = $this->createPaymentSession($payment);
        } else {
            if (!PaymentDetailsHelper::areValid($storedPaymentDetails)) {
                throw new RuntimeException('Payment details are already populated with others data. Maybe this payment should be marked as error');
            }
            $paymentDetails = PaymentDetails::createFromStoredPaymentDetails($storedPaymentDetails);
            $this->logger->info(sprintf(
                'Klarna payment session "%s" already created.',
                $paymentDetails->getPaymentSessionId(),
            ));
        }

        if ($paymentDetails->getHostedPaymentPageRedirectUrl() !== null) {
            $this->logger->info(sprintf(
                'Klarna payment session "%s" already contains the redirect url, so no need to continue here. Redirecting the user to the Sylius Klarna Payments waiting page.',
                $paymentDetails->getPaymentSessionId(),
            ));

            $session = $this->requestStack->getSession();
            $session->set(PaymentController::PAYMENT_ID_SESSION_KEY, $paymentId);
            $session->set(PaymentController::TOKEN_HASH_SESSION_KEY, $captureToken->getHash());

            $order = $payment->getOrder();
            Assert::isInstanceOf($order, OrderInterface::class);

            throw new HttpRedirect(
                $this->router->generate('webgriffe_sylius_klarna_payments_plugin_payment_process', [
                    'tokenValue' => $order->getTokenValue(),
                    '_locale' => $order->getLocaleCode(),
                ]),
            );
        }

        if ($paymentDetails->getHostedPaymentPageId() === null) {
            $this->createHostedPaymentPageSession(
                $paymentDetails,
                $captureToken,
            );
        }

        $payment->setDetails($paymentDetails->toStoredPaymentDetails());

        $hostedPaymentPageRedirectUrl = $paymentDetails->getHostedPaymentPageRedirectUrl();
        Assert::stringNotEmpty($hostedPaymentPageRedirectUrl);

        $this->logger->info(sprintf(
            'Redirecting the user to the Klarna Hosted Payment Page redirect URL "%s".',
            $hostedPaymentPageRedirectUrl,
        ));

        throw new HttpRedirect($hostedPaymentPageRedirectUrl);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }

    private function createPaymentSession(
        SyliusPaymentInterface $payment,
    ): PaymentDetails {
        $convertSyliusPaymentToKlarnaPayment = new ConvertSyliusPaymentToKlarnaPayment(
            $payment,
            null,
            null,
            null,
            null,
        );
        $this->gateway->execute($convertSyliusPaymentToKlarnaPayment);
        $klarnaPayment = $convertSyliusPaymentToKlarnaPayment->getKlarnaPayment();
        Assert::isInstanceOf($klarnaPayment, Payment::class);

        $createPaymentSession = new CreatePaymentSession($klarnaPayment);
        $this->gateway->execute($createPaymentSession);
        $paymentSession = $createPaymentSession->getPaymentSession();
        Assert::isInstanceOf($paymentSession, PaymentSession::class);

        /** @var string|int $paymentId */
        $paymentId = $payment->getId();
        $this->logger->info(sprintf(
            'Created Klarna Payment Session with ID "%s" for Sylius payment "%s".',
            $paymentSession->getSessionId(),
            $paymentId,
        ));

        return PaymentDetails::createFromPaymentSession($paymentSession);
    }

    private function createHostedPaymentPageSession(
        PaymentDetails $paymentDetails,
        TokenInterface $captureToken,
    ): void {
        $klarnaPaymentsApi = $this->api;
        Assert::isInstanceOf($klarnaPaymentsApi, KlarnaPaymentsApi::class);

        $apiContext = new ApiContext(
            new Authorization($klarnaPaymentsApi->getUsername(), $klarnaPaymentsApi->getPassword()),
            $klarnaPaymentsApi->getServerRegion(),
            $klarnaPaymentsApi->isSandBox(),
        );
        $cancelToken = $this->tokenFactory->createToken(
            $captureToken->getGatewayName(),
            $captureToken->getDetails(),
            'payum_cancel_do',
            [],
            $captureToken->getAfterUrl(),
        );
        $cancelUrl = $cancelToken->getTargetUrl();
        $notifyToken = $this->tokenFactory->createNotifyToken(
            $captureToken->getGatewayName(),
            $captureToken->getDetails(),
        );
        $notifyUrl = $notifyToken->getTargetUrl();

        $convertSyliusPaymentToKlarnaHostedPaymentPage = new ConvertSyliusPaymentToKlarnaHostedPaymentPage(
            $captureToken->getTargetUrl(),
            $notifyUrl,
            $cancelUrl,
            $cancelUrl,
            $cancelUrl,
            $cancelUrl,
            $this->client->createPaymentSessionUrl($apiContext, $paymentDetails->getPaymentSessionId()),
        );
        $this->gateway->execute($convertSyliusPaymentToKlarnaHostedPaymentPage);
        $klarnaHostedPaymentPage = $convertSyliusPaymentToKlarnaHostedPaymentPage->getKlarnaHostedPaymentPage();
        Assert::isInstanceOf($klarnaHostedPaymentPage, HostedPaymentPage::class);

        $createHostedPaymentPageSession = new CreateHostedPaymentPageSession($klarnaHostedPaymentPage);
        $this->gateway->execute($createHostedPaymentPageSession);
        $hostedPaymentPageSession = $createHostedPaymentPageSession->getHostedPaymentPageSession();
        Assert::isInstanceOf($hostedPaymentPageSession, HostedPaymentPageSession::class);

        $this->logger->info(sprintf(
            'Created Klarna Hosted Payment Page Session with ID "%s" for Klarna session with ID "%s".',
            $hostedPaymentPageSession->getSessionId(),
            $paymentDetails->getPaymentSessionId(),
        ));

        $paymentDetails->setHostedPaymentPageId($hostedPaymentPageSession->getSessionId());
        $paymentDetails->setHostedPaymentPageRedirectUrl($hostedPaymentPageSession->getRedirectUrl());
    }
}
