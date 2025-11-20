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
use Joomla\CMS\Router\Route;

$successData = $this->getSuccessData();
$articleId = $successData['article_id'] ?? 0;
$authorEmail = $successData['author_email'] ?? '';
?>
<div class="cesesubmitproposal">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success">
                <h1 class="alert-heading"><?php echo Text::_('COM_CESESUBMITPROPOSAL_SUCCESS_TITLE'); ?></h1>
                
                <?php if (!empty($authorEmail)) : ?>
                    <p><?php echo Text::sprintf('COM_CESESUBMITPROPOSAL_SUCCESS_MESSAGE', htmlspecialchars($authorEmail)); ?></p>
                <?php endif; ?>
                
                <?php if ($articleId > 0) : ?>
                    <p><?php echo Text::sprintf('COM_CESESUBMITPROPOSAL_SUCCESS_ARTICLE_ID', $articleId); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
