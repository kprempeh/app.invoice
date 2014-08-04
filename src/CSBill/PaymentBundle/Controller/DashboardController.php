<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;

class DashboardController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recentAction()
    {
        $payments = $this->getRepository('CSBillPaymentBundle:Payment')->getRecentPayments();

        return $this->render('CSBillPaymentBundle:Dashboard:recent.html.twig', array('payments' => $payments));
    }
} 