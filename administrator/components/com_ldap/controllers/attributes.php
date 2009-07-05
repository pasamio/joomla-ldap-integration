<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );


class LdapControllerAttributes extends JController
{
	/**
	 * Constructor
	 */
	function __construct( $config = array() )
	{
		parent::__construct( $config );
		// Register Extra tasks
		//$this->registerTask( 'add',			'edit' );
		$this->registerTask( 'apply',		'save' );
	}

	/**
	 * Display the list of configurations
	 */
	function display()
	{
		global $mainframe;

		$params = &JComponentHelper::getParams( 'com_ldap' );

		$templateid = $params->get( 'templateid' );

		//echo "template id : " .$rows;
		$db =& JFactory::getDBO();
		$query = "select * from #__ldap_template where tid=".$templateid;
		$db->setQuery( $query);
		$list = array();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'attributes.php');
		$rows = $db->loadObject(); //attribute row from #__ldap_template
		//insert this into #__ldap_template_attribute_map
		$parts = split("\r\n",$rows->attributes);
		$query = 'insert into #__ldap_template_attribute_map(template_id, attribute) values'; //first time inserts === check for the alternative
		for($i = 0; $i<count($parts)-2; $i++) {
			$query .= '(' . $templateid.', \''. $parts[$i] .'\' ), ';
		}
		$query .= '(' . $templateid.', \''. $parts[$i] .'\') ';
			
		$db->setQuery( $query );
		if (!$db->query()) {
			//JError::raiseWarning( 500, $db->getError() );
		}
		//get the results for parameter display
		$query = "select * from #__ldap_template_attribute_map where template_id=".$templateid;
		$db->setQuery( $query);
		$rows = $db->loadObjectList();
		$lists = array();
		$user = JFactory::getUser(0);
		$arr = array();
		$i=1;
	
		$arr     = array(  JHTML::_('select.option',  '', '- '. JText::_( 'Select Param' ) .' -' ) );

		foreach($user as $key=>$value) {
				$arr[] = JHTML::_('select.option',  $key );
		}
	
		LdapViewAttributes::attributes( $rows, $pageNav, $arr );
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


		require_once(JPATH_COMPONENT.DS.'views'.DS.'attributes.php');
		

		LdapViewAttributes::attribute( $row, $lists );
		}
		

	/**
	 * Save method
	 */

	
	 function save()
	 {
		global $mainframe;

		$this->setRedirect( 'index.php?option=com_ldap' );

		// Initialize variables
		$db =& JFactory::getDBO();

		$post	= JRequest::get( 'post' );

		$row =& JTable::getInstance('attributemap', 'Table');

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
		$link = 'index.php?option=com_ldap&c=attributes';
		break;

		case 'save':
		default:
		$link = 'index.php?option=com_ldap';
		break;
		}

		$this->setRedirect( $link, JText::_( 'Param Saved' ) );
		}

		function cancel()
		{

		$this->setRedirect( 'index.php?option=com_ldap' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$post	= JRequest::get( 'post' );
		$row	=& JTable::getInstance('attributemap', 'Table');
		$row->bind( $post );
		$row->checkin();
		}
		
}

