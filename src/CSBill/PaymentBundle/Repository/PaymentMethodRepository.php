<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class PaymentMethodRepository extends EntityRepository
{
    /**
     * @param string $paymentMethod
     *
     * @return array
     */
    public function getSettingsForMethodArray($paymentMethod)
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $queryBuilder->select('pm.settings')
            ->where('pm.paymentMethod = :paymentMethod')
            ->setParameter('paymentMethod', $paymentMethod);

        try {
            $settings = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return array();
        }

        return $settings['settings'];
    }

    /**
     * Get the total number of payment gateways configured.
     *
     * @return int
     */
    public function getTotalMethodsConfigured()
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $queryBuilder->select('COUNT(pm.id)')
            ->where('pm.enabled = 1');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
