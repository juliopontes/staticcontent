<?php

/**
 * @package     Static Content Component
 * @author      Julio Pontes - juliopfneto at gmail.com - juliopontes
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 3. See license.txt
 */
defined('_JEXEC') or die('Restricted access');

// Include dependancies
if (JVERSION >= '3.0')
{
	jimport('legacy.model.legacy');
	jimport('legacy.controller.legacy');
	jimport('legacy.view.legacy');

	class_alias('JControllerLegacy', 'Controller');
	class_alias('JModelLegacy', 'Model');
	class_alias('JViewLegacy', 'View');
	
}
else
{
	jimport('joomla.application.component.controller');
	jimport('joomla.application.component.model');
	jimport('joomla.application.component.view');
	
	class_alias('JController', 'Controller');
	class_alias('JModel', 'Model');
	class_alias('JView', 'View');
	
	JFactory::getDocument()->addScript('components/com_staticcontent/js/jquery-1.9.1.min.js');
}
//import filesystem

jimport('joomla.filesystem.path');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

require_once 'controller.php';

$controller = Controller::getInstance('StaticContent');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();