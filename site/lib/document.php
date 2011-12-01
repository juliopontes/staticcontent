<?php
class StaticContentDocument
{
	public function __construct(array $config = array())
	{
		//initialize vars
		$this->_config = $config;
		//set source directory
		$this->_source_directory = $this->_config['source_path'];
		
		//dom
		$this->_dom = new DOMDocument();
		$this->_dom->loadHTML($this->_config['content']);
		
		//source
		$this->content = $this->_config['content'];
	}
	
	public function proccess()
	{
		//remove URI root
		$this->content = str_replace(JURI::root(),'',$this->content);
		
		$this->_fixBaseTag();
		$this->_fixMenuLinks();
		$this->_fixBannersLinks();
		$this->_copyRequiredFiles();
		
		return $this->content;
	}
	
	private function _fixMenuLinks($menuItems)
	{
		foreach ($menuItems as $menuItem)
		{	
			$body = str_replace('href="'.JURI::root(true).'/'.$menuItem->menu.'"','href="'.$sefLink.'"',$body);
		}
		
		return $body;
	}
	
	private function _fixBannersLinks()
	{
		$db = JFactory::getDbo();
		$db->setQuery('SELECT id,clickurl FROM #__banners');
		$banners = $db->loadObjectList();
		
		foreach ($banners as $banner)
		{
			$bannerNoSEFUrl = JURI::root(true).'/index.php/component/banners/click/'.$banner->id;
			$bannerSEFUrl = $banner->clickurl;
			$this->content = str_replace('href="'.$bannerNoSEFUrl.'"','href="'.$bannerSEFUrl.'"',$this->content);
		}
	}
	
	private function _fixBaseTag()
	{
		$base = $this->_dom->getElementsByTagName('base');		
		
		//replace base
		if (!empty($base)) {
			foreach ($base as $node) {
				$href = $node->getAttribute('href');
				$this->content = str_replace('<base href="'.$href.'" />','<base href="'.$baseFolder.'index.html" />',$this->content );
			}
		}
	}
	
	private function _copyRequiredFiles()
	{
		$links = $this->_dom->getElementsByTagName('link');
		$scripts = $this->_dom->getElementsByTagName('script');
		$images = $this->_dom->getElementsByTagName('img');
		
		foreach ($links as $link) {
			$linkHref = $link->getAttribute('href');
			$cleanLinkHref = str_replace(JURI::root(true).'/', '', $linkHref);
			if (strpos($linkHref,'format=feed') !== false) {
				$cleanLinkHref = 'feed.xml';
			}
			$this->content = str_replace('href="'.htmlspecialchars($linkHref).'"','href="'.$cleanLinkHref.'"',$this->content);
			$this->copyFile($link, 'href');
		}
		foreach ($images as $img) {
			$imgHref = $img->getAttribute('src');
			
			$cleanImgHref = str_replace(str_replace(JURI::base(true),'',JURI::base()), '', $imgHref);
			$cleanImgHref = str_replace(JURI::base(true).'/', '', $cleanImgHref);
			$cleanImgHref = str_replace(JURI::base(true), '', $cleanImgHref);
			$img->setAttribute('src',$cleanImgHref);
			$this->content = str_replace('src="'.$imgHref.'"','src="'.$cleanImgHref.'"',$this->content);
			
			$this->copyFile($img, 'src');
		}
		foreach ($scripts as $script) {
			$scriptHref = $script->getAttribute('src');
			$cleanScriptHref = str_replace(JURI::root(true).'/', '', $scriptHref);
			$this->content = str_replace('src="'.$scriptHref.'"','src="'.$cleanScriptHref.'"',$this->content);
			$this->copyFile($script, 'src');
		}
	}
	
	private function copyFile($node,$attribute)
	{
		$path_source = $this->_source_directory;
		
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
					}
				}
			}
			else {
				var_dump($intersect);
				var_dump($path);
				var_dump($uri->getPath());
				die($url);
			}
		}
	}
}