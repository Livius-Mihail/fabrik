<?php
/**
 * Admin Element Edit - Validations Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

Use Joomla\CMS\Language\Text;

?>

	<fieldset>
		<legend><?php echo Text::_('COM_FABRIK_VALIDATIONS'); ?></legend>
		<div id="plugins" class="accordion"></div>
		<div class="row">
			<div class="col-md-12">
				<a href="#" class="btn btn-success" id="addPlugin">
					<i class="icon-plus"></i>
					<?php echo Text::_('COM_FABRIK_ADD'); ?>
				</a>
			</div>
		</div>
	</fieldset>