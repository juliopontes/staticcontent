<?php
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

class StaticContentViewMenus extends JView
{
	public function display($tpl = null)
	{
		JModel::addIncludePath(JPATH_BASE.DS.'components'.DS.'com_menus'.DS.'models');
		
		$model = JModel::getInstance('Menus','MenusModel');
		
		$this->menutypes = $model->getItems();
		$this->menu = JApplication::getInstance('site')->getMenu();
		
		parent::display($tpl);
	}
}