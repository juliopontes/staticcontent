<?php
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class StaticContentControllerHTML extends JController
{
	/**
	 * @var		string	The default view.
	 * @since	1.7
	 */
	protected $default_view = 'staticcontent';
	
	public function delete()
	{
		$params = JComponentHelper::getParams('com_staticcontent');
		$this->base_directory = JPath::clean($params->get('base_directory'));
		JFolder::delete($this->base_directory);
		JFolder::create($this->base_directory);
		JFactory::getApplication()->redirect('index.php?option=com_staticcontent&view=staticcontent',JText::_('COM_STATICCONTENT_HTML_DELETED'));
	}
	
	public function download()
	{
		$params = JComponentHelper::getParams('com_staticcontent');
		
		jimport('joomla.filesystem.archive');
		$adapter = JArchive::getAdapter('zip');
		$this->base_directory = JPath::clean($params->get('base_directory'));
		$tmpFiles = JFolder::files($this->base_directory, '.', true,true);
		
		$files = array();
		foreach ($tmpFiles as $tmpFile) {
			$clean_file = str_replace($this->base_directory,'',$tmpFile);
			$file = array(
				'name' => substr($clean_file,1),
				'data' => JFile::read($tmpFile)
			);
			array_push($files, $file);
		}
		
		$config = new JConfig();
		$fileName = $config->sitename.md5(JURI::root()).'.zip';
		
		$file = JPATH_ROOT.DS.'tmp'.DS.$fileName;
		JFile::delete($file);
		if (!$adapter->create($file,$files)) {
			JFactory::getApplication()->redirect('index.php?option=com_staticcontent&view=staticcontent',JText::_('COM_STATICCONTENT_HTML_ZIP_ERROR'));
		}
		
		if (file_exists($file)) {
		    header('Content-Description: File Transfer');
		    header('Content-Type: application/octet-stream');
		    header('Content-Disposition: attachment; filename='.basename($file));
		    header('Content-Transfer-Encoding: binary');
		    header('Expires: 0');
		    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		    header('Pragma: public');
		    header('Content-Length: ' . filesize($file));
		    ob_clean();
		    flush();
		    readfile($file);
		    JFactory::getApplication()->close();
		}
	}
}
