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
	
	public function generate()
	{
		$items = JApplication::getInstance('site')->getMenu()->getMenu();
		$countFiles = 0;
		
		$params = JComponentHelper::getParams('com_staticcontent');
		$this->base_directory = JPath::clean($params->get('base_directory'));
		
		require_once JPATH_COMPONENT.DS.'helpers'.DS.'menu.php';
		
		foreach ($items as $item) {
			
			//not copy external links
			if (!JURI::isInternal($item->link) || $item->access != 1) continue;
			
			ComStaticContentHelperMenu::getLink($item);
			
			if (!isset($item->flink)) continue;
			
			$uri = JURI::getInstance($item->flink);
			$url = JURI::root();
			$url .= 'index.php?'.$uri->getQuery();
			
			if ($uri->getVar('option') == 'com_user' && $uri->getVar('view') == 'profile') continue;
			
			$file_source = (!$item->home) ? $item->alias.'.html' : 'index.html' ;
			$path_source = $this->base_directory.DS;
			
			$body = file_get_contents($url);
			if (empty($body)) continue;
			$domDocument = new DOMDocument();
			$domDocument->loadHTML($body);
			$links = $domDocument->getElementsByTagName('link');
			$scripts = $domDocument->getElementsByTagName('script');
			$images = $domDocument->getElementsByTagName('img');
			
			foreach ($images as $img) {
				$this->copyFile($img, 'src');
			}
			foreach ($scripts as $script) {
				$this->copyFile($script, 'src');
			}
			foreach ($links as $link) {
				$this->copyFile($link, 'href');
			}
			
			$pathFileSource = $path_source.$file_source;
			if (JFile::write($pathFileSource, $body)) $countFiles++;;
		}
		
		JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_STATICCONTENT_HTML_GENERATE_HTML',$countFiles));
		$this->display();
	}
	
	private function copyFile($node,$attribute)
	{
		$path_source = $this->base_directory;
		
		if ($node->hasAttribute($attribute)) {
			$url = $node->getAttribute($attribute);
			$uri = JFactory::getURI($url);
			
			$interno = false;
			
			$uriHost = $uri->getHost();
			if (!empty($uriHost) && $uriHost == JURI::getInstance()->getHost()) $interno = true;
			
			if(JURI::isInternal($url) == $interno) return;
			
			$path = str_replace(end( explode(DS,JPATH_ROOT) ).'/','',$uri->getPath());
			
			$sourceFilePath = JPATH_ROOT.$path;
			$filePath = JPath::clean($path_source.$path);
	
			if (JFile::exists($sourceFilePath)) {
				//creating folders
				JFolder::create(dirname($filePath));
				//copy file
				JFile::copy($sourceFilePath, $filePath);
			}
		}
	}
}