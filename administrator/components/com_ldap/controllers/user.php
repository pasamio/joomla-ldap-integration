<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );
jimport('joomla.client.ldap');


class LdapControllerUser extends JController
{
	/**
	 * Constructor
	 */
	function __construct( $config = array() )
	{
		parent::__construct( $config );
		$this->registerTask('unpublish','publish' );
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
		. ' FROM #__users'
		. $where
		;
		$db->setQuery( $query );
		$total = $db->loadResult();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit );
		//get all joomla users
		$query = 'SELECT * '
		. ' FROM #__users '
		;
		$db->setQuery( $query, $pageNav->limitstart, $pageNav->limit );
		$rows = $db->loadObjectList();
		//get all LDAP users
		//get the currently enable LDAP template
		$params = &JComponentHelper::getParams( 'com_ldap' );

		$templateid = $params->get( 'templateid' );
		//fetch template details from the database
		$query = "select * from #__ldap_template where tid=".$templateid;
		$db->setQuery( $query);
		$template = $db->loadObject();
		$rdn = $template->rdn;
		$containter = $template->container;
		$configid = $template->configid;
		$attributes = $template->attributes;
		//fetch ldap configuration from database;
		$query = "select * from #__ldap_config where id=".$configid;
		$db->setQuery( $query);
		$config = $db->loadObject();
		
		$params->set('host', $config->host); //hostname
		$params->set('port', $config->port); //port
		$params->set('use_ldapV3', $config->version3); //varsion3
		$params->set('negotiate_tls', $config->negotiate_tls); //varsion3
		$params->set('no_referrals', $config->follow_referrals); //no referrals
		$params->set('base_dn', $config->basedn); //base_dn
		$params->set('username', $config->connect_username); //username
		$params->set('password', $config->connect_password); //password
		$ldap =  new JLDAP($params);
		if(!$ldap->connect()) {
			JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
			return false;
		}
		if(!$ldap->bind()) {
			JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
			return false;
		}
		//search users in LDAP Directory
		$lists = array();
		for ($i=0, $n=count( $rows ); $i < $n; $i++) {
			$row 			= &$rows[$i];
			$rdnn = str_replace("[username]", $row->username, $rdn);
			$result = $ldap->simple_search($rdnn);
			$sync = true;
			if(count($result) == 1) {
				//JError::raiseWarning(40, JText::_($rdnn . ' found in LDAP'));
				//$lists[$row->id] = $result[0]['dn'];
				$this->__checkUserSync($ldap,$result[0], $row->id, $attributes, $lists);
				
				foreach($lists[$row->id] as $key => $value){
					$sync = $sync && $value;
				}
				$lists[$row->id]['insync'] = $sync;
				$lists[$row->id]['dn'] = $result[0]['dn'];

			} else {
				$lists[$row->id]['insync'] = false;
				$lists[$row->id]['dn'] = "<b><i>Not in LDAP</i></b>";
			}
		}		
		
		
		require_once(JPATH_COMPONENT.DS.'views'.DS.'user.php');
		LdapViewUser::users( $rows, $pageNav, $lists );
	}	
	function &__checkUserSync(&$ldap, $ldapuser, $userid, $attributes, &$lists) {
		$user =& JFactory::getUser( $userid );
		//mappable attributes
		$parts = split("\r\n",$attributes);//$attributes
		foreach($parts as $key=>$value) {
			$attrmap = split("=>", $value);
		
			if(!$attrmap[1] ) {
				continue;
			} else {
				if($ldapuser[$attrmap[0]][0] == $user->$attrmap[1])
				{
					$lists[$userid][$attrmap[0]] = true;					 
				} else {
					$lists[$userid][$attrmap[0]] = false; //can capture the details if required
				}
			}
		}
		return $lists; 

	}
		
	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap' );

		// Initialize variables
		$db			=& JFactory::getDBO();
		$user		=& JFactory::getUser();
		$cid		= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$sync	= ($task == 'sync');
		$n			= count( $cid );

		if (empty( $cid )) {
			return JError::raiseWarning( 500, JText::_( 'No items selected' ) );
		}

		JArrayHelper::toInteger( $cid );
		$cids = implode( ',', $cid );

		$db->setQuery( $query );
		if (!$db->query()) {
			return JError::raiseWarning( 500, $db->getError() );
		}
		$this->setMessage( JText::sprintf( $publish ? 'Items published' : 'Items unpublished', $n ) );
	}
	
}
