<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle;

use CSBill\DashboardBundle\Widgets\WidgetInterface;

class WidgetFactory
{
    const DEFAULT_LOCATION = 'top';

    /**
     * @var \SplPriorityQueue[]
     */
    private $queues = array();

    private $locations = array('top', 'left_column', 'right_column');

    public function __construct()
    {
        foreach ($this->locations as $location) {
            $this->queues[$location] = new \SplPriorityQueue();
        }
    }

    /**
     * @param WidgetInterface $widget
     * @param string          $location
     * @param int             $priority
     *
     * @throws \Exception
     */
    public function add(WidgetInterface $widget, $location = null, $priority = null)
    {
        $location = $location ?: self::DEFAULT_LOCATION;

        if (!isset($this->queues[$location])) {
            throw new \Exception(sprintf('Invalid widget location: %s', $location));
        }

        $this->queues[$location]->insert($widget, $priority);
    }

    /**
     * @param string $location
     *
     * @return \SplPriorityQueue
     */
    public function get($location)
    {
        if (!isset($this->queues[$location])) {
            return new \SplPriorityQueue();
        }

        return $this->queues[$location];
    }
}
