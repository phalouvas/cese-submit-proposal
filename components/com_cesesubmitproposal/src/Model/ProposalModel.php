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
        
        // Determine submission type
        $submissionType = $data['submission_type'] ?? 'individual';
        
        // Check if at least one author is provided
        $hasAuthor = false;
        
        if ($submissionType === 'group') {
            // For group submissions (panel/session), check abstract1_author1
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($data['abstract1_author' . $i . '_name']) || 
                    !empty($data['abstract1_author' . $i . '_surname']) || 
                    !empty($data['abstract1_author' . $i . '_email'])) {
                    $hasAuthor = true;
                    
                    // If any author field is filled, validate email
                    if (!empty($data['abstract1_author' . $i . '_email']) && 
                        !MailHelper::isEmailAddress($data['abstract1_author' . $i . '_email'])) {
                        $errors[] = Text::sprintf('COM_CESESUBMITPROPOSAL_ERROR_INVALID_EMAIL');
                    }
                }
            }
        } else {
            // For individual submissions, check author1
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($data['author' . $i . '_name']) || 
                    !empty($data['author' . $i . '_surname']) || 
                    !empty($data['author' . $i . '_email'])) {
                    $hasAuthor = true;
                    
                    // If any author field is filled, validate email
                    if (!empty($data['author' . $i . '_email']) && 
                        !MailHelper::isEmailAddress($data['author' . $i . '_email'])) {
                        $errors[] = Text::sprintf('COM_CESESUBMITPROPOSAL_ERROR_INVALID_EMAIL');
                    }
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
     * Verify spam protection (honeypot + time-based)
     *
     * @param   array  $data  The form data including honeypot and timestamp
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function verifySpamProtection($data)
    {
        // Check honeypot field - if filled, it's a bot
        if (!empty($data['website'])) {
            Log::add('Spam detected: Honeypot field filled', Log::WARNING, 'com_cesesubmitproposal');
            return false;
        }
        
        // Check time-based protection
        $params = ComponentHelper::getParams('com_cesesubmitproposal');
        $minTime = $params->get('min_submission_time', 3); // Default 3 seconds
        
        if (empty($data['form_start_time'])) {
            Log::add('Spam detected: Missing form start time', Log::WARNING, 'com_cesesubmitproposal');
            return false;
        }
        
        $startTime = (int) $data['form_start_time'];
        $currentTime = time();
        $elapsedTime = $currentTime - $startTime;
        
        // Check if form was submitted too quickly (bot behavior)
        if ($elapsedTime < $minTime) {
            Log::add(sprintf('Spam detected: Form submitted too quickly (%d seconds)', $elapsedTime), Log::WARNING, 'com_cesesubmitproposal');
            return false;
        }
        
        // Check if timestamp is too old (more than 24 hours)
        if ($elapsedTime > 86400) {
            Log::add('Spam detected: Form timestamp too old', Log::WARNING, 'com_cesesubmitproposal');
            return false;
        }
        
        return true;
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
        
        // Check if "Proposals" category already exists
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('title') . ' = ' . $db->quote('Proposals'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'));
        
        $db->setQuery($query);
        $existingId = $db->loadResult();
        
        if ($existingId) {
            Log::add('Using existing Proposals category: ' . $existingId, Log::INFO, 'com_cesesubmitproposal');
            return $existingId;
        }
        
        // If no category exists, admin must configure one
        // Return the Uncategorised category (id=2) as fallback
        Log::add('No Proposals category found. Please configure category in component options or create a "Proposals" category. Using Uncategorised as fallback.', Log::WARNING, 'com_cesesubmitproposal');
        return 2; // Uncategorised category
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
        
        // Get a valid user ID (use first Super User as creator)
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('u.id')
            ->from($db->quoteName('#__users', 'u'))
            ->join('LEFT', $db->quoteName('#__user_usergroup_map', 'ug') . ' ON ug.user_id = u.id')
            ->where('ug.group_id = 8') // Super Users group
            ->setLimit(1);
        $db->setQuery($query);
        $creatorId = (int) $db->loadResult();
        
        // Fallback to any user if no super user found
        if (!$creatorId) {
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('block') . ' = 0')
                ->setLimit(1);
            $db->setQuery($query);
            $creatorId = (int) $db->loadResult();
        }
        
        if (!$creatorId) {
            return false;
        }
        
        // Generate unique alias
        $baseAlias = \Joomla\CMS\Filter\OutputFilter::stringURLSafe($title);
        $alias = $baseAlias . '-' . time();
        
        // Prepare article data for Joomla model
        $articleData = [
            'id' => 0,
            'catid' => $categoryId,
            'title' => $title,
            'alias' => $alias,
            'introtext' => $content,
            'fulltext' => '',
            'state' => $params->get('article_state', 1),
            'access' => 1,
            'language' => '*',
            'created_by' => $creatorId,
            'publish_up' => Factory::getDate()->toSql(),
            'publish_down' => '',
            'images' => '{}',
            'urls' => '{}',
            'attribs' => '{}',
            'metadata' => ['robots' => '', 'author' => '', 'rights' => ''],
            'metakey' => '',
            'metadesc' => '',
            'featured' => 0
        ];
        
        try {
            // Use Joomla article model
            $articleModel = $app->bootComponent('com_content')
                ->getMVCFactory()
                ->createModel('Article', 'Administrator', ['ignore_request' => true]);
            
            if ($articleModel->save($articleData)) {
                $articleId = $articleModel->getState('article.id');
                return $articleId;
            }
            
            // Get error messages
            $errors = $articleModel->getErrors();
            foreach ($errors as $error) {
                if ($error instanceof \Exception) {
                    Log::add('Article save error: ' . $error->getMessage(), Log::ERROR, 'com_cesesubmitproposal');
                } else {
                    Log::add('Article save error: ' . $error, Log::ERROR, 'com_cesesubmitproposal');
                }
            }
            return false;
        } catch (\Exception $e) {
            Log::add('Exception creating article: ' . $e->getMessage(), Log::ERROR, 'com_cesesubmitproposal');
            return false;
        }
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
        
        // Determine first author surname based on submission type
        $submissionType = $data['submission_type'] ?? 'individual';
        if ($submissionType === 'group') {
            // For group submissions, get author from abstract1_author1_surname
            $firstAuthorSurname = !empty($data['abstract1_author1_surname']) ? $data['abstract1_author1_surname'] : 'Unknown';
        } else {
            // For individual submissions, get author from author1_surname
            $firstAuthorSurname = !empty($data['author1_surname']) ? $data['author1_surname'] : 'Unknown';
        }
        
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
        $config = $app->getConfig();
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
        // Determine correct first author email based on submission type
        $submissionType = $data['submission_type'] ?? 'individual';
        if ($submissionType === 'group') {
            $authorEmail = $data['abstract1_author1_email'] ?? '';
        } else {
            $authorEmail = $data['author1_email'] ?? '';
        }
        if (empty($authorEmail)) {
            return true;
        }
        
        $app = Factory::getApplication();
        $config = $app->getConfig();
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
        
        // First author email depends on submission type
        $submissionType = $data['submission_type'] ?? 'individual';
        $firstAuthorEmail = ($submissionType === 'group') ? ($data['abstract1_author1_email'] ?? 'N/A') : ($data['author1_email'] ?? 'N/A');
        $html .= '<p><strong>' . Text::_('COM_CESESUBMITPROPOSAL_EMAIL_ADMIN_SUBMITTER_EMAIL') . ':</strong> ' 
             . htmlspecialchars($firstAuthorEmail) . '</p>';
        
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
        
        $config = Factory::getApplication()->getConfig();
        $contactEmail = $config->get('mailfrom');
        $html .= '<p><em>' . Text::sprintf('COM_CESESUBMITPROPOSAL_EMAIL_CONFIRM_FOOTER', $contactEmail) . '</em></p>';
        
        return $html;
    }
}
