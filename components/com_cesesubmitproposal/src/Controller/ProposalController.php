<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_cesesubmitproposal
 *
 * @copyright   KAINOTOMO PH LTD - All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

namespace KAINOTOMO\Component\Cesesubmitproposal\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Proposal Controller
 *
 * @since  1.0.0
 */
class ProposalController extends BaseController
{
    /**
     * Save step 1 data and redirect to step 2
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function saveStep1()
    {
        // Check for request forgeries
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = $this->app;
        $input = $app->input;
        
        // Get form data
        $data = [
            'proposal_type' => $input->post->getString('proposal_type', '')
        ];
        
        // Get model
        $model = $this->getModel('Proposal');
        
        // Validate step 1
        if (!$model->validateStep1($data)) {
            $app->enqueueMessage(Text::_('COM_CESESUBMITPROPOSAL_ERROR_VALIDATION_FAILED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main', false));
            return;
        }
        
        // Store in session
        $app->setUserState('com_cesesubmitproposal.step1', $data);
        
        // Redirect to step 2
        $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=2', false));
    }

    /**
     * Save step 2 data and redirect to step 3
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function saveStep2()
    {
        // Check for request forgeries
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = $this->app;
        $input = $app->input;
        
        // Check if step 1 is completed
        $step1Data = $app->getUserState('com_cesesubmitproposal.step1');
        if (empty($step1Data)) {
            $app->enqueueMessage(Text::_('COM_CESESUBMITPROPOSAL_ERROR_NO_STEP1_DATA'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main', false));
            return;
        }
        
        // Get all form data
        $data = $input->post->get('jform', [], 'array');
        
        // Get model
        $model = $this->getModel('Proposal');
        
        // Validate step 2
        $validation = $model->validateStep2($data);
        if (!$validation['valid']) {
            foreach ($validation['errors'] as $error) {
                $app->enqueueMessage($error, 'error');
            }
            $app->setUserState('com_cesesubmitproposal.step2', $data); // Save for repopulation
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=2', false));
            return;
        }
        
        // Store in session
        $app->setUserState('com_cesesubmitproposal.step2', $data);
        
        // Redirect to step 3
        $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=3', false));
    }

    /**
     * Submit the complete proposal
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function submit()
    {
        // Check for request forgeries
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = $this->app;
        $input = $app->input;
        
        // Check if previous steps are completed
        $step1Data = $app->getUserState('com_cesesubmitproposal.step1');
        $step2Data = $app->getUserState('com_cesesubmitproposal.step2');
        
        if (empty($step1Data)) {
            $app->enqueueMessage(Text::_('COM_CESESUBMITPROPOSAL_ERROR_NO_STEP1_DATA'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main', false));
            return;
        }
        
        if (empty($step2Data)) {
            $app->enqueueMessage(Text::_('COM_CESESUBMITPROPOSAL_ERROR_NO_STEP2_DATA'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=2', false));
            return;
        }
        
        // Get model
        $model = $this->getModel('Proposal');
        
        // Verify spam protection (honeypot + time-based)
        $spamData = [
            'website' => $input->post->getString('website', ''),
            'form_start_time' => $input->post->getInt('form_start_time', 0)
        ];
        
        if (!$model->verifySpamProtection($spamData)) {
            $app->enqueueMessage(Text::_('COM_CESESUBMITPROPOSAL_ERROR_SPAM_DETECTED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=3', false));
            return;
        }
        
        // Merge all data
        $completeData = array_merge($step1Data, $step2Data);
        
        // Create article
        $articleId = $model->createProposalArticle($completeData);
        
        if (!$articleId) {
            $app->enqueueMessage(Text::_('COM_CESESUBMITPROPOSAL_ERROR_SAVE_FAILED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=3', false));
            return;
        }
        
        // Send emails
        $adminEmailSent = $model->sendAdminNotificationEmail($completeData, $articleId);
        $confirmEmailSent = $model->sendConfirmationEmail($completeData, $articleId);
        
        if (!$adminEmailSent || !$confirmEmailSent) {
            $app->enqueueMessage(Text::_('COM_CESESUBMITPROPOSAL_ERROR_EMAIL_FAILED'), 'warning');
        }
        
        // Clear session data
        $app->setUserState('com_cesesubmitproposal.step1', null);
        $app->setUserState('com_cesesubmitproposal.step2', null);
        
        // Store success data for display
        $app->setUserState('com_cesesubmitproposal.success', [
            'article_id' => $articleId,
            'author_email' => $completeData['author1_email'] ?? ''
        ]);
        
        // Redirect to success page
        $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=success', false));
    }

    /**
     * Navigate back to previous step
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function back()
    {
        $app = $this->app;
        $input = $app->input;
        
        $currentStep = $input->getInt('step', 1);
        $previousStep = max(1, $currentStep - 1);
        
        if ($previousStep == 1) {
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main', false));
        } else {
            $this->setRedirect(Route::_('index.php?option=com_cesesubmitproposal&view=main&step=' . $previousStep, false));
        }
    }
}
