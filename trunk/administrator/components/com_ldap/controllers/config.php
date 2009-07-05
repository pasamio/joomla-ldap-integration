<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );


class LdapControllerConfig extends JController
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

		$db =& JFactory::getDBO();
		$limit		= $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart = $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );

		$where = array();
		// get the total number of records
		$query = 'SELECT COUNT(*)'
		. ' FROM #__ldap_config'
		. $where
		;
		$db->setQuery( $query );
		$total = $db->loadResult();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit );

		$query = 'SELECT * '
		. ' FROM #__ldap_config '
		;
		$db->setQuery( $query, $pageNav->limitstart, $pageNav->limit );
		$rows = $db->loadObjectList();
		$lists = array();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'config.php');
		LdapViewConfig::configs( $rows, $pageNav, $lists );
	}

	function edit()
	{
		$db		=& JFactory::getDBO();

		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array( 0 );
		}

		$option = JRequest::getCmd('option');

		$lists = array();

		$row =& JTable::getInstance('config', 'Table');
		$row->load( $cid[0] );

		$lists['version3'] = JHTML::_('select.booleanlist', 'version3', 'class="inputbox"', $row->version3);
		$lists['negotiate_tls'] = JHTML::_('select.booleanlist', 'negotiate_tls', 'class="inputbox"', $row->negotiate_tls);
		$lists['follow_referrals'] = JHTML::_('select.booleanlist', 'follow_referrals', 'class="inputbox"', $row->follow_referrals);
		require_once(JPATH_COMPONENT.DS.'views'.DS.'config.php');
		LdapViewConfig::config( $row, $lists );
	}
	/**
	 * Save method
	 */
	function save()
	{
		global $mainframe;

		$this->setRedirect( 'index.php?option=com_temp' );

		// Initialize variables
		$db =& JFactory::getDBO();

		$post	= JRequest::get( 'post' );

		$row =& JTable::getInstance('config', 'Table');

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
				$link = 'index.php?option=com_ldap&task=edit&cid[]='. $row->id;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_ldap';
				break;
		}

		$this->setRedirect( $link, JText::_( 'Item Saved' ) );
	}

	function cancel()
	{

		$this->setRedirect( 'index.php?option=com_ldap' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$post	= JRequest::get( 'post' );
		$row	=& JTable::getInstance('config', 'Table');
		$row->bind( $post );
		$row->checkin();
	}
	function remove()
	{

		$this->setRedirect( 'index.php?option=com_ldap' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$n		= count( $cid );
		JArrayHelper::toInteger( $cid );

		if ($n)
		{
			$query = 'DELETE FROM #__ldap_config'
			. ' WHERE id = ' . implode( ' OR id = ', $cid )
			;
			$db->setQuery( $query );
			if (!$db->query()) {
				JError::raiseWarning( 500, $db->getError() );
			}
		}

		$this->setMessage( JText::sprintf( 'Items removed', $n ) );
	}

}
