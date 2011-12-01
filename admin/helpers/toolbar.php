<?php
class ComStaticContentHelperToolbar extends JToolBarHelper
{
	public static function customExport($height = '550', $width = '875', $onClose = '')
	{
		$top = 0;
		$left = 0;
		$bar = JToolBar::getInstance('toolbar');
		// Add a configuration button.
		$bar->appendButton('Popup', 'options', JText::_('COM_STATICCONTENT_TOOLBAR_CUSTOM_EXPORT'), 'index.php?option=com_staticcontent&amp;view=menus&amp;tmpl=component', $width, $height, $top, $left, $onClose);
	}
	
	public static function completeExport()
	{
		$bar = JToolBar::getInstance('toolbar');
		$bar->appendButton('Link', 'refresh', JText::_('COM_STATICCONTENT_TOOLBAR_CREATE'),'javascript:requestAllItems();');
	}
}