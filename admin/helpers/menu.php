<?php
class ComStaticContentHelperMenu
{
	public static function getLink(&$item)
	{
		switch ($item->type)
		{
			case 'separator':
				// No further action needed.
				continue;
			case 'component':
				$item->flink = $item->link.'&Itemid='.$item->id;
				break;
				
			case 'url':
				if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
					// If this is an internal Joomla link, ensure the Itemid is set.
					$item->flink = $item->link.'&Itemid='.$item->id;
				}
				break;
	
			case 'alias':
				// If this is an alias use the item id stored in the parameters to make the link.
				$citem = JMenu::getInstance('site')->getItem($item->params->get('aliasoptions'));
				self::getLink($citem);
				$item = $citem;
				return;
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
}