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

namespace KAINOTOMO\Component\Cesesubmitproposal\Site\View\Main;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * View class for cesesubmitproposal component.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The current step
     *
     * @var    integer
     * @since  1.0.0
     */
    protected $step = 1;

    /**
     * Step 1 data
     *
     * @var    array
     * @since  1.0.0
     */
    protected $step1Data = [];

    /**
     * Step 2 data
     *
     * @var    array
     * @since  1.0.0
     */
    protected $step2Data = [];

    /**
     * Component parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * Success data
     *
     * @var    array
     * @since  1.0.0
     */
    protected $successData = [];

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $document = $app->getDocument();
        
        // Load CSS and JS
        $document->getWebAssetManager()
            ->registerAndUseStyle('com_cesesubmitproposal', 'media/com_cesesubmitproposal/css/proposal-form.css')
            ->registerAndUseScript('com_cesesubmitproposal', 'media/com_cesesubmitproposal/js/proposal-form.js');
        
        // Get current step from URL
        $this->step = $input->getInt('step', 1);
        
        // Get session data
        $this->step1Data = $app->getUserState('com_cesesubmitproposal.step1', []);
        $this->step2Data = $app->getUserState('com_cesesubmitproposal.step2', []);
        $this->successData = $app->getUserState('com_cesesubmitproposal.success', []);
        
        // Get component parameters
        $this->params = ComponentHelper::getParams('com_cesesubmitproposal');
        
        // Validate step access
        if ($this->step == 2 && empty($this->step1Data)) {
            // Redirect to step 1 if step 1 data is missing
            $app->redirect('index.php?option=com_cesesubmitproposal&view=main');
            return;
        }
        
        if ($this->step == 3 && (empty($this->step1Data) || empty($this->step2Data))) {
            // Redirect to appropriate step
            if (empty($this->step1Data)) {
                $app->redirect('index.php?option=com_cesesubmitproposal&view=main');
            } else {
                $app->redirect('index.php?option=com_cesesubmitproposal&view=main&step=2');
            }
            return;
        }
        
        parent::display($tpl);
    }

    /**
     * Get the current step
     *
     * @return  integer
     *
     * @since   1.0.0
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Get step 1 data
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getStep1Data()
    {
        return $this->step1Data;
    }

    /**
     * Get step 2 data
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getStep2Data()
    {
        return $this->step2Data;
    }

    /**
     * Get component parameters
     *
     * @return  \Joomla\Registry\Registry
     *
     * @since   1.0.0
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get success data
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getSuccessData()
    {
        return $this->successData;
    }
}
