<?php
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

require_once JPATH_COMPONENT.DS.'helpers'.DS.'siterouter.php';
require_once JPATH_COMPONENT.DS.'helpers'.DS.'menu.php';

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
		ini_set('max_execution_time','0');
		$guest = JFactory::getUser(0);
		$site = JApplication::getInstance('site');
		$items = $site->getMenu()->getItems(null,array());
		$config = new JConfig();
		$countFiles = 0;
		
		$params = JComponentHelper::getParams('com_staticcontent');
		$this->base_directory = JPath::clean($params->get('base_directory'));

		$coreFolders = array(
				JPATH_ROOT.DS.'administrator',
				JPATH_ROOT.DS.'components',
				JPATH_ROOT.DS.'modules',
				JPATH_ROOT.DS.'includes',
				JPATH_ROOT.DS.'plugins',
				JPATH_ROOT.DS.'templates',
				JPATH_ROOT.DS.'images',
				JPATH_ROOT.DS.'language',
				JPATH_ROOT.DS.'libraries',
				JPATH_ROOT.DS.'cache',
				JPATH_ROOT.DS.'cli',
				JPATH_ROOT.DS.'images');

		if ($this->base_directory == $config->tmp_path || $this->base_directory == $config->log_path || in_array($this->base_directory,$coreFolders)) {
			JFactory::getApplication()->enqueueMessage(JText::_('COM_STATICCONTENT_HTML_CONFIG_PATH_IN_USE'));
			$this->setRedirect('index.php?option=com_staticcontent');
			$this->redirect();			
		}

		echo $this->base_directory;
		exit;

		foreach ($items as $item) {
			$canAccessMenu = $this->checkMenuAccess($guest,$item->id);
			//not copy external links and check if guest user has access
			if (!JURI::isInternal($item->link) || !$canAccessMenu) continue;
			
			ComStaticContentHelperMenu::getLink($item);
			
			if (!isset($item->flink)) {
				continue;
			}
			
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
			
			$body = $this->fixMenuLinks($body);
			
			foreach ($links as $link) {
				$this->copyFile($link, 'href');
			}
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
			//creating folders
			JFolder::create(dirname($pathFileSource));
			if (JFile::write($pathFileSource, $body)) $countFiles++;
		}
		
		
		JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_STATICCONTENT_HTML_GENERATE_HTML',$countFiles));
		$this->setRedirect('index.php?option=com_staticcontent');
		$this->redirect();
	}
	
	private function checkMenuAccess($user,$mid)
	{
		$site = JApplication::getInstance('site');
		$menu = $site->getMenu()->getItem($mid);
		
		if ($menu) {
			return in_array((int) $menu->access, $user->getAuthorisedViewLevels());
		}
		else {
			return true;
		}
	}

	public static function _($url, $xhtml = true, $ssl = null)
	{
		// Get the router.
		$app	= JApplication::getInstance('site');
		$config = JFactory::getConfig();
		$options = array('mode' => $config->get('sef'));
		$router	= new ComStaticContentHelperSiteRouter($options);

		// Make sure that we have our router
		if (!$router) {
			return null;
		}

		if ((strpos($url, '&') !== 0) && (strpos($url, 'index.php') !== 0)) {
			return $url;
		}

		// Build route.
		$uri = $router->build($app,$url);
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
	
	private function fixMenuLinks($body)
	{
		$db = JFactory::getDbo();
		$guest = JFactory::getUser(0);
		$site = JApplication::getInstance('site');
		$router = new ComStaticContentHelperSiteRouter();
		
		$domDocument = new DOMDocument();
		$domDocument->loadHTML($body);
		$linksDomDocument = $domDocument->getElementsByTagName('a');
		$links = array();
		foreach ($linksDomDocument as $linkDomDocument) {
			if ($linkDomDocument->hasAttribute('href')) {
				$url = $linkDomDocument->getAttribute('href');
				if (empty($url) || strpos($url,'index.php') === false) continue;
				
				$url = JURI::root().$url;
				$base = JUri::root();
				$clean_url = str_replace($base,'',$url);
				
				$uri	= new JUri($url);
				$result = $router->parse($uri);
				
				if (strpos($clean_url,'index.php/component/')!== false) {
					if (strpos($clean_url,'component/banners') !== false){
						$id = end( explode('/',$url) );
						
						$db->setQuery('SELECT clickurl FROM #__banners WHERE id = '.$db->quote(intval($id)));
						$key = $db->loadResult();
						
						$links[$key] = $clean_url;
					}
				}
				else {
					//menu item
					$config = new JConfig();
					if ($config->sef && !$config->sef_rewrite) {
						$alias = str_replace('index.php/','',$clean_url);
						$alias .= '.html';
					}
					else if($config->sef && $config->sef_rewrite) {
						$alias = str_replace('index.php/','',$clean_url);
						$alias .= '.html';
					}
					
					if (isset($alias))
						$links[$alias] = $clean_url;
				}
			}
		}
		
		if (!empty($links)) {
			foreach ($links as $alias => $original) {
				$body = str_replace('href="'.$original.'"','href="'.$alias.'"',$body);
			}
		}
		
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
			
			if(JURI::isInternal($url) == $interno || strpos($url,'index.php') !== false) return;
			
			$intersect = array_intersect(explode(DS,JPATH_ROOT), explode('/',$uri->getPath()));
			$path = str_replace(implode('/',$intersect).'/','',$uri->getPath());
			
			$sourceFilePath = JPath::clean(JPATH_ROOT.DS.$path);
			$filePath = JPath::clean($path_source.DS.$path);
			
			if (JFile::exists($sourceFilePath)) {
				//creating folders
				JFolder::create(dirname($filePath));
				//copy file
				if (JFile::copy($sourceFilePath, $filePath)) {
					//copy all url(*) data
					if (strtolower( JFile::getExt($sourceFilePath) ) == 'css') {
						//$break = (JFile::getName($sourceFilePath) == 'personal.css');
						$css_file_content = JFile::read($sourceFilePath);
						preg_match_all('/(url|URL)\(.*?\)/i', $css_file_content, $data_array);
						
						if (!empty($data_array[0])) {
							$baseSourceFilePath = dirname($sourceFilePath).DS;
							$baseFilePath = dirname($filePath).DS;
							foreach($data_array[0] as $img) {
								$removeDirs = substr_count($img,'../');
								$clean_path = str_replace('../','',$img);
								$clean_path = str_replace('"','',$clean_path);
								$clean_path = str_replace('(','',$clean_path);
								$clean_path = str_replace(')','',$clean_path);
								$clean_path = str_replace('url','',$clean_path);
								$clean_path = str_replace('URL','',$clean_path);
								
								for ($d=1;$d<=$removeDirs;$d++) {
									$sourceFilePath = dirname($baseSourceFilePath).DS;
									$filePath = dirname($baseFilePath).DS;
								}
								$sourceFilePath = $sourceFilePath.$clean_path;
								$filePath = $filePath.$clean_path;
								$sourceFilePath = JPath::clean($sourceFilePath);
								$filePath = JPath::clean($filePath);
								
								if (JFile::exists($sourceFilePath)) {
									//creating folders
									JFolder::create(dirname($filePath));
									if (!JFile::copy($sourceFilePath, $filePath)) {
										die($sourceFilePath);
									}
								}
							}
						}
						else {
							//echo JFile::getName($sourceFilePath);
						}
					}
				}
			}
		}
	}
}
