<?php
abstract class StaticContentHelperDocument
{
	static public function body($page,$pageLinks,$itemLevel)
	{
		$baseFolder = ($itemLevel <= 0) ? '' : str_repeat('../',$itemLevel) ;
		
		$body = $page->content;
		$domDocument = new DOMDocument();
		$domDocument->loadHTML($body);
		
		$base = $domDocument->getElementsByTagName('base');		
		
		//replace base
		if (!empty($base)) {
			foreach ($base as $node) {
				$href = $node->getAttribute('href');
				$body = str_replace('<base href="'.$href.'" />','<base href="'.$baseFolder.'index.html" />',$body);
			}
		}
		
		$links = $domDocument->getElementsByTagName('link');
		$scripts = $domDocument->getElementsByTagName('script');
		$images = $domDocument->getElementsByTagName('img');
	
		$body = str_replace(JURI::root(),'',$body);
		
		foreach ($links as $link) {
			$linkHref = $link->getAttribute('href');
			$cleanLinkHref = str_replace(JURI::root(true).'/', '', $linkHref);
			if (strpos($linkHref,'format=feed') !== false) {
				$cleanLinkHref = 'feed.xml';
			}
			$body = str_replace('href="'.htmlspecialchars($linkHref).'"','href="'.$cleanLinkHref.'"',$body);
			self::copyFile($link, 'href');
		}
		foreach ($images as $img) {
			$imgHref = $img->getAttribute('src');
			
			$cleanImgHref = str_replace(str_replace(JURI::base(true),'',JURI::base()), '', $imgHref);
			$cleanImgHref = str_replace(JURI::base(true).'/', '', $cleanImgHref);
			$cleanImgHref = str_replace(JURI::base(true), '', $cleanImgHref);
			$img->setAttribute('src',$cleanImgHref);
			$body = str_replace('src="'.$imgHref.'"','src="'.$cleanImgHref.'"',$body);
			
			self::copyFile($img, 'src');
		}
		foreach ($scripts as $script) {
			$scriptHref = $script->getAttribute('src');
			$cleanScriptHref = str_replace(JURI::root(true).'/', '', $scriptHref);
			$body = str_replace('src="'.$scriptHref.'"','src="'.$cleanScriptHref.'"',$body);
			self::copyFile($script, 'src');
		}
		
		unset($domDocument);
		
		if (!empty($pageLinks['print'])) {
			$body = self::fixPrintLinks($body,$pageLinks['print']);
		}
		$body = self::fixMenuLinks($body,$pageLinks);
		$body = self::fixBannersLinks($body);
		
		return $body;
	}
	
	static public function fixMenuLinks($body,$menuItems)
	{
		foreach ($menuItems['menu'] as $menuItem)
		{	
			$sefLink = $menuItem->file;
			$originalLink = JURI::root(true).'/'.$menuItem->relative;
			$body = str_replace('href="'.$originalLink.'"','href="'.$sefLink.'"',$body);
		}
		
		foreach ($menuItems['pages'] as $menuItem)
		{	
			$sefLink = $menuItem->file;
			$originalLink = JURI::root(true).'/'.$menuItem->relative;
			$body = str_replace('href="'.$originalLink.'"','href="'.$sefLink.'"',$body);
		}
		
		return $body;
	}
	
	static public function fixPrintLinks($body,$printLinks)
	{
		foreach ($printLinks as $originaPrintLink => $sefPrintLink) {
			$originaPrintLink = str_replace(JURI::base(true).'/','', $originaPrintLink);
			$body = str_replace('href="'.$originaPrintLink.'"','href="'.$sefPrintLink.'"',$body);
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
		$option = JRequest::getCmd('option');
		$comParams = JComponentHelper::getParams($option);
		$path_source = JPath::clean($comParams->get('base_directory'));
		
		if ($node->hasAttribute($attribute)) {
			$url = $node->getAttribute($attribute);
			$uri = JFactory::getURI($url);
			
			$interno = false;
			
			$uriHost = $uri->getHost();
			if (!empty($uriHost) && $uriHost == JURI::getInstance()->getHost()) $interno = true;
			
			if(JURI::isInternal($url) == $interno || strpos($url,'index.php') !== false) return;
			
			$tmp = str_replace('/',DS,$uri->getPath());
			$intersect = array_intersect(explode(DS,JPATH_ROOT), explode(DS,$tmp));
			$tmpBasePath = implode('/',$intersect);
			if (!empty($tmpBasePath)) $tmpBasePath .= '/';
			
			if (!empty($tmpBasePath)) {
				$path = str_replace($tmpBasePath,'',$uri->getPath());
			}
			else {
				$path = $uri->getPath();
			}
			
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
										die(JText::sprintf('COM_STATICCONTENT_MSG_FAILURE_COPY_FILE',$sourceFilePath));
									}
								}
							}
						}
					}
				}
				else {
					die(JText::sprintf('COM_STATICCONTENT_MSG_FAILURE_COPY_FILE',$sourceFilePath));
				}
			}
		}
	}
}