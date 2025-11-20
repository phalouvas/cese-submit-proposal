<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_cesesubmitproposal
 *
 * @copyright   KAINOTOMO PH LTD - All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use KAINOTOMO\Component\Cesesubmitproposal\Administrator\Extension\CesesubmitproposalComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The cesesubmitproposal service provider.
 *
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory('\\KAINOTOMO\\Component\\Cesesubmitproposal'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\KAINOTOMO\\Component\\Cesesubmitproposal'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new CesesubmitproposalComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
