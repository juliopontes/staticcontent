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
		$site = JApplication::getInstance('site');
		$items = $site->getMenu()->getMenu();
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
			
			$authorize = $site->getMenu()->authorise($item->id);
			if ( ($uri->getVar('option') == 'com_user' && $uri->getVar('view') == 'profile' ) || !$authorize) continue;
			
			$file_source = (!$item->home) ? $item->alias.'.html' : 'index.html' ;
			$path_source = $this->base_directory.DS;
			
			$body = file_get_contents($url);
			if (empty($body)) continue;
			$domDocument = new DOMDocument();
			$domDocument->loadHTML($body);
			$links = $domDocument->getElementsByTagName('link');
			$scripts = $domDocument->getElementsByTagName('script');
			$images = $domDocument->getElementsByTagName('img');
			
			$intersect = array_intersect(explode(DS,JPATH_ROOT), explode('/',$uri->getPath()));
			$basePath = '/'.implode('/',$intersect).'/';
			
			$body = str_replace($uri->root(),'',$body);
			$body = str_replace($basePath,'',$body);
			
			foreach ($images as $img) {
				$this->copyFile($img, 'src');
			}
			foreach ($scripts as $script) {
				$this->copyFile($script, 'src');
			}
			foreach ($links as $link) {
				$this->copyFile($link, 'href');
			}
			
			//$body = $this->fixLinks($body,$items);
			
			$pathFileSource = $path_source.$file_source;
			if (JFile::write($pathFileSource, $body)) $countFiles++;
		}
		
		JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_STATICCONTENT_HTML_GENERATE_HTML',$countFiles));
		$this->display();
	}
	

	public static function _($url, $xhtml = true, $ssl = null)
	{
		// Get the router.
		$app	= JApplication::getInstance('site');
		$router	= $app->getRouter();

		// Make sure that we have our router
		if (!$router) {
			return null;
		}

		if ((strpos($url, '&') !== 0) && (strpos($url, 'index.php') !== 0)) {
			return $url;
		}

		// Build route.
		$uri = $router->build($url);
		$url = $uri->toString(array('path', 'query', 'fragment'));

		// Replace spaces.
		$url = preg_replace('/\s/u', '%20', $url);

		/*
		 * Get the secure/unsecure URLs.
		 *
		 * If the first 5 characters of the BASE are 'https', then we are on an ssl connection over
		 * https and need to set our secure URL to the current request URL, if not, and the scheme is
		 * 'http', then we need to do a quick string manipulation to switch schemes.
		 */
		if ((int) $ssl) {
			$uri = JURI::getInstance();

			// Get additional parts.
			static $prefix;
			if (!$prefix) {
				$prefix = $uri->toString(array('host', 'port'));
			}

			// Determine which scheme we want.
			$scheme	= ((int)$ssl === 1) ? 'https' : 'http';

			// Make sure our URL path begins with a slash.
			if (!preg_match('#^/#', $url)) {
				$url = '/'.$url;
			}

			// Build the URL.
			$url = $scheme.'://'.$prefix.$url;
		}

		if ($xhtml) {
			$url = htmlspecialchars($url);
		}

		return $url;
	}
	
	private function fixLinks($body,$items)
	{
		foreach ($items as $item) {
			
			//not copy external links
			if (!JURI::isInternal($item->link) || $item->access != 1) continue;
			
			ComStaticContentHelperMenu::getLink($item);
			if (!isset($item->flink)) continue;
			$item->flink = str_replace('administrator/','',$item->flink);
			
			if (!isset($item->flink)) continue;
			
			$url = self::_($item->flink);
			
			$base = JUri::root();
			
			$url = str_replace($base.'/','',$url);
			
			$body = $this->fixLink($url, $item->alias, $body);
		}
		
		return $body;
	}
	
	private function fixLink($url,$alias,$body)
	{
		$body = str_replace($url,$alias.'.html',$body);
		
		return $body;
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
			
			$intersect = array_intersect(explode(DS,JPATH_ROOT), explode('/',$uri->getPath()));
			$path = str_replace(implode('/',$intersect).'/','',$uri->getPath());
			
			
			$sourceFilePath = JPath::clean(JPATH_ROOT.$path);
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