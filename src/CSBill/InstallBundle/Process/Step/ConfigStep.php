<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Process\Step;

use CSBill\InstallBundle\Form\Step\ConfigStepForm;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Step\ControllerStep;

class ConfigStep extends ControllerStep
{
    /**
     * Array of currently implemented database drivers.
     *
     * @var array
     */
    protected $implementedDrivers = array(
        'mysql',
    );

    /**
     * @var array
     */
    protected $mailerTransports = array(
        'mail' => 'PHP Mail',
        'sendmail' => 'Sendmail',
        'smtp' => 'SMTP',
        'gmail' => 'Gmail',
    );

    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $form = $this->getForm();

        return $this->render('CSBillInstallBundle:Flow:config.html.twig', array('form' => $form->createView()));
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(ProcessContextInterface $context)
    {
        $request = $context->getRequest();
        $form = $this->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $config = array();

            // sets the database details
            foreach ($data['database_config'] as $key => $param) {
                $key = sprintf('database_%s', $key);
                $config[$key] = $param;
            }

            // sets the database details
            foreach ($data['email_settings'] as $key => $param) {
                $key = sprintf('mailer_%s', $key);
                $config[$key] = $param;
            }

            $this->get('csbill.core.config_writer')->dump($config);

            return $this->complete();
        }

        return $this->render(
            'CSBillInstallBundle:Flow:config.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function getForm()
    {
        $availableDrivers = array_intersect($this->implementedDrivers, \PDO::getAvailableDrivers());
        $drivers = array_combine(
            array_map(
                function ($value) {
                    return sprintf('pdo_%s', $value);
                },
                $availableDrivers
            ),
            $availableDrivers
        );

        $config = $this->get('csbill.core.config_writer')->getConfigValues();

        $data = array(
            'database_config' => array(
                'host' => $config['database_host'],
                'port' => $config['database_port'],
                'name' => $config['database_name'],
            ),
        );

        $options = array(
            'drivers' => $drivers,
            'mailer_transports' => $this->mailerTransports,
            'action' => $this->generateUrl(
                'sylius_flow_forward',
                array(
                    'scenarioAlias' => 'install',
                    'stepName' => 'config',
                )
            ),
        );

        return $this->createForm(new ConfigStepForm(), $data, $options);
    }
}
