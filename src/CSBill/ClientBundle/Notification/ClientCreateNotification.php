<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Notification;

use CSBill\NotificationBundle\Notification\NotificationMessage;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ClientCreateNotification extends NotificationMessage
{
    const HTML_TEMPLATE = 'CSBillClientBundle:Email:client_create.html.twig';

    const TEXT_TEMPLATE = 'CSBillClientBundle:Email:client_create.text.twig';

    /**
     * {@inheritdoc}
     */
    public function getHtmlContent(EngineInterface $templating = null)
    {
        return $templating->render(self::HTML_TEMPLATE, $this->getParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getTextContent(EngineInterface $templating = null)
    {
        return $templating->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject(TranslatorInterface $translator = null)
    {
        return $translator->trans('client.create.subject', array(), 'email');
    }
}
