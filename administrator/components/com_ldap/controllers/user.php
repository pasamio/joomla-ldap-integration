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
		$container = $template->container;
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
			$filter = $rdnn;
			$filters = array("($filter)");
			//$result = $ldap->simple_search($rdnn);
			$result = $ldap->search($filters, $container);
			$sync = true;
			if(count($result) == 1) {
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

		$this->setRedirect( 'index.php?option=com_ldap&c=user' );
		// Initialize variables
		$uid		= JRequest::getVar( 'uid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$n			= count( $uid );

		//get the currently enable LDAP template
		$params = &JComponentHelper::getParams( 'com_ldap' );

		$templateid = $params->get( 'templateid' );
		$db = &JFactory::getDBO();
		//fetch template details from the database
		$query = "select * from #__ldap_template where tid=".$templateid;
		$db->setQuery( $query);
		$template = $db->loadObject();
		$usercontainer = $template->container; //container
		$userdn = $template->userdn; //userdn format
		$rdn = $template->rdn; //rdn
		$attributes = $template->attributes; //attributes
		$objectclasses = $template->objectclasses; //objectclasses
		$configid = $template->configid;
		
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
		for ($i=0; $i < $n; $i++) {
			$user		=& JFactory::getUser($uid[$i]);
			//ldap user object
			$ldapuser = Array();
			$parts = split("\r\n",$attributes);//$attributes
			foreach($parts as $key=>$value) {
				$attrmap = split("=>", $value);
				if(!$attrmap[1] ) {
					continue;
				} else {
					$ldapuser[$attrmap[0]] = $user->$attrmap[1];
				}
			}
			//not mapped attributes
			//sn
			$parts = explode(' ',$user->name);
			$ldapuser['sn'] = array_pop($parts); // Get the last part, ensures we at least have a value for surname (req)
			//initials
			if(count($parts)) {
				$ldapuser['initials'] = implode(' ', $parts); // abuse this; outlook does the same
			}
			//userpassword
			if(!empty($user->password_clear)) $ldapuser['userpassword'] = Array($ldap->generatePassword($user->password_clear));

			//Objectclass
			$parts = split("\r\n",$objectclasses);//$objectclasses
			for($j = 0; $j<count($parts);$j++) {
				$ldapuser['objectclass'][$j] = $parts[$j];
			}

			//update dn
			$dnn = str_replace("[username]", $user->username, $userdn); // new userdn
			
			//new RDn
			$rdnn = str_replace("[username]", $user->username, $rdn);
			//search
			
			$filter = $rdnn;
			$filters = array("($filter)");
			//$result = $ldap->simple_search($rdnn);
			$result = $ldap->search($filters, $usercontainer);
			if(count($result) == 1) {
					
				$changed = $ldap->rename($dnn, $rdnn, null, true);// or die("Cannot rename");

					if($changed) {
					$uname = split('=',$rdnn);
					unset($ldapuser[$uname[0]]); // to make AD allow change the RDN
					unset($ldapuser['objectclass']); //AD doesn't allow modifying objectclasses
					if(!$ldap->replace($dnn, $ldapuser)){ // or die("Cannot Modify");
					JError::raiseWarning(44, JText::sprintf('LDAP Modify failed: %s', $ldap->getErrorMsg()));
					}
					}

			} else {
				if(!$this->_createUser($ldap, $dnn, $ldapuser))
				JError::raiseWarning(45, JText::sprintf('Failed to create user: %s', $ldap->getErrorMsg()));
			}
		}

		if (empty( $uid )) {
			return JError::raiseWarning( 500, JText::_( 'No items selected' ) );
		}

		JArrayHelper::toInteger( $uid );
		$uids = implode( ',', $uid );

		$this->setMessage( JText::sprintf( $n . ' Item(s) synched', $n ) );
	}

	function _createUser(&$ldap, $dn, $ldapuser) {
		if(!array_key_exists('userpassword', $ldapuser)) {
			jimport('joomla.user.helper'); // just in case
			$password = JUserHelper::genRandomPassword(32);
			$ldapuser['userpassword'] = $ldap->generatePassword($password); // set a dummy password
			//JError::raiseWarning(1,JText::sprintf('LDAP Password for user set to %s', $password));
		}
		return $ldap->create($dn,$ldapuser); // or die('Failed to add '. $dn .': ' . $ldap->getErrorMsg());
	}

}