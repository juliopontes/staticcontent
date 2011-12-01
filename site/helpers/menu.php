<?php
abstract class StaticContentHelperMenu
{
	/**
	 * List of links from all menu items
	 * 
	 * @var array
	 */
	static $links;
	
	/**
	 * Register menu links links
	 * 
	 * @param array $links
	 */
	static public function setLinks($links)
	{
		self::$links = $links;
	}
	
	/**
	 * Check type of menuitem
	 * 
	 * @param Object $menu
	 */
	static public function validType($menu)
	{
		$valid = ($menu->type == 'url' || $menu->type == 'separator') ? false : true ;
		
		return $valid;
	}
	
	/**
	 * Build a menu link
	 * 
	 * @param object $item
	 */
	static public function buildLink($item)
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
	
	/**
	 * Check if guest can access menu item
	 * 
	 * @param object $menuItem
	 * @return boolean TRUE if guest can access
	 */
	static public function guestCanAccess($menuItem)
	{
		$app 	= JApplication::getInstance('site');
		$menu 	= $app->getMenu('site')->getItem($menuItem->id);
		$isInternal = JURI::isInternal($menuItem->flink);
		$canAccess	= (is_object($menu)) ? in_array((int) $menu->access, JFactory::getUser(0)->getAuthorisedViewLevels()) : false ;
		
		if (($isInternal && $canAccess) || $menuItem->type ='url') {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Check for satifatory links, return 
	 * 
	 * @param string $link
	 * @return boolean TRUE for internal links pages that not have in menu items
	 */
	static public function isSatisfatoryLink($link)
	{
		$isAnchor			= (strpos($link,'#') !== false);
		$isJavascript		= (strpos($link,'javascript') !== false);
		$isHome				= (empty($link) || (JURI::base(true) == $link) || (JURI::root() == $link));
		$isExternal			= !JURI::isInternal($link);
		$isAdministrator	= ($link == JURI::root().'administrator');
		$existsInMenu		= self::existsInMenu($link);
		$isWeblink 			= (strpos($link,'task=weblink.go') !== false);
		$isMailto			= (strpos($link,'component/mailto') !== false);
		$isBannerLink 		= (strpos($link,'component/banners') !== false);
		
		if ($isAnchor || $isJavascript || $isHome || $isExternal || $existsInMenu || $isAdministrator || $isWeblink || $isBannerLink || $isMailto) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check if URL exists in menu links
	 * 
	 * @param string $link
	 * @return boolean TRUE if url exists in menu items
	 */
	static public function existsInMenu($link)
	{
		$isInternalLink = (array_search($link, self::$links) !== false) ? true : false ;
		return $isInternalLink;
	}
}