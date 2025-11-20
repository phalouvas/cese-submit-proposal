<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_cesesubmitproposal
 *
 * @copyright   KAINOTOMO PH LTD - All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

namespace KAINOTOMO\Component\Cesesubmitproposal\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Proposal Model
 *
 * @since  1.0.0
 */
class ProposalModel extends BaseDatabaseModel
{
    /**
     * Validate step 1 data (proposal type)
     *
     * @param   array  $data  The form data
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function validateStep1($data)
    {
        if (empty($data['proposal_type'])) {
            return false;
        }

        $validTypes = ['working_group', 'thematically_focused_panel', 'cross_thematic_session'];
        
        return in_array($data['proposal_type'], $validTypes);
    }

    /**
     * Validate step 2 data (form fields)
     *
     * @param   array  $data  The form data
     *
     * @return  array  Array with 'valid' boolean and 'errors' array
     *
     * @since   1.0.0
     */
    public function validateStep2($data)
    {
        $errors = [];
        
        // Check if at least one author is provided
        $hasAuthor = false;
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($data['author' . $i . '_name']) || !empty($data['author' . $i . '_surname']) || 
                !empty($data['author' . $i . '_email'])) {
                $hasAuthor = true;
                
                // If any author field is filled, validate email
                if (!empty($data['author' . $i . '_email']) && !MailHelper::isEmailAddress($data['author' . $i . '_email'])) {
                    $errors[] = Text::sprintf('COM_CESESUBMITPROPOSAL_ERROR_INVALID_EMAIL');
                }
            }
        }
        
        if (!$hasAuthor) {
            $errors[] = Text::_('COM_CESESUBMITPROPOSAL_ERROR_MIN_ONE_AUTHOR');
        }
        
        // Validate abstract title and details
        if (empty($data['abstract1_title'])) {
            $errors[] = Text::_('COM_CESESUBMITPROPOSAL_ERROR_REQUIRED_FIELD');
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Verify reCAPTCHA response
     *
     * @param   string  $response  The reCAPTCHA response token
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function verifyCaptcha($response)
    {
        $params = ComponentHelper::getParams('com_cesesubmitproposal');
        
        if (!$params->get('enable_recaptcha', 1)) {
            return true; // Skip if disabled
        }
        
        $secretKey = $params->get('recaptcha_secret_key');
        
        if (empty($secretKey)) {
            Log::add('reCAPTCHA secret key not configured', Log::WARNING, 'com_cesesubmitproposal');
            return true; // Allow if not configured
        }
        
        if (empty($response)) {
            return false;
        }
        
        $app = Factory::getApplication();
        $remoteIp = $app->input->server->get('REMOTE_ADDR', '', 'string');
        
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => $remoteIp
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($verifyUrl, false, $context);
        
        if ($result === false) {
            Log::add('Failed to verify reCAPTCHA', Log::ERROR, 'com_cesesubmitproposal');
            return false;
        }
        
        $resultJson = json_decode($result);
        
        return isset($resultJson->success) && $resultJson->success === true;
    }

    /**
     * Get or create the Proposals category
     *
     * @return  integer|boolean  Category ID or false on failure
     *
     * @since   1.0.0
     */
    public function getOrCreateCategory()
    {
        $params = ComponentHelper::getParams('com_cesesubmitproposal');
        $categoryId = $params->get('category_id');
        
        // If category is configured, verify it exists
        if ($categoryId) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('id') . ' = ' . (int) $categoryId)
                ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'));
            
            $db->setQuery($query);
            
            if ($db->loadResult()) {
                return $categoryId;
            }
        }
        
        // Create "Proposals" category
        $app = Factory::getApplication();
        $categoryData = [
            'title' => 'Proposals',
            'alias' => 'proposals',
            'extension' => 'com_content',
            'parent_id' => 1,
            'published' => 1,
            'access' => 1,
            'language' => '*',
            'description' => 'Conference proposal submissions'
        ];
        
        try {
            $categoryModel = $app->bootComponent('com_categories')
                ->getMVCFactory()
                ->createModel('Category', 'Administrator', ['ignore_request' => true]);
            
            if ($categoryModel->save($categoryData)) {
                return $categoryModel->getState('category.id');
            }
        } catch (\Exception $e) {
            Log::add('Failed to create Proposals category: ' . $e->getMessage(), Log::ERROR, 'com_cesesubmitproposal');
        }
        
        return false;
    }

    /**
     * Create proposal article
     *
     * @param   array  $data  The complete form data
     *
     * @return  integer|boolean  Article ID or false on failure
     *
     * @since   1.0.0
     */
    public function createProposalArticle($data)
    {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_cesesubmitproposal');
        
        // Get or create category
        $categoryId = $this->getOrCreateCategory();
        if (!$categoryId) {
            return false;
        }
        
        // Format article content
        $content = $this->formatArticleContent($data);
        
        // Generate article title
        $title = $this->generateArticleTitle($data);
        
        // Prepare article data
        $articleData = [
            'catid' => $categoryId,
            'title' => $title,
            'alias' => '', // Auto-generated
            'introtext' => $content,
            'fulltext' => '',
            'state' => $params->get('article_state', 1),
            'access' => 1,
            'language' => '*',
            'created_by' => 0, // Guest
            'publish_up' => Factory::getDate()->toSql(),
            'metadata' => [
                'author' => '',
                'robots' => ''
            ],
            'featured' => 0
        ];
        
        try {
            $articleModel = $app->bootComponent('com_content')
                ->getMVCFactory()
                ->createModel('Article', 'Administrator', ['ignore_request' => true]);
            
            if ($articleModel->save($articleData)) {
                return $articleModel->getState('article.id');
            }
            
            Log::add('Failed to save article: ' . $articleModel->getError(), Log::ERROR, 'com_cesesubmitproposal');
        } catch (\Exception $e) {
            Log::add('Exception creating article: ' . $e->getMessage(), Log::ERROR, 'com_cesesubmitproposal');
        }
        
        return false;
    }

    /**
     * Generate article title
     *
     * @param   array  $data  The form data
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function generateArticleTitle($data)
    {
        $proposalType = ucwords(str_replace('_', ' ', $data['proposal_type']));
        $firstAuthorSurname = !empty($data['author1_surname']) ? $data['author1_surname'] : 'Unknown';
        $abstractTitle = !empty($data['abstract1_title']) ? $data['abstract1_title'] : 'Untitled';
        
        return $proposalType . ' - ' . $firstAuthorSurname . ' - ' . $abstractTitle;
    }

    /**
     * Format article content
     *
     * @param   array  $data  The form data
     *
     * @return  string  HTML formatted content
     *
     * @since   1.0.0
     */
    protected function formatArticleContent($data)
    {
        $html = '<h2>' . Text::_('COM_CESESUBMITPROPOSAL_PROPOSAL_TYPE_LABEL') . ': ' 
                . ucwords(str_replace('_', ' ', $data['proposal_type'])) . '</h2>';
        
        // Add submission type if applicable
        if (!empty($data['submission_type'])) {
            $html .= '<h3>' . Text::_('COM_CESESUBMITPROPOSAL_SUBMISSION_TYPE') . ': ' 
                     . ucfirst($data['submission_type']) . '</h3>';
        }
        
        // Add working group if applicable
        if (!empty($data['working_group'])) {
            $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_WORKING_GROUP_LABEL') . ':</strong> ' 
                     . htmlspecialchars($data['working_group']) . '</p>';
        }
        
        // Add panel/session title and summary if applicable
        if (!empty($data['panel_title'])) {
            $html .= '<h3>' . htmlspecialchars($data['panel_title']) . '</h3>';
            if (!empty($data['panel_summary'])) {
                $html .= '<p>' . nl2br(htmlspecialchars($data['panel_summary'])) . '</p>';
            }
        }
        
        // Process abstracts (1-4 depending on submission type)
        $abstractCount = (!empty($data['submission_type']) && $data['submission_type'] === 'group') ? 4 : 1;
        
        for ($a = 1; $a <= $abstractCount; $a++) {
            if ($abstractCount > 1) {
                $html .= '<h3>' . Text::sprintf('COM_CESESUBMITPROPOSAL_ABSTRACT_N', $a) . '</h3>';
            } else {
                $html .= '<h3>' . Text::_('COM_CESESUBMITPROPOSAL_ABSTRACT_TITLE') . '</h3>';
            }
            
            // Authors for this abstract
            $html .= '<h4>' . Text::_('COM_CESESUBMITPROPOSAL_AUTHOR_LABEL') . '</h4>';
            
            for ($i = 1; $i <= 4; $i++) {
                $nameKey = ($abstractCount > 1) ? 'abstract' . $a . '_author' . $i . '_name' : 'author' . $i . '_name';
                $surnameKey = ($abstractCount > 1) ? 'abstract' . $a . '_author' . $i . '_surname' : 'author' . $i . '_surname';
                $emailKey = ($abstractCount > 1) ? 'abstract' . $a . '_author' . $i . '_email' : 'author' . $i . '_email';
                $affiliationKey = ($abstractCount > 1) ? 'abstract' . $a . '_author' . $i . '_affiliation' : 'author' . $i . '_affiliation';
                
                if (!empty($data[$nameKey]) || !empty($data[$surnameKey])) {
                    $html .= '<p><strong>' . Text::sprintf('COM_CESESUBMITPROPOSAL_AUTHOR_LABEL', $i) . ':</strong> ';
                    $html .= htmlspecialchars($data[$nameKey] ?? '') . ' ' . htmlspecialchars($data[$surnameKey] ?? '') . '<br>';
                    
                    if (!empty($data[$emailKey])) {
                        $html .= Text::_('COM_CESESUBMITPROPOSAL_EMAIL_LABEL') . ': ' . htmlspecialchars($data[$emailKey]) . '<br>';
                    }
                    
                    if (!empty($data[$affiliationKey])) {
                        $html .= Text::_('COM_CESESUBMITPROPOSAL_AFFILIATION_LABEL') . ': ' . htmlspecialchars($data[$affiliationKey]);
                    }
                    
                    $html .= '</p>';
                }
            }
            
            // Abstract details
            $titleKey = ($abstractCount > 1) ? 'abstract' . $a . '_title' : 'abstract1_title';
            $detailsKey = ($abstractCount > 1) ? 'abstract' . $a . '_details' : 'abstract1_details';
            
            if (!empty($data[$titleKey])) {
                $html .= '<h4>' . htmlspecialchars($data[$titleKey]) . '</h4>';
            }
            
            if (!empty($data[$detailsKey])) {
                $html .= '<p>' . nl2br(htmlspecialchars($data[$detailsKey])) . '</p>';
            }
        }
        
        return $html;
    }

    /**
     * Send admin notification email
     *
     * @param   array    $data       The form data
     * @param   integer  $articleId  The created article ID
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function sendAdminNotificationEmail($data, $articleId)
    {
        $params = ComponentHelper::getParams('com_cesesubmitproposal');
        
        if (!$params->get('enable_notifications', 1)) {
            return true;
        }
        
        $adminEmail = $params->get('admin_email');
        if (empty($adminEmail)) {
            return true;
        }
        
        $app = Factory::getApplication();
        $config = $app->get('config');
        $siteName = $config->get('sitename');
        
        $mailer = Factory::getMailer();
        
        // Subject
        $title = $this->generateArticleTitle($data);
        $subjectPrefix = $params->get('email_subject_prefix', 'New Proposal Submission');
        $subject = $subjectPrefix . ': ' . $title;
        
        // Body
        $body = $this->formatAdminEmailBody($data, $articleId, $siteName);
        
        // Set email properties
        $mailer->setSender([$config->get('mailfrom'), $config->get('fromname')]);
        $mailer->addRecipient($adminEmail);
        $mailer->setSubject($subject);
        $mailer->isHtml(true);
        $mailer->setBody($body);
        
        try {
            $sent = $mailer->send();
            if (!$sent) {
                Log::add('Failed to send admin notification email', Log::WARNING, 'com_cesesubmitproposal');
            }
            return $sent;
        } catch (\Exception $e) {
            Log::add('Email error: ' . $e->getMessage(), Log::ERROR, 'com_cesesubmitproposal');
            return false;
        }
    }

    /**
     * Send confirmation email to submitter
     *
     * @param   array    $data       The form data
     * @param   integer  $articleId  The created article ID
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function sendConfirmationEmail($data, $articleId)
    {
        $params = ComponentHelper::getParams('com_cesesubmitproposal');
        
        if (!$params->get('enable_notifications', 1) || !$params->get('enable_confirmation_email', 1)) {
            return true;
        }
        
        $authorEmail = $data['author1_email'] ?? '';
        if (empty($authorEmail)) {
            return true;
        }
        
        $app = Factory::getApplication();
        $config = $app->get('config');
        $siteName = $config->get('sitename');
        
        $mailer = Factory::getMailer();
        
        // Subject
        $subject = Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_SUBJECT');
        
        // Body
        $body = $this->formatConfirmationEmailBody($data, $articleId, $siteName);
        
        // Set email properties
        $mailer->setSender([$config->get('mailfrom'), $config->get('fromname')]);
        $mailer->addRecipient($authorEmail);
        $mailer->setSubject($subject);
        $mailer->isHtml(true);
        $mailer->setBody($body);
        
        try {
            $sent = $mailer->send();
            if (!$sent) {
                Log::add('Failed to send confirmation email', Log::WARNING, 'com_cesesubmitproposal');
            }
            return $sent;
        } catch (\Exception $e) {
            Log::add('Confirmation email error: ' . $e->getMessage(), Log::ERROR, 'com_cesesubmitproposal');
            return false;
        }
    }

    /**
     * Format admin email body
     *
     * @param   array    $data       The form data
     * @param   integer  $articleId  The article ID
     * @param   string   $siteName   The site name
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function formatAdminEmailBody($data, $articleId, $siteName)
    {
        $html = '<h1>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_ADMIN_GREETING') . '</h1>';
        
        $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_ADMIN_SUBMISSION_DATE') . ':</strong> ' 
                 . Factory::getDate()->format('Y-m-d H:i:s') . '</p>';
        
        $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_ADMIN_ARTICLE_ID') . ':</strong> ' 
                 . $articleId . '</p>';
        
        // Backend link
        $backendUrl = Uri::root() . 'administrator/index.php?option=com_content&task=article.edit&id=' . $articleId;
        $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_ADMIN_BACKEND_LINK') . ':</strong> ' 
                 . '<a href="' . $backendUrl . '">' . $backendUrl . '</a></p>';
        
        $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_ADMIN_SUBMITTER_EMAIL') . ':</strong> ' 
                 . htmlspecialchars($data['author1_email'] ?? 'N/A') . '</p>';
        
        $html .= '<hr>';
        
        // Article content
        $html .= $this->formatArticleContent($data);
        
        $html .= '<hr>';
        $html .= '<p><em>' . Text::sprintf('COM_CESESUBMITPROPOSAL_EMAIL_ADMIN_FOOTER', $siteName) . '</em></p>';
        
        return $html;
    }

    /**
     * Format confirmation email body
     *
     * @param   array    $data       The form data
     * @param   integer  $articleId  The article ID
     * @param   string   $siteName   The site name
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function formatConfirmationEmailBody($data, $articleId, $siteName)
    {
        $html = '<h1>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_GREETING') . '</h1>';
        
        $html .= '<p>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_MESSAGE') . '</p>';
        
        $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_CONFIRMATION_NUMBER') . ':</strong> ' 
                 . $articleId . '</p>';
        
        $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_PROPOSAL_TYPE') . ':</strong> ' 
                 . ucwords(str_replace('_', ' ', $data['proposal_type'])) . '</p>';
        
        if (!empty($data['abstract1_title'])) {
            $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_TITLE') . ':</strong> ' 
                     . htmlspecialchars($data['abstract1_title']) . '</p>';
        }
        
        $html .= '<h3>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_NEXT_STEPS') . '</h3>';
        $html .= '<p>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_NEXT_STEPS_TEXT') . '</p>';
        
        $html .= '<hr>';
        
        $config = Factory::getApplication()->get('config');
        $contactEmail = $config->get('mailfrom');
        $html .= '<p><em>' . Text::sprintf('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_FOOTER', $contactEmail) . '</em></p>';
        
        return $html;
    }
}
