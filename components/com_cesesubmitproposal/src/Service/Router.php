<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_privacy
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace KAINOTOMO\Component\Cesesubmitproposal\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

/**
 * Routing class from com_privacy
 *
 * @since  3.9.0
 */
class Router extends RouterView
{
	/**
	 * Privacy Component router constructor
	 *
	 * @param   CMSApplication  $app   The application object
	 * @param   AbstractMenu    $menu  The menu object to work with
	 *
	 * @since   3.9.0
	 */
	public function __construct(SiteApplication $app, AbstractMenu $menu)
	{
		$this->registerView(new RouterViewConfiguration('transactions'));

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}
}
