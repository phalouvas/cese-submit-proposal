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
$proposalType = $step1Data['proposal_type'] ?? 'working_group';
?>
<div class="cesesubmitproposal">
    <div class="row">
        <div class="col-12">
            <p class="alert alert-info"><?php echo Text::_('COM_CESESUBMITPROPOSAL_AUTHORS_INFO'); ?></p>
            
            <form action="<?php echo Route::_('index.php?option=com_cesesubmitproposal&task=proposal.saveStep2'); ?>" method="post" name="proposalForm" id="proposalForm" class="form-validate">
                
                <?php
                // Load appropriate template based on proposal type
                if ($proposalType === 'working_group') {
                    echo $this->loadTemplate('workinggroup');
                } elseif ($proposalType === 'thematically_focused_panel') {
                    // Check submission type from saved data or show individual by default
                    $submissionType = $step2Data['submission_type'] ?? 'individual';
                    if ($submissionType === 'group') {
                        echo $this->loadTemplate('panel_group');
                    } else {
                        echo $this->loadTemplate('panel_individual');
                    }
                } elseif ($proposalType === 'cross_thematic_session') {
                    $submissionType = $step2Data['submission_type'] ?? 'individual';
                    if ($submissionType === 'group') {
                        echo $this->loadTemplate('session_group');
                    } else {
                        echo $this->loadTemplate('session_individual');
                    }
                }
                ?>
                
                <div class="form-actions mt-4">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo Route::_('index.php?option=com_cesesubmitproposal&view=main'); ?>'">
                        <?php echo Text::_('COM_CESESUBMITPROPOSAL_BTN_BACK'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo Text::_('COM_CESESUBMITPROPOSAL_BTN_NEXT'); ?>
                    </button>
                </div>
                
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>

<script>
// Handle submission type radio change for panel/session
document.addEventListener('DOMContentLoaded', function() {
    const submissionTypeRadios = document.querySelectorAll('input[name="jform[submission_type]"]');
    submissionTypeRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Reload the page with the selected submission type
            const form = document.getElementById('proposalForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reload';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        });
    });
});
</script>
