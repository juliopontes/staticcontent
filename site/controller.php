<?php
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class StaticContentController extends JController
{
	public function allpages()
	{
		// Check for request forgeries.
		//JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$site = JFactory::getApplication('site');
		$menuItems = $site->getMenu('site')->getItems(array(),array());
		$this->export($menuItems);
	}
	
	public function menuitems()
	{
		// Check for request forgeries.
		//JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request.
		$cid	= JRequest::getVar('cid', array(), '', 'array');

		if (!is_array($cid) || count($cid) < 1) {
			die(JText::_('COM_STATICCONTENT_NO_MENU_ITEMS_SELECTED'));
		} else {
			$site = JApplication::getInstance('site');
			$menuItems = array();
			foreach($cid as $mid) {
				$menuItems[] = $site->getMenu()->getItem($mid);
			}
		}
		
		$this->export($menuItems);
	}
	
	private function export($items)
	{
		// Check for request forgeries.
		//JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$model = $this->getModel('export');
		$model->setItems($items);
		echo $model->createPages();
		JFactory::getApplication()->close();
	}
}