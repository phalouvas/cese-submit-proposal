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

// Working group dropdown
$workingGroups = [
    '' => Text::_('COM_CESESUBMITPROPOSAL_WORKING_GROUP_LABEL'),
    'wg1' => Text::_('COM_CESESUBMITPROPOSAL_WG1'),
    'wg2' => Text::_('COM_CESESUBMITPROPOSAL_WG2'),
    'wg3' => Text::_('COM_CESESUBMITPROPOSAL_WG3'),
    'wg4' => Text::_('COM_CESESUBMITPROPOSAL_WG4'),
    'wg5' => Text::_('COM_CESESUBMITPROPOSAL_WG5'),
    'wg_new' => Text::_('COM_CESESUBMITPROPOSAL_WG6')
];
?>
<div class="working-group-selection mb-4">
    <select name="jform[working_group]" id="working_group" class="form-select" required>
        <?php foreach ($workingGroups as $value => $label) : ?>
            <option value="<?php echo $value; ?>" 
                    <?php echo (($step2Data['working_group'] ?? '') === $value) ? 'selected' : ''; ?>
                    <?php echo ($value === '') ? 'disabled' : ''; ?>>
                <?php echo $label; ?>
            </option>
        <?php endforeach; ?>
    </select>
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
