<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_cesesubmitproposal
 *
 * @copyright   KAINOTOMO PH LTD - All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$step1Data = $this->getStep1Data();
$step2Data = $this->getStep2Data();
$params = $this->getParams();

// Get proposal type display name
$proposalTypeMap = [
    'working_group' => Text::_('COM_CESESUBMITPROPOSAL_PROPOSAL_TYPE_WORKING_GROUP'),
    'thematically_focused_panel' => Text::_('COM_CESESUBMITPROPOSAL_PROPOSAL_TYPE_PANEL'),
    'cross_thematic_session' => Text::_('COM_CESESUBMITPROPOSAL_PROPOSAL_TYPE_SESSION')
];

$proposalType = $proposalTypeMap[$step1Data['proposal_type']] ?? '';
$submissionType = ucfirst($step2Data['submission_type'] ?? 'individual');

// reCAPTCHA settings
$recaptchaEnabled = $params->get('enable_recaptcha', 1);
$recaptchaSiteKey = $params->get('recaptcha_site_key', '');
?>
<div class="cesesubmitproposal">
    <div class="row">
        <div class="col-12">
            <h1><?php echo Text::_('COM_CESESUBMITPROPOSAL_SUMMARY_HEADING'); ?></h1>
            
            <p class="alert alert-warning"><?php echo Text::_('COM_CESESUBMITPROPOSAL_SUMMARY_SUBMIT_INFO'); ?></p>
            
            <div class="summary-info card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><strong><?php echo Text::_('COM_CESESUBMITPROPOSAL_GROUP_TYPE'); ?>:</strong></div>
                        <div class="col-md-8"><?php echo $proposalType; ?></div>
                    </div>
                    
                    <?php if (!empty($step2Data['submission_type'])) : ?>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong><?php echo Text::_('COM_CESESUBMITPROPOSAL_SUBMISSION_TYPE'); ?>:</strong></div>
                            <div class="col-md-8"><?php echo $submissionType; ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($step2Data['working_group'])) : ?>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong><?php echo Text::_('COM_CESESUBMITPROPOSAL_WORKING_GROUP_LABEL'); ?>:</strong></div>
                            <div class="col-md-8">
                                <?php
                                $wgMap = [
                                    'wg1' => Text::_('COM_CESESUBMITPROPOSAL_WG1'),
                                    'wg2' => Text::_('COM_CESESUBMITPROPOSAL_WG2'),
                                    'wg3' => Text::_('COM_CESESUBMITPROPOSAL_WG3'),
                                    'wg4' => Text::_('COM_CESESUBMITPROPOSAL_WG4'),
                                    'wg5' => Text::_('COM_CESESUBMITPROPOSAL_WG5'),
                                    'wg_new' => Text::_('COM_CESESUBMITPROPOSAL_WG_NEW_SCHOLARS')
                                ];
                                echo $wgMap[$step2Data['working_group']] ?? $step2Data['working_group'];
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($step2Data['panel_title'])) : ?>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong><?php echo Text::_('COM_CESESUBMITPROPOSAL_TITLE_LABEL'); ?>:</strong></div>
                            <div class="col-md-8"><?php echo htmlspecialchars($step2Data['panel_title']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-4"><strong><?php echo Text::_('COM_CESESUBMITPROPOSAL_ABSTRACT_TITLE_LABEL'); ?>:</strong></div>
                        <div class="col-md-8"><?php echo htmlspecialchars($step2Data['abstract1_title'] ?? ''); ?></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4"><strong><?php echo Text::_('COM_CESESUBMITPROPOSAL_AUTHOR_LABEL'); ?> 1:</strong></div>
                        <div class="col-md-8">
                            <?php echo htmlspecialchars($step2Data['author1_name'] ?? '') . ' ' . htmlspecialchars($step2Data['author1_surname'] ?? ''); ?><br>
                            <?php if (!empty($step2Data['author1_email'])) : ?>
                                <?php echo htmlspecialchars($step2Data['author1_email']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="<?php echo Route::_('index.php?option=com_cesesubmitproposal&task=proposal.submit'); ?>" method="post" name="proposalForm" id="proposalForm">
                
                <?php if ($recaptchaEnabled && !empty($recaptchaSiteKey)) : ?>
                    <div class="form-group mb-4">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>
                    </div>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo Route::_('index.php?option=com_cesesubmitproposal&view=main&step=2'); ?>'">
                        <?php echo Text::_('COM_CESESUBMITPROPOSAL_BTN_BACK'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo Text::_('COM_CESESUBMITPROPOSAL_BTN_SUBMIT'); ?>
                    </button>
                </div>
                
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>
