<?php
// no direct access
defined('_JEXEC') or die;

function checkMenuAccess($user,$mid)
{
	$site = JApplication::getInstance('site');
	$menu = $site->getMenu('site')->getItem($mid);
	
	if ($menu) {
		return in_array((int) $menu->access, $user->getAuthorisedViewLevels());
	}
	else {
		return true;
	}
}

function buildMenuLink(&$item)
{
	$item->flink = $item->link;

	switch ($item->type)
	{
		case 'separator':
			// No further action needed.
			continue;

		case 'url':
			if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
				// If this is an internal Joomla link, ensure the Itemid is set.
				$item->flink = $item->link.'&Itemid='.$item->id;
			}
			break;

		case 'alias':
			// If this is an alias use the item id stored in the parameters to make the link.
			$item->flink = 'index.php?Itemid='.$item->params->get('aliasoptions');
			break;

		default:
			$router = JSite::getRouter();
			if ($router->getMode() == JROUTER_MODE_SEF) {
				$item->flink = 'index.php?Itemid='.$item->id;
			}
			else {
				$item->flink .= '&Itemid='.$item->id;
			}
			break;
	}

	if (strcasecmp(substr($item->flink, 0, 4), 'http') && (strpos($item->flink, 'index.php?') !== false)) {
		$item->flink = JRoute::_($item->flink, true, $item->params->get('secure'));
	}
	else {
		$item->flink = JRoute::_($item->flink);
	}
}

function copyFile($node,$attribute)
{
	$path_source = JPATH_ROOT.DS.'htmlstatic';
	
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
		else {
			var_dump($intersect);
			var_dump($path);
			var_dump($uri->getPath());
			die($url);
		}
	}
}

function proccessHtml($item,$menuItems,$printLinks,$itemLevel,$baseUrlPath)
{
	$body = $item->content;
	
	$domDocument = new DOMDocument();
	$domDocument->loadHTML($body);
	
	$baseFolder = ($itemLevel <= 0) ? '' : str_repeat('../',$itemLevel) ;
	
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
		copyFile($link, 'href');
	}
	foreach ($images as $img) {
		$imgHref = $img->getAttribute('src');
		
		$cleanImgHref = str_replace(str_replace(JURI::base(true),'',JURI::base()), '', $imgHref);
		$cleanImgHref = str_replace(JURI::base(true).'/', '', $cleanImgHref);
		$cleanImgHref = str_replace(JURI::base(true), '', $cleanImgHref);
		$img->setAttribute('src',$cleanImgHref);
		$body = str_replace('src="'.$imgHref.'"','src="'.$cleanImgHref.'"',$body);
		
		copyFile($img, 'src');
	}
	foreach ($scripts as $script) {
		$scriptHref = $script->getAttribute('src');
		$cleanScriptHref = str_replace(JURI::root(true).'/', '', $scriptHref);
		$body = str_replace('src="'.$scriptHref.'"','src="'.$cleanScriptHref.'"',$body);
		copyFile($script, 'src');
	}
	
	if(!empty($printLinks)) $body = fixPrintLinks($body,$printLinks);
	$body = fixMenuLinks($body,$menuItems,$baseUrlPath);
	$body = fixBannersLinks($body);
	
	return $body;
}

function fixPrintLinks($body,$printLinks)
{
	foreach ($printLinks as $sefPrintLink => $originaPrintLink) {
		$body = str_replace(JURI::base(true).'/'.$originaPrintLink,$sefPrintLink,$body);
	}
	
	return $body;
}

function fixBannersLinks($body)
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

function fixMenuLinks($body,$menuItems,$baseUrlPath)
{
	foreach ($menuItems as $menuItem)
	{	
		$sefLink = str_replace('.html','',$menuItem->file).'.html';
		$sefLink = str_replace('index.php/', '', $sefLink);
		if (strpos($baseUrlPath,$sefLink) !== false) {
			$sefLink = str_replace($baseUrlPath,'',$sefLink);
		}
		
		$body = str_replace('href="'.JURI::root(true).'/'.$menuItem->menu.'"','href="'.$sefLink.'"',$body);
	}
	
	return $body;
}

jimport('joomla.cache.cache');

$guest = JFactory::getUser(0);
$site = JApplication::getInstance('site');
$config = new JConfig();
$menuItems = $site->getMenu('site')->getItems(array(),array());
$links = Array();
$internalLinks = array();
$printLinks = array();
$cache = JCache::getInstance();
$cache->setCaching( true );
$cache->setLifeTime(90000);

