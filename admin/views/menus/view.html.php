<?php
// No direct access.
defined('_JEXEC') or die;

class StaticContentViewMenus extends View
{
	public function display($tpl = null)
	{
		Model::addIncludePath(JPATH_BASE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_menus'.DIRECTORY_SEPARATOR.'models');
		
		$model = JModel::getInstance('Menus','MenusModel');
		
		$this->menutypes = $model->getItems();
		$this->menu = JApplication::getInstance('site')->getMenu();
		
		parent::display($tpl);
	}
}