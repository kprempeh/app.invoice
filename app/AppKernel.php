<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use CSBill\CoreBundle\Kernel\ContainerClassKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel implements ContainerClassKernelInterface
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),

            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new APY\DataGridBundle\APYDataGridBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Payum\Bundle\PayumBundle\PayumBundle(),
            new Sylius\Bundle\FlowBundle\SyliusFlowBundle(),
            new Finite\Bundle\FiniteBundle\FiniteFiniteBundle(),

            new CSBill\CoreBundle\CSBillCoreBundle(),
            new CSBill\InstallBundle\CSBillInstallBundle(),
            new CSBill\ClientBundle\CSBillClientBundle(),
            new CSBill\DataGridBundle\CSBillDataGridBundle(),
            new CSBill\QuoteBundle\CSBillQuoteBundle(),
            new CSBill\InvoiceBundle\CSBillInvoiceBundle(),
            new CSBill\ItemBundle\CSBillItemBundle(),
            new CSBill\SettingsBundle\CSBillSettingsBundle(),
            new CSBill\UserBundle\CSBillUserBundle(),
            new CSBill\PaymentBundle\CSBillPaymentBundle(),
            new CSBill\DashboardBundle\CSBillDashboardBundle(),
            new CSBill\TaxBundle\CSBillTaxBundle(),
            new CSBill\NotificationBundle\CSBillNotificationBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new JMS\DebuggingBundle\JMSDebuggingBundle($this);
            }
        }

        return $bundles;
    }

    /**
     * Return the name of the cached container class
     *
     * @return string
     */
    public function getContainerCacheClass()
    {
        return $this->getContainerClass();
    }

    protected function getContainerBaseClass()
    {
        if (in_array($this->getEnvironment(), array('dev'))) {
            return '\JMS\DebuggingBundle\DependencyInjection\TraceableContainer';
        }

        return parent::getContainerBaseClass();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
