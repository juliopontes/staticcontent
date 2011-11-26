<?php
/**
 * @version		$Id: default.php $
 * @package		Joomla.Administrator
 * @subpackage	com_restore
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

$template = JFactory::getApplication()->getTemplate();

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
?>
<form action="<?php echo JRoute::_('index.php?option=com_staticcontent');?>" id="extensions-form" method="post" name="adminForm" autocomplete="off" class="form-validate">
	<fieldset>
		<div class="fltrt">
			<button type="button" onclick="Joomla.submitform('html.customexport', this.form);">
				<?php echo JText::_('COM_STATICCONTENT_BUTTON_CUSTOM_EXPORT');?></button>
			<button type="button" onclick="<?php echo JRequest::getBool('refresh', 0) ? 'window.parent.location.href=window.parent.location.href;' : '';?>  window.parent.SqueezeBox.close();">
				<?php echo JText::_('JCANCEL');?></button>
		</div>
		<div class="configuration" >
			<?php echo JText::_('COM_STATICCONTENT_MENUS_TITLE') ?>
		</div>
		
		<?php echo JHtml::_('tabs.start','slides-extension-types', array('useCookie'=>1)); ?>
			<?php foreach ($this->menutypes as $menutype): ?>
				<?php echo JHtml::_('tabs.panel',$menutype->title, $menutype->menutype.'-menutype'); ?>
				<ul>
				<?php foreach($this->menu->getItems('menutype',$menutype->menutype) as $menuItem): ?>
					<li>
						<input type="checkbox" name="cid[]" value="<?php echo $menuItem->id; ?>"> <span><?php echo $menuItem->title; ?></span>
					</li>
				<?php endforeach; ?>
				</ul>
				<div class="clr"></div>
			<?php endforeach; ?>
		<?php echo JHtml::_('tabs.end'); ?>
			
	</fieldset>
	<div>
		<input type="hidden" name="option" value="com_staticcontent" />
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
