<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Widgets;

interface WidgetInterface
{
    /**
     * @return array
     */
    public function getData();

    /**
     * @return string
     */
    public function getTemplate();
}
