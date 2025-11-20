<?php

/* 
 * Copyright (C) 2018 KAINOTOMO PH LTD <info@kainotomo.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace KAINOTOMO\Component\Cesesubmitproposal\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\ComponentDispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * ComponentDispatcher class for com_users
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Override checkAccess to allow users edit profile without having to have core.manager permission
     *
     * @return  void
     *
     * @since  4.0.0
     */
    protected function checkAccess()
    {
        $task         = $this->input->getCmd('task');
        $view         = $this->input->getCmd('view');
        $layout       = $this->input->getCmd('layout');
        $allowedTasks = ['user.edit', 'user.apply', 'user.save', 'user.cancel'];

        // Allow users to edit their own account
        if (in_array($task, $allowedTasks, true) || ($view === 'user' && $layout === 'edit')) {
            $user = $this->app->getIdentity();
            $id   = $this->input->getInt('id');

            if ((int) $user->id === $id) {
                return;
            }
        }

        /**
         * Special case: Multi-factor Authentication
         *
         * We allow access to all MFA views and tasks. Access control for MFA tasks is performed in
         * the Controllers since what is allowed depends on who is logged in and whose account you
         * are trying to modify. Implementing these checks in the Dispatcher would violate the
         * separation of concerns.
         */
        $allowedViews  = ['callback', 'captive', 'method', 'methods'];
        $isAllowedTask = array_reduce(
            $allowedViews,
            function ($carry, $taskPrefix) use ($task) {
                return $carry || strpos($task ?? '', $taskPrefix . '.') === 0;
            },
            false
        );

        if (in_array(strtolower($view ?? ''), $allowedViews) || $isAllowedTask) {
            return;
        }

        parent::checkAccess();
    }
}
