<?php
/**
 * @version 2.0
 * @package com_staticcontent
 * @copyright Copyright (C) 2013. All rights reserved.
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * @author Júlio Pontes <juliopfneto@gmail.com>
 */

// no direct access
defined('_JEXEC') or die;

$option = basename(__FILE__);
$name = str_replace('com_', '', $option);
// Include dependencies
$controllerPrefix = ucfirst($name);
// Autoload component files
JLoader::registerPrefix($controllerPrefix, JPATH_COMPONENT);

$controller	= JControllerLegacy::getInstance($controllerPrefix);
$controller->execute(JFactory::getApplication()->input->getCmd('task'));
$controller->redirect();