foreach ($menuItems as $menuItem) {
	//build menu item link
	buildMenuLink($menuItem);
	//create JURI object
	$uri = JURI::getInstance($menuItem->flink);
	//get guest user
	$authorize = checkMenuAccess($guest,$menuItem->id);
	//check if link is internal
	$internal = JURI::isInternal($menuItem->flink);
	//check if user has access to menu
	if (!$internal && !$authorize || $menuItem->type == 'url') continue;
	
	$path = JURI::root(true);
	$clean_link = str_replace($path.'/','',$menuItem->flink);
	$full_link = JURI::root().$clean_link;
	
	if ( array_search($menuItem->flink,$links) == false ) {
		$file_source = str_replace('index.php/','',$clean_link);
		if (empty($file_source)) $file_source = 'index.html';
		else {
			$file_source = str_replace('.html','',$file_source) . '.html';
		}
		
		//cache requests
		$cache_id = md5($full_link);
		$cache_group = 'com_staticcontent';
		$request_content = $cache->get($cache_id,$cache_group);
		if (empty($request_content)) {
			$request_content = file_get_contents($full_link);
			$cache->store($request_content,$cache_id,$cache_group);
		}
		
		$link = new JObject();
		$link->set('file',$file_source);
		$link->set('link',$full_link);
		$link->set('menu',$clean_link);
		$link->set('content',$request_content);
		array_push($links,$link);
	}
}

$menuLinks = JArrayHelper::getColumn($links,'menu');
$menuLinks = array_unique($menuLinks);

foreach ($links as $link) {
		$dom = new DomDocument();
		$dom->loadHTML( $link->get('content') );
		
		$aLinks = $dom->getElementsByTagName('a');
		
		foreach ($aLinks as $aLink) {
			$href = $aLink->getAttribute('href');
			
			$linkJavascript = strpos($href,'javascript:') !== false;
			$linkAnchor = strpos($href,'#') !== false;			
			if ($linkJavascript || $linkAnchor) continue;
			
			$path = JURI::root(true);
			$clean_link = str_replace($path.'/','',$href);
			$clean_link = str_replace('.html','',$href);
			$clean_link = str_replace($path.'/','',$clean_link);
			
			$full_link = JURI::root().$clean_link;
			//check if is a print link
			if ( $full_link == JURI::root() || strpos($clean_link,'task=weblink.go') !== false || strpos($clean_link,'component/mailto') !== false || strpos($clean_link,'/component/banners') !== false || $path.'/' == $clean_link || $path.'/administrator' == $clean_link  || empty($clean_link) || array_search($clean_link,$menuLinks) !== false || !JURI::isInternal($clean_link) || $clean_link == 'administrator' ) continue;
			
			//cache requests
			$cache_id = md5($full_link);
			$cache_group = 'com_staticcontent';
			$request_content = $cache->get($cache_id,$cache_group);
			if (empty($request_content)) {
				$request_content = file_get_contents($full_link);
				$cache->store($request_content,$cache_id,$cache_group);
			}
			
			$file_source = $clean_link;
			$file_source = str_replace('index.php/','',$file_source);
			$file_source = str_replace('.html','',$file_source);
			
			//check is a print page
			if (strpos($clean_link,'print=1')) {
				$file_source = substr($file_source,0,strpos($file_source,'?')).'-print.html';
				$printLinks[$file_source] = $clean_link;
			}
			else {
				if (strpos($file_source, '?')) {
					$file_source = substr($file_source,0,strpos($file_source,'?'));
				}
				$file_source = $file_source.'.html';			
			}
			
			$internalLink = array_search($clean_link, $menuLinks);
			
			if ($internalLink === false)
			{
				$link = new JObject();
				$link->set('file',$file_source);
				$link->set('link',$full_link);
				$link->set('menu',$clean_link);
				$link->set('content',$request_content);
				$link->set('internal',true);
				
				array_push($links,$link);
			}
		}
}

$basePath = JPATH_ROOT.DS.'htmlstatic'.DS;
JFolder::create($basePath);
//now write files
foreach ($links as $link) {
	$fileDirectoryPath = JPath::clean(dirname($basePath.$link->file));
	$filePath = JPath::clean($basePath.$link->file);
	
	$itemLevel = count(explode('/',str_replace($basePath,$filePath,$link->file))) - 1;
	$baseUrlPath = str_replace($basePath, '', JFile::stripExt($filePath));
	$baseUrlPath = str_replace(DS,'/',$baseUrlPath);
	
	$file_content = proccessHtml($link,$links,$printLinks,$itemLevel,$baseUrlPath);
	
	JFolder::create($fileDirectoryPath);
	JFile::write($filePath,$file_content);
}

die('files created!');