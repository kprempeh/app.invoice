<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Notification;

use CSBill\CoreBundle\Mailer\Exception\UnexpectedFormatException;
use CSBill\SettingsBundle\Manager\SettingsManager;
use Namshi\Notificator\Notification\HipChat\HipChatNotification;
use Namshi\Notificator\NotificationInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Factory
{
    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SettingsManager
     */
    private $settings;

    /**
     * @param EngineInterface     $templating
     * @param TranslatorInterface $translator
     * @param SettingsManager     $settings
     */
    public function __construct(
        EngineInterface $templating,
        TranslatorInterface $translator,
        SettingsManager $settings
    ) {
        $this->templating = $templating;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    /**
     * @param NotificationMessageInterface $message
     *
     * @return NotificationInterface
     */
    public function createEmailNotification(NotificationMessageInterface $message)
    {
        $swiftMessage = \Swift_Message::newInstance();

        $from = array($this->settings->get('email.from_address') => $this->settings->get('email.from_name'));

        $swiftMessage->setFrom($from);
        $swiftMessage->setSubject($message->getSubject($this->translator));

        foreach ($message->getUsers() as $user) {
            $swiftMessage->addTo($user->getEmail(), $user->getUsername());
        }

        $format = (string) $this->settings->get('email.format');

        switch ($format) {
            case 'html':
                $swiftMessage->setBody($message->getHtmlContent($this->templating), 'text/html');
                break;

            case 'text':
                $swiftMessage->setBody($message->getTextContent($this->templating), 'text/plain');
                break;

            case 'both':
                $swiftMessage->setBody($message->getHtmlContent($this->templating), 'text/html');
                $swiftMessage->addPart($message->getTextContent($this->templating), 'text/plain');
                break;

            default:
                throw new UnexpectedFormatException($format);
        }

        return new SwiftMailerNotification($swiftMessage);
    }

    /**
     * @param NotificationMessageInterface $message
     *
     * @return NotificationInterface
     */
    public function createHipchatNotification(NotificationMessageInterface $message)
    {
        $content = $message->getTextContent($this->templating);

        return new HipChatNotification(
            $content,
            $this->settings->get('system.general.app_name'),
            $this->settings->get('hipchat.room_id'),
            array(
                'hipchat_color' => $this->settings->get('hipchat.message_color'),
                'hipchat_notify' => (string) $this->settings->get('hipchat.notify'),
            )
        );
    }

    /**
     * @param string                       $cellphone
     * @param NotificationMessageInterface $message
     *
     * @return NotificationInterface
     */
    public function createSmsNotification($cellphone, NotificationMessageInterface $message)
    {
        return new TwilioNotification($cellphone, $message->getTextContent($this->templating));
    }
}
