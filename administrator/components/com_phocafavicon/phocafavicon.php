<?php
/*
 * @package		Joomla.Framework
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @component Phoca Component
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License version 2 or later;
 */
defined('_JEXEC') or die;
if (!JFactory::getUser()->authorise('core.manage', 'com_phocafavicon')) {
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}
// Require the base controller and helpers
require_once( JPATH_COMPONENT.DS.'controller.php' );
require_once( JPATH_COMPONENT.DS.'helpers'.DS.'phocafavicon.php' );
require_once( JPATH_COMPONENT.DS.'helpers'.DS.'phocafaviconico.php' );
require_once( JPATH_COMPONENT.DS.'helpers'.DS.'phocafaviconcp.php' );
require_once( JPATH_COMPONENT.DS.'helpers'.DS.'phocafaviconfileupload.php' );

jimport('joomla.application.component.controller');

$controller	= JController::getInstance('PhocaFaviconCp');

$controller->execute(JRequest::getCmd('task'));

$controller->redirect();

?>