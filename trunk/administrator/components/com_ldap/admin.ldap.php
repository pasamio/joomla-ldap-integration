<?php

defined( '_JEXEC' ) or die( 'Restricted access' );

// Set the table directory
JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_ldap'.DS.'tables');

$controllerName = JRequest::getCmd( 'c', 'config' );

switch($controllerName) {
	case 'template':
		JSubMenuHelper::addEntry(JText::_('Configurations'), 'index.php?option=com_ldap');
		JSubMenuHelper::addEntry(JText::_('Templates'), 'index.php?option=com_ldap&c=template', true );
		JSubMenuHelper::addEntry(JText::_('Users'), 'index.php?option=com_ldap&c=user');
		//JSubMenuHelper::addEntry(JText::_('Attribute Map'), 'index.php?option=com_temp&c=attributes');
		break;
	case 'user':
		JSubMenuHelper::addEntry(JText::_('Configurations'), 'index.php?option=com_ldap');
		JSubMenuHelper::addEntry(JText::_('Templates'), 'index.php?option=com_ldap&c=template');
		JSubMenuHelper::addEntry(JText::_('Users'), 'index.php?option=com_ldap&c=user', true );
		//JSubMenuHelper::addEntry(JText::_('Attribute Map'), 'index.php?option=com_temp&c=attributes');
		break;
	case 'attributes':
		JSubMenuHelper::addEntry(JText::_('Configurations'), 'index.php?option=com_ldap');
		JSubMenuHelper::addEntry(JText::_('Templates'), 'index.php?option=com_ldap&c=template');
		JSubMenuHelper::addEntry(JText::_('Users'), 'index.php?option=com_ldap&c=user');
		//JSubMenuHelper::addEntry(JText::_('Attribute Map'), 'index.php?option=com_temp&c=attributes', true );
		break;
	default:
		JSubMenuHelper::addEntry(JText::_('Configurations'), 'index.php?option=com_ldap', true);
		JSubMenuHelper::addEntry(JText::_('Templates'), 'index.php?option=com_ldap&c=template');
		JSubMenuHelper::addEntry(JText::_('Users'), 'index.php?option=com_ldap&c=user');
		//JSubMenuHelper::addEntry(JText::_('Attribute Map'), 'index.php?option=com_temp&c=attributes');
		
}

switch ($controllerName)
{
	default:
		$controllerName = 'config';
		// allow fall through

	case 'config' :
	case 'template':
		// Temporary interceptor
		$task = JRequest::getCmd('task');
		if ($task == 'listtemplates') {
			$controllerName = 'template';
		}

		require_once( JPATH_COMPONENT.DS.'controllers'.DS.$controllerName.'.php' );
		$controllerName = 'LdapController'.$controllerName;

		// Create the controller
		$controller = new $controllerName();

		// Perform the Request task
		$controller->execute( JRequest::getCmd('task') );

		// Redirect if set by the controller
		$controller->redirect();
		break;
	case 'user':
		break;
	case 'attributes':
		// Temporary interceptor
		$task = JRequest::getCmd('task');
		if ($task == 'listattributes') {
			$controllerName = 'attributes';
		}

		require_once( JPATH_COMPONENT.DS.'controllers'.DS.$controllerName.'.php' );
		$controllerName = 'LdapController'.$controllerName;

		// Create the controller
		$controller = new $controllerName();

		// Perform the Request task
		$controller->execute( JRequest::getCmd('task') );

		// Redirect if set by the controller
		$controller->redirect();
		break;
}