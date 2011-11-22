<?php
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

class StaticContentViewStaticContent extends JView
{
	public function display($tpl = null)
	{
		$params = JComponentHelper::getParams('com_staticcontent');
		$base_directory = $params->get('base_directory');
		
		$this->base_directory = empty($base_directory) ? '' : JPath::clean($base_directory);
		
		if ( !empty($this->base_directory) )
		{
			if (!JFolder::exists($this->base_directory)) {
				JFolder::create($this->base_directory);
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_STATICCONTENT_HTML_CREATED_DIRECTORY_MESSAGE',$this->base_directory));
			}
			$this->items = JFolder::files($this->base_directory, '.html', true, true);
			foreach ($this->items as &$item) {
				$item = str_replace($this->base_directory.DS,'',$item);
				$item = str_replace(DS,'/',$item);
			}
			
			$folderPath = str_replace(JPATH_ROOT.DS,'',$this->base_directory);
			$this->baseUri = JUri::root().$folderPath.'/';
		}
		else {
			$this->_layout = 'preferences';
		}
		
		$this->addToolbar();
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		JToolBarHelper::title('Static Content');
		
		if (!empty($this->base_directory)) {
			JToolBarHelper::custom('html.generate','refresh','refresh','COM_STATICCONTENT_TOOLBAR_CREATE',false);
			
			if(!empty($this->items)) {
				JToolBarHelper::custom('html.delete','delete','delete','COM_STATICCONTENT_TOOLBAR_DELETE', false);
				JToolBarHelper::custom('html.download','archive','archive','COM_STATICCONTENT_TOOLBAR_DOWNLOAD', false);
			}
		}
		
		JToolBarHelper::preferences('com_staticcontent');
	}
}