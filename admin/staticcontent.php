<?php
// Include dependancies
jimport('joomla.application.component.controller');

require_once 'controller.php';

$controller	= JController::getInstance('StaticContent');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();