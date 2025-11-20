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

namespace KAINOTOMO\Component\Cesesubmitproposal\Administrator\Extension;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use KAINOTOMO\Component\Cesesubmitproposal\Administrator\Service\HTML\AdministratorService;
use KAINOTOMO\Component\Cesesubmitproposal\Administrator\Service\HTML\Users;
use Psr\Container\ContainerInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component class for com_users
 *
 * @since  4.0.0
 */
class CesesubmitproposalComponent extends MVCComponent implements BootableExtensionInterface, RouterServiceInterface, FieldsServiceInterface
{
    use RouterServiceTrait;
    use HTMLRegistryAwareTrait;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function boot(ContainerInterface $container)
    {
        $this->getRegistry()->register('users', new Users());
    }

    /**
     * Returns a valid section for the given section. If it is not valid then null is returned.
     *
     * @param   string       $section  The section to get the mapping for
     * @param   object|null  $item     The content item or null
     *
     * @return  string|null  The new section or null
     *
     * @since   4.0.0
     */
    public function validateSection($section, $item = null)
    {
        if (Factory::getApplication()->isClient('site')) {
            switch ($section) {
                case 'registration':
                case 'profile':
                    return 'user';
            }
        }

        if ($section === 'user') {
            return $section;
        }

        // We don't know other sections.
        return null;
    }

    /**
     * Returns valid contexts.
     *
     * @return  array  Associative array with contexts as keys and translated strings as values
     *
     * @since   4.0.0
     */
    public function getContexts(): array
    {
        $language = Factory::getApplication()->getLanguage();
        $language->load('com_users', JPATH_ADMINISTRATOR);

        return [
            'com_users.user' => $language->_('COM_USERS'),
        ];
    }
}
