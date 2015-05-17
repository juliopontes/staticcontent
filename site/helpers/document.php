<?php
jimport('joomla.log.log');

abstract class StaticContentHelperDocument
{
	private static $_LOG;

	static public function body($page,$pageLinks,$itemLevel)
	{
		// $baseFolder = ($itemLevel <= 0) ? '' : str_repeat('../',$itemLevel) ;
		$body = $page->content;
		$domDocument = new DOMDocument();
		$domDocument->loadHTML($body);

		$option = JFactory::getApplication()->input->get('option');
		$comParams = JComponentHelper::getParams($option);
        $newBaseURL = rtrim($comParams->get('base_url' /* , $baseFolder */), '/') .'/';

		$links = $domDocument->getElementsByTagName('link');
		$scripts = $domDocument->getElementsByTagName('script');
		$images = $domDocument->getElementsByTagName('img');
        $pictures = $domDocument->getElementsByTagName('source');
	
		$body = str_replace(JURI::root(),'',$body);

		foreach ($links as $link) {
            $url = $link->getAttribute('href');
            $relativeUrl = StaticContentHelperUrl::getRelativeLink($url);
            $link->setAttribute('href', $relativeUrl);
			$body = str_replace('href="'.htmlspecialchars($url).'"','href="'.$relativeUrl.'"',$body);
			self::copyFile($link, 'href');
		}
		foreach ($images as $img) {
			$url = $img->getAttribute('src');
            $relativeUrl = StaticContentHelperUrl::getRelativeLink($url);
			$img->setAttribute('src', $relativeUrl);
			$body = str_replace('src="'.$url.'"','src="'.$relativeUrl.'"',$body);
			self::copyFile($img, 'src');
		}
        foreach ($pictures as $picture) {
            $url = $picture->getAttribute('srcset');
            $relativeUrl = StaticContentHelperUrl::getRelativeLink($url);
            $picture->setAttribute('srcset', $relativeUrl);
            $body = str_replace('src="'.$url.'"','src="'.$relativeUrl.'"',$body);

            self::copyFile($picture, 'srcset');
        }
		foreach ($scripts as $script) {
            $url = $script->getAttribute('src');
            $relativeUrl = StaticContentHelperUrl::getRelativeLink($url);
            $script->setAttribute('src', $relativeUrl);
			$body = str_replace('src="'.$url.'"','src="'.$relativeUrl.'"',$body);
			self::copyFile($script, 'src');
		}
		
		if (!empty($pageLinks['print'])) {
			$body = self::fixPrintLinks($body,$pageLinks['print']);
		}
		$body = self::fixMenuLinks($body,$pageLinks);
		$body = self::fixBannersLinks($body);

        // Need to parse DOM again since the original 'base' could
        // have been already replaced.
        $domDocument = new DOMDocument();
        $domDocument->loadHTML($body);
        if ($base = $domDocument->getElementsByTagName('base')) {
            foreach ($base as $node) {
                $href = $node->getAttribute('href');
                $body = str_replace('<base href="' . $href . '" />', '<base href="' . $newBaseURL . '" />', $body);
            }
        }

        unset($domDocument);

		return $body;
	}
	
	static public function fixMenuLinks($body,$menuItems)
	{
        $params = JComponentHelper::getParams('com_staticcontent');
        $allMenuItems = array_merge($menuItems['menu'], $menuItems['pages']);

		foreach ($allMenuItems as $menuItem)
		{
			$originalLink = JURI::root(true).'/'.ltrim($menuItem->relative, '/');
            $updatedLink = $menuItem->relative;

            if ($updatedLink == '/') {
                $updatedLink = $params->get('base_url');
            }

			$body = str_replace('href="'.$originalLink.'"','href="'.$updatedLink.'"',$body);
		}

		return $body;
	}
	
	static public function fixPrintLinks($body,$printLinks)
	{
		if (empty($body)) {
			return $body;
        	}

		foreach ($printLinks as $originaPrintLink => $sefPrintLink) {
			//remove base
			$base = JURI::root(true);
			if ($base && strpos($originaPrintLink, $base . '/') === 0) {
				$originaPrintLink = substr($originaPrintLink, strlen($base . '/'));
			}
			$body = str_replace('href="'.htmlspecialchars($originaPrintLink).'"','href="'.$sefPrintLink.'"',$body);
		}

		return $body;
	}
	
