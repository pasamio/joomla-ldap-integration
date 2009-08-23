<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );


class LdapControllerTemplate extends JController
{
	/**
	 * Constructor
	 */
	function __construct( $config = array() )
	{
		parent::__construct( $config );
		// Register Extra tasks
		$this->registerTask( 'add',			'edit' );
		$this->registerTask( 'apply',		'save' );
	}

	/**
	 * Display the list of configurations
	 */
	function display()
	{
		global $mainframe;
		$context = '';
		$db =& JFactory::getDBO();
		$limit		= $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart = $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );

		$where = array();
		// get the total number of records
		$query = 'SELECT COUNT(*)'
		. ' FROM #__ldap_template'
		. $where
		;
		$db->setQuery( $query );
		$total = $db->loadResult();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit );

		$query = 'SELECT * '
		. ' FROM #__ldap_template '
		;
		$db->setQuery( $query, $pageNav->limitstart, $pageNav->limit );
		$rows = $db->loadObjectList();
		$lists = array();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'template.php');
		LdapViewTemplate::templates( $rows, $pageNav, $lists );
	}
	
	function edit()
	{
		$db		=& JFactory::getDBO();
		//$user	=& JFactory::getUser();

		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array( 0 );
		}

		$option = JRequest::getCmd('option');

		$lists = array();

		$row =& JTable::getInstance('template', 'Table');
		$row->load( $cid[0] );

		// 	Build Configuration select list
		$sql = 'SELECT id as configid, name'
		. ' FROM #__ldap_config'
		;
		$db->setQuery($sql);
		if (!$db->query())
		{
			$this->setRedirect( 'index.php?option=com_ldap&c=template' );
			return JError::raiseWarning( 500, $db->getErrorMsg() );
		}
		$configlist[]		= JHTML::_('select.option',  '0', JText::_( 'Select LDAP Configuration' ), 'configid', 'name' );
		$configlist			= array_merge( $configlist, $db->loadObjectList() );
		$lists['configid']		= JHTML::_('select.genericlist',   $configlist, 'configid', 'class="inputbox" size="1"','configid', 'name', $row->configid );
		require_once(JPATH_COMPONENT.DS.'views'.DS.'template.php');
		LdapViewTemplate::template( $row, $lists );
	}
	/**
	 * Save method
	 */
	function save()
	{
		global $mainframe;

		// Check for request forgeries
		//JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap&c=template' );

		// Initialize variables
		$db =& JFactory::getDBO();

		$post	= JRequest::get( 'post' );
		$row =& JTable::getInstance('template', 'Table');

		if (!$row->bind( $post )) {
			return JError::raiseWarning( 500, $row->getError() );
		}
		if (!$row->check()) {
			return JError::raiseWarning( 500, $row->getError() );
		}

		if (!$row->store()) {
			return JError::raiseWarning( 500, $row->getError() );
		}
		$row->checkin();
		$task = JRequest::getCmd( 'task' );
		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_ldap&c=template&task=edit&cid[]='. $row->tid;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_ldap&c=template';
				break;
		}

		$this->setRedirect( $link, JText::_( 'Item Saved' ) );
	}

	function cancel()
	{
		// Check for request forgeries
		//JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap&c=template' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$post	= JRequest::get( 'post' );
		$row	=& JTable::getInstance('template', 'Table');
		$row->bind( $post );
		$row->checkin();
	}
	function remove()
	{
		// Check for request forgeries
		//JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap&c=template' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$n		= count( $cid );
		JArrayHelper::toInteger( $cid );

		if ($n)
		{
			$query = 'DELETE FROM #__ldap_template'
			. ' WHERE tid = ' . implode( ' OR tid = ', $cid )
			;
			$db->setQuery( $query );
			if (!$db->query()) {
				JError::raiseWarning( 500, $db->getError() );
			}
		}

		$this->setMessage( JText::sprintf( 'Items removed', $n ) );
	}
	
}
