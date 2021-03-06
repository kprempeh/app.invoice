<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Listener\Doctrine;

use CSBill\CoreBundle\Util\ArrayUtil;
use CSBill\InvoiceBundle\Entity\Invoice;
use Doctrine\ORM\Event\LifecycleEventArgs;

class InvoiceUsersListener
{
    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $entity = $event->getEntity();

        if ($entity instanceof Invoice && count($entity->getUsers()) > 0) {
            $contacts = $em->getRepository('CSBillClientBundle:Contact')
                ->findBy(
                    array(
                        'id' => ArrayUtil::column($entity->getUsers()->toArray(), 'id'),
                    )
                );

            $entity->setUsers($contacts);
        }
    }
}
