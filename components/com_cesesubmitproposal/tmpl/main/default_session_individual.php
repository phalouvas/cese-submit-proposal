<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_cesesubmitproposal
 *
 * @copyright   KAINOTOMO PH LTD - All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$step2Data = $this->getStep2Data();
?>
<!-- Submission Type -->
<div class="submission-type-selection mb-4">
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="jform[submission_type]" id="submission_individual" value="individual" checked>
        <label class="form-check-label" for="submission_individual">
            <?php echo Text::_('COM_CESESUBMITPROPOSAL_SUBMISSION_TYPE_INDIVIDUAL'); ?>
        </label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="jform[submission_type]" id="submission_group" value="group">
        <label class="form-check-label" for="submission_group">
            <?php echo Text::_('COM_CESESUBMITPROPOSAL_SUBMISSION_TYPE_GROUP'); ?>
        </label>
    </div>
</div>

<h3><?php echo Text::_('COM_CESESUBMITPROPOSAL_ABSTRACT_TITLE'); ?></h3>

<!-- Authors 1-4 -->
<?php for ($i = 1; $i <= 4; $i++) : ?>
    <div class="author-section mb-4">
        <h4><?php echo Text::sprintf('COM_CESESUBMITPROPOSAL_AUTHOR_LABEL', $i); ?></h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="text" name="jform[author<?php echo $i; ?>_name]" 
                       class="form-control" 
                       placeholder="<?php echo Text::_('COM_CESESUBMITPROPOSAL_NAME_PLACEHOLDER'); ?>"
                       value="<?php echo htmlspecialchars($step2Data['author' . $i . '_name'] ?? ''); ?>"
                       <?php echo ($i === 1) ? 'required' : ''; ?>>
            </div>
            <div class="col-md-6 mb-3">
                <input type="text" name="jform[author<?php echo $i; ?>_surname]" 
                       class="form-control" 
                       placeholder="<?php echo Text::_('COM_CESESUBMITPROPOSAL_SURNAME_PLACEHOLDER'); ?>"
                       value="<?php echo htmlspecialchars($step2Data['author' . $i . '_surname'] ?? ''); ?>"
                       <?php echo ($i === 1) ? 'required' : ''; ?>>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="email" name="jform[author<?php echo $i; ?>_email]" 
                       class="form-control" 
                       placeholder="<?php echo Text::_('COM_CESESUBMITPROPOSAL_EMAIL_PLACEHOLDER'); ?>"
                       value="<?php echo htmlspecialchars($step2Data['author' . $i . '_email'] ?? ''); ?>"
                       <?php echo ($i === 1) ? 'required' : ''; ?>>
            </div>
            <div class="col-md-6 mb-3">
                <input type="text" name="jform[author<?php echo $i; ?>_affiliation]" 
                       class="form-control" 
                       placeholder="<?php echo Text::_('COM_CESESUBMITPROPOSAL_AFFILIATION_PLACEHOLDER'); ?>"
                       value="<?php echo htmlspecialchars($step2Data['author' . $i . '_affiliation'] ?? ''); ?>">
            </div>
        </div>
    </div>
<?php endfor; ?>

<h3><?php echo Text::_('COM_CESESUBMITPROPOSAL_DETAILS_TITLE'); ?></h3>

<div class="abstract-details mb-4">
    <div class="mb-3">
        <input type="text" name="jform[abstract1_title]" 
               class="form-control" 
               placeholder="<?php echo Text::_('COM_CESESUBMITPROPOSAL_ABSTRACT_TITLE_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($step2Data['abstract1_title'] ?? ''); ?>"
               required>
    </div>
    <div class="mb-3">
        <textarea name="jform[abstract1_details]" 
                  class="form-control" 
                  rows="10"
                  placeholder="<?php echo Text::_('COM_CESESUBMITPROPOSAL_ABSTRACT_DETAILS_PLACEHOLDER'); ?>"
                  required><?php echo htmlspecialchars($step2Data['abstract1_details'] ?? ''); ?></textarea>
    </div>
</div>
