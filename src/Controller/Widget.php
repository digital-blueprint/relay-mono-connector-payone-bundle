<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Controller;

use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Dbp\Relay\MonoConnectorPayoneBundle\Config\ConfigurationService;
use Dbp\Relay\MonoConnectorPayoneBundle\Persistence\PaymentDataService;
use Dbp\Relay\MonoConnectorPayoneBundle\Service\PayunityService;
use Dbp\Relay\MonoConnectorPayoneBundle\Service\Utils;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Widget extends AbstractController
{
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var PayunityService
     */
    private $payunityService;

    /**
     * @var Locale
     */
    private $locale;

    /**
     * @var LoggerInterface
     */
    private $auditLogger;
    /**
     * @var PaymentDataService
     */
    private $paymentDataService;
    /**
     * @var ConfigurationService
     */
    private $configService;

    public function __construct(
        PaymentService $paymentService,
        PayunityService $payunityService,
        PaymentDataService $paymentDataService,
        ConfigurationService $configService,
        Locale $locale
    ) {
        $this->paymentService = $paymentService;
        $this->payunityService = $payunityService;
        $this->paymentDataService = $paymentDataService;
        $this->locale = $locale;
        $this->auditLogger = new NullLogger();
        $this->configService = $configService;
    }

    public function setAuditLogger(LoggerInterface $auditLogger): void
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * @param string[] $brands
     */
    public static function getTemplateForBrands(string $method, array $brands): string
    {
        // We currently have an apple specific template which is not compatible with others (could it be?)
        // so error out if someone wants to mix it.
        $wanted = [];
        foreach ($brands as $brand) {
            if ($brand === 'APPLEPAY') {
                $wanted[$brand] = 'applepay.html.twig';
            } else {
                $wanted[$brand] = 'index.html.twig';
            }
        }
        $final = array_unique(array_values($wanted));
        if (count($final) === 0) {
            return 'index.html.twig';
        } elseif (count($final) === 1) {
            return $final[0];
        } else {
            throw new \RuntimeException("Selected brands for method '$method' are not compatible");
        }
    }

    public function index(Request $request): Response
    {
        $identifier = (string) $request->query->get('identifier');
        $this->locale->setCurrentRequestLocaleFromQuery('lang');

        $payment = $this->paymentService->getPaymentPersistenceByIdentifier($identifier);
        $this->auditLogger->debug('payunity: loading widget page', $this->payunityService->getLoggingContext($payment));

        $paymentData = $this->paymentDataService->getByPaymentIdentifier($identifier);
        $contractId = $paymentData->getPspContract();
        $methodId = $paymentData->getPspMethod();

        $contract = $this->configService->getPaymentContractByIdentifier($contractId);
        $method = $contract->getPaymentMethod($methodId);

        // payunity supports a list of locales, which more or less match the primary language format,
        // so just use that instead fo hardcoding the list:
        // https://www.payunity.com/tutorials/integration-guide/customisation#optionslang
        $puLocale = $this->locale->getCurrentPrimaryLanguage();

        $shopperResultUrl = Utils::extendReturnUrl($payment->getPspReturnUrl());
        $scriptSrc = $this->payunityService->getPaymentScriptSrc($payment, $paymentData);
        $context = [
            'shopperResultUrl' => $shopperResultUrl,
            'brands' => $method->getBrands(),
            'scriptSrc' => $scriptSrc,
            'recipient' => $payment->getRecipient(),
            'locale' => $puLocale,
        ];

        $loader = new FilesystemLoader(dirname(__FILE__).'/../Resources/views/');
        $twig = new Environment($loader);
        $template = self::getTemplateForBrands($method->getIdentifier(), $method->getBrands());
        $template = $twig->load($template);
        $content = $template->render($context);

        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
