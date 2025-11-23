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

// Get the step from the view
$step = $this->getStep();
$step1Data = $this->getStep1Data();

// Load different templates based on step
if ($step == 2) {
    echo $this->loadTemplate('step2');
    return;
} elseif ($step == 3) {
    echo $this->loadTemplate('step3');
    return;
} elseif ($step === 'success' || $step == 4) {
    echo $this->loadTemplate('success');
    return;
}

// Step 1 - Proposal Type Selection
?>
<div class="cesesubmitproposal">
    <div class="row">
        <div class="col-12">
            <h1 class="text-danger"><?php echo Text::_('COM_CESESUBMITPROPOSAL_STEP1_TITLE'); ?></h1>
            
            <form action="<?php echo Route::_('index.php?option=com_cesesubmitproposal&task=proposal.saveStep1'); ?>" method="post" name="proposalForm" id="proposalForm">
                
                <div class="proposal-type-selection mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="proposal_type" id="type_working_group" value="working_group" checked>
                        <label class="form-check-label" for="type_working_group">
                            <?php echo Text::_('COM_CESESUBMITPROPOSAL_PROPOSAL_TYPE_WORKING_GROUP'); ?>
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="proposal_type" id="type_panel" value="thematically_focused_panel">
                        <label class="form-check-label" for="type_panel">
                            <?php echo Text::_('COM_CESESUBMITPROPOSAL_PROPOSAL_TYPE_PANEL'); ?>
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="proposal_type" id="type_session" value="cross_thematic_session">
                        <label class="form-check-label" for="type_session">
                            <?php echo Text::_('COM_CESESUBMITPROPOSAL_PROPOSAL_TYPE_SESSION'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo Text::_('COM_CESESUBMITPROPOSAL_BTN_NEXT'); ?>
                    </button>
                </div>
                
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>
