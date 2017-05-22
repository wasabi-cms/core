<?php
/**
 * Wasabi Core
 * Copyright (c) Frank Förster (http://frankfoerster.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Frank Förster (http://frankfoerster.com)
 * @link          https://github.com/wasabi-cms/core Wasabi Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Wasabi\Core\Controller;

use Cake\Event\Event;
use Cake\Utility\Hash;

/**
 * Class DashboardController
 */
class DashboardController extends BackendAppController
{
    /**
     * Index action
     * GET
     *
     * @return void
     */
    public function index()
    {
        $event = new Event('Dashboard.SummaryBoxes.init');
        $this->eventManager()->dispatch($event);
        $summaryBoxes = $event->getResult();

        $summaryBoxes = Hash::sort($summaryBoxes, '{*}.priority');

        $this->set([
            'summaryBoxes' => $summaryBoxes
        ]);
    }
}
