<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Command;

use CSBill\CoreBundle\CSBillCoreBundle;
use CSBill\CoreBundle\Repository\VersionRepository;
use CSBill\InstallBundle\Exception\ApplicationInstalledException;
use CSBill\UserBundle\Entity\User;
use Doctrine\DBAL\DriverManager;
use RandomLib\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class InstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:install')
            ->setDescription('Installs the application')
            ->addOption('database-driver', null, InputOption::VALUE_REQUIRED, 'The database driver to use (Only pdo_mysql supported)', 'pdo_mysql')
            ->addOption('database-host', null, InputOption::VALUE_REQUIRED, 'The database host', 'localhost')
            ->addOption('database-port', null, InputOption::VALUE_REQUIRED, 'The database port', 3306)
            ->addOption('database-name', null, InputOption::VALUE_REQUIRED, 'The name of the database to use (will be created if it doesn\'t exist)', 'csbill')
            ->addOption('database-user', null, InputOption::VALUE_REQUIRED, 'The name of the database user')
            ->addOption('database-password', null, InputOption::VALUE_REQUIRED, 'The password for the database user')

            ->addOption('mailer-transport', null, InputOption::VALUE_REQUIRED, 'The email transport to use (PHPMail, Sendmail, SMTP, Gmail)', 'mail')
            ->addOption('mailer-host', null, InputOption::VALUE_REQUIRED, 'The email host (only applicable for SMTP)', 'localhost')
            ->addOption('mailer-user', null, InputOption::VALUE_REQUIRED, 'The user for email authentication (only applicable for SMTP and Gmail)')
            ->addOption('mailer-password', null, InputOption::VALUE_REQUIRED, 'The password for the email user (only applicable for SMTP and Gmail)')
            ->addOption('mailer-port', null, InputOption::VALUE_REQUIRED, 'The email port to use  (only applicable for SMTP and Gmail)', 25)
            ->addOption('mailer-encryption', null, InputOption::VALUE_REQUIRED, 'The encryption to use for email, if any')

            ->addOption('admin-username', null, InputOption::VALUE_REQUIRED, 'The username of the admin user')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'The password of admin user')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'The email address of admin user')

            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The locale to use')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'The currency to use')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null !== $this->getContainer()->getParameter('installed')) {
            throw new ApplicationInstalledException();
        }

        $this->validate($input);

        if (1 === ($return = $this->checkRequirements())) {
            return $return;
        }

        $this->saveConfig($input)
            ->install($input, $output);

        $success = $this
            ->getHelper('formatter')
            ->formatBlock('Application installed successfully!', 'bg=green;options=bold', true);

        $output->writeln('');
        $output->writeln($success);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null !== $this->getContainer()->getParameter('installed')) {
            throw new ApplicationInstalledException();
        }

        $currencies = Intl::getCurrencyBundle()->getCurrencyNames();
        $locales = Intl::getLocaleBundle()->getLocaleNames();

        $localeQuestion = new Question('<question>Please enter a locale:</question> ');
        $localeQuestion->setAutocompleterValues($locales);

        $currencyQuestion = new Question('<question>Please enter a currency:</question> ');
        $currencyQuestion->setAutocompleterValues($currencies);

        $passwordQuestion = new Question('<question>Please enter a password for the admin account:</question> ');
        $passwordQuestion->setHidden(true);

        $options = array(
            'database-user' => new Question('<question>Please enter your database user name:</question> '),
            'admin-username' => new Question('<question>Please enter a username for the admin account:</question> '),
            'admin-password' => $passwordQuestion,
            'admin-email' => new Question('<question>Please enter an email address for the admin account:</question> '),
            'locale' => $localeQuestion,
            'currency' => $currencyQuestion,
        );

        /** @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');

        /** @var Question $question */
        foreach ($options as $option => $question) {
            if (null === $input->getOption($option)) {

                $value = null;

                while (empty($value)) {
                    $value = $dialog->ask($input, $output, $question);

                    if ($values = $question->getAutocompleterValues()) {
                        $value = array_search($value, $values);
                    }
                }

                $input->setOption($option, $value);
            }
        }
    }

    /**
     * Checks if the system matches all the requirements
     *
     * @return int
     */
    private function checkRequirements()
    {
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $return = true;

        return require_once $rootDir.DIRECTORY_SEPARATOR.'app_check.php';
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function install(InputInterface $input, OutputInterface $output)
    {
        if ($this->initDb($input, $output)) {
            $this->createAdminUser($input, $output);

            $version = CSBillCoreBundle::VERSION;

            $entityManager = $this->getContainer()->get('doctrine')->getManager();

            /** @var VersionRepository $repository */
            $repository = $entityManager->getRepository('CSBillCoreBundle:Version');

            $repository->updateVersion($version);

            $time = new \DateTime('NOW');

            $config = array(
                'installed' => $time->format(\DateTime::ISO8601),
            );

            $this->getContainer()->get('csbill.core.config_writer')->dump($config);
        }
    }

    /**
     * @param InputInterface $input
     *
     * @return $this
     */
    private function saveConfig(InputInterface $input)
    {
        $factory = new Factory;

        // Don't update installed here, in case something goes wrong with the rest of the installation process
        $config = array(
            'database_driver' => $input->getOption('database-driver'),
            'database_host' => $input->getOption('database-host'),
            'database_port' => $input->getOption('database-port'),
            'database_name' => $input->getOption('database-name'),
            'database_user' => $input->getOption('database-user'),
            'database_password' => $input->getOption('database-password'),
            'mailer_transport' => $input->getOption('mailer-transport'),
            'mailer_host' => $input->getOption('mailer-host'),
            'mailer_user' => $input->getOption('mailer-user'),
            'mailer_password' => $input->getOption('mailer-password'),
            'mailer_port' => $input->getOption('mailer-port'),
            'mailer_encryption' => $input->getOption('mailer-encryption'),
            'locale' => $input->getOption('locale'),
            'currency' => $input->getOption('currency'),
            'secret' => $factory->getMediumStrengthGenerator()->generateString(32),
        );

        $this->getContainer()->get('csbill.core.config_writer')->dump($config);

        return $this;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     * @throws \Exception
     */
    private function initDb(InputInterface $input, OutputInterface $output)
    {
        $this->createDb($input, $output);

        $migration = $this->getContainer()->get('csbill.installer.database.migration');

        $callback = function ($message) use ($output) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln($message);
            }
        };

        $output->writeln('<info>Running database migrations</info>');

        $migration->migrate($callback);

        return true;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    private function createDb(InputInterface $input, OutputInterface $output)
    {
        $dbName = $input->getOption('database-name');

        $params = array(
            'driver' => $input->getOption('database-driver'),
            'host' => $input->getOption('database-host'),
            'port' => $input->getOption('database-port'),
            'user' => $input->getOption('database-user'),
            'password' => $input->getOption('database-password'),
            'charset' => 'UTF8',
            'driverOptions' => array()
        );

        $tmpConnection = DriverManager::getConnection($params);

        try {
            $tmpConnection->getSchemaManager()->createDatabase($dbName);
            $output->writeln(sprintf('<info>Created database %s</info>', $dbName));
        } catch (\Exception $e) {
            if (false !== strpos($e->getMessage(), 'database exists')) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    $output->writeln(sprintf('<info>Database %s already exists</info>', $dbName));
                }
            } else {
                throw $e;
            }
        }

        $params['dbname'] = $dbName;

        // Set the current connection to the new DB name
        $connection = $this->getContainer()->get('doctrine')->getConnection();

        if ($connection->isConnected()) {
            $connection->close();
        }

        $connection->__construct(
            $params,
            $connection->getDriver(),
            $connection->getConfiguration(),
            $connection->getEventManager()
        );

        $connection->connect();

        return true;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function createAdminUser(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Creating Admin User</info>');

        $user = new User();

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);

        $password = $encoder->encodePassword($input->getOption('admin-password'), $user->getSalt());

        $user->setUsername($input->getOption('admin-username'))
            ->setEmail($input->getOption('admin-email'))
            ->setPassword($password)
            ->setEnabled(true)
            ->setSuperAdmin(true);

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entityManager->persist($user);
        $entityManager->flush();
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Exception
     */
    private function validate(Inputinterface $input)
    {
        $values = array(
            'database-host',
            'database-user',
            'admin-username',
            'admin-password',
            'admin-email',
            'locale',
            'currency',
        );

        foreach ($values as $option) {
            if (null === $input->getOption($option)) {
                throw new \Exception(sprintf('The --%s option needs to be specified', $option));
            }
        }

        $currencies = Intl::getCurrencyBundle()->getCurrencyNames();
        $locales = Intl::getLocaleBundle()->getLocaleNames();

        if (!array_key_exists($locale = $input->getOption('locale'), $locales)) {
            throw new \InvalidArgumentException(sprintf('The locale "%s" is invalid', $locale));
        }

        if (!array_key_exists($currency = $input->getOption('currency'), $currencies)) {
            throw new \InvalidArgumentException(sprintf('The currency "%s" is invalid', $currency));
        }

        if ('smtp' === strtolower($input->getOption('mailer-transport'))) {
            if (null == $input->getOption('mailer-host')) {
                throw new \Exception('The --mailer-host option needs to be specified when using SMTP as email transport');
            }

            if (null == $input->getOption('mailer-port')) {
                throw new \Exception('The --mailer-port option needs to be specified when using SMTP as email transport');
            }
        } elseif ('gmail' === strtolower($input->getOption('mailer-transport'))) {
            if (null == $input->getOption('mailer-user')) {
                throw new \Exception('The --mailer-user option needs to be specified when using Gmail as email transport');
            }

            if (null == $input->getOption('mailer-password')) {
                throw new \Exception('The --mailer-password option needs to be specified when using Gmail as email transport');
            }
        }
    }
}