	static public function fixBannersLinks($body)
	{
		$db = JFactory::getDbo();
		$db->setQuery('SELECT id,clickurl FROM #__banners');
		$banners = $db->loadObjectList();
		
		foreach ($banners as $banner)
		{
			$bannerNoSEFUrl = JURI::root(true).'/index.php/component/banners/click/'.$banner->id;
			$bannerSEFUrl = $banner->clickurl;
			$body = str_replace('href="'.$bannerNoSEFUrl.'"','href="'.$bannerSEFUrl.'"',$body);
		}
		
		return $body;
	}
	
	static public function copyFile($node,$attribute)
	{
		$option = JFactory::getApplication()->input->get('option');
		$comParams = JComponentHelper::getParams($option);
		$path_source = JPath::clean($comParams->get('base_directory'));
		
		if ($node->hasAttribute($attribute)) {
			$url = $node->getAttribute($attribute);
			$uri = JFactory::getURI($url);

            if (!JURI::isInternal($url)) {
                return;
            }

			$path = $uri->getPath();

			if (strpos($path,'~') > 0) {
				$path = explode('/',$path);
				array_shift($path);
				array_shift($path);
				$path = implode($path,'/');
                $uri->setPath($path);
			}

			if (empty($path) || $path == '<') {
                return;
            }

			$sourceFilePath = JPath::clean(JPATH_ROOT.DIRECTORY_SEPARATOR.$path);
			$filePath = JPath::clean($path_source.DIRECTORY_SEPARATOR.$path);

			if (JFile::exists($sourceFilePath)) {
				//creating folders
				JFolder::create(dirname($filePath));
				//copy file
				if (JFile::copy($sourceFilePath, $filePath)) {
					//copy all url(*) data
					if (strtolower( JFile::getExt($sourceFilePath) ) == 'css') {
						$css_file_content = JFile::read($sourceFilePath);
						preg_match_all('/(url|URL)\(.*?\)/i', $css_file_content, $data_array);
						if (!empty($data_array[0])) {
							$baseSourceFilePath = dirname($sourceFilePath).DIRECTORY_SEPARATOR;
							$baseFilePath = dirname($filePath).DIRECTORY_SEPARATOR;
							
							foreach($data_array[0] as $img) {
								$img = trim($img);
								$removeDirs = substr_count($img,'./');
								$removeDirs += substr_count($img,'../');
								$clean_path = str_replace('../','',$img);
								$clean_path = str_replace('"','',$clean_path);
								$clean_path = str_replace("'",'',$clean_path);
								$clean_path = str_replace('(','',$clean_path);
								$clean_path = str_replace(')','',$clean_path);
								$clean_path = str_replace('url','',$clean_path);
								$clean_path = str_replace('URL','',$clean_path);
								
								for ($d=1;$d<=$removeDirs;$d++) {
									$sourceFilePath = dirname($baseSourceFilePath).DIRECTORY_SEPARATOR;
									$filePath = dirname($baseFilePath).DIRECTORY_SEPARATOR;
								}
								$sourceFilePath = $sourceFilePath.$clean_path;
								$filePath = $filePath.$clean_path;
								$sourceFilePath = JPath::clean($sourceFilePath);
								$filePath = JPath::clean($filePath);
								
								if (JFile::exists($sourceFilePath)) {
									//creating folders
									JFolder::create(dirname($filePath));
									if (!JFile::copy($sourceFilePath, $filePath)) {
										die(JText::sprintf('COM_STATICCONTENT_MSG_FAILURE_COPY_FILE',$sourceFilePath));
									}
								} else {
									self::log("Cant copy file {$sourceFilePath}");
								}
							}
						}
					}
				}
				else {
					die(JText::sprintf('COM_STATICCONTENT_MSG_FAILURE_COPY_FILE',$sourceFilePath));
				}
			} else {
				self::log("Cant find file {$sourceFilePath}");
			}
		}
	}
	
	static private function log($message)
	{
		if (empty(self::$_LOG[$message]))
			self::$_LOG[$message] = true;
	}
	
	public static function writeLog()
	{
		$date = JFactory::getDate()->format('Y-m-d');

		// Add the logger.
		JLog::addLogger(
		    array(
		        'text_file' => 'com_statiscontent.'.$date.'.php'
		    )
		);
		
		foreach (self::$_LOG as $message => $trunk)
			JLog::add($message);
	}
}
