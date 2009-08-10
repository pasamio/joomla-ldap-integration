<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );
jimport('joomla.client.ldap');


class LdapControllerUserGroup extends JController
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
		$params = &JComponentHelper::getParams( 'com_ldap' );
		$context = 'com_ldap.user.list.';

		$filter_order		= $mainframe->getUserStateFromRequest( $context.'filter_order',		'filter_order',		'cc.groupname',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',			'word' );
		$filter_groupid		= $mainframe->getUserStateFromRequest( $context.'filter_groupid',		'filter_groupid',	'',	'int' );
		$limit		= $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart = $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );

		$where = array();
		if ($filter_groupid) {
			$where[] = 'cc.groupid = ' . (int) $filter_groupid;
		}


		$where		= count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir;
		// get the total number of records
		$query = 'SELECT COUNT(*)'
		. ' FROM #__users as u'
		. ' LEFT JOIN #__ldap_user_groups AS cc ON cc.uid = u.id'
		. $where
		;
		$db->setQuery( $query );
		$total = $db->loadResult();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit );

		$query = 'SELECT u.* FROM #__users AS u';
		$db->setQuery( $query, $pageNav->limitstart, $pageNav->limit );
		$rows = $db->loadObjectList();

		// 	Build groups select list

		$sql = 'SELECT groupname, groupid '
		. ' FROM #__ldap_group'
		;
		$db->setQuery($sql);
		if (!$db->query())
		{
			$this->setRedirect( 'index.php?option=com_ldap&c=usergroup' );
			return JError::raiseWarning( 500, $db->getErrorMsg() );
		}

		$lists = array();

		$types[] 		= JHTML::_('select.option',  '0', '- '. JText::_( 'Select Group' ) .' -' );
		foreach( $db->loadObjectList() as $obj )
		{
			$types[] = JHTML::_('select.option',  $obj->groupid, JText::_( $obj->groupname ) );
		}
		$lists['groupid'] 	= JHTML::_('select.genericlist',   $types, 'groupid', 'class="inputbox" size="1" ', 'value', 'text', "groupid" );

		// state filter
		$lists['state']	= JHTML::_('grid.state',  $filter_state );

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;

		// search filter
		$lists['search']= $search;

		//get the the uid and groupid mappings
		$query2 = 'select lg.*, g.groupname from #__ldap_user_groups as lg left join #__ldap_group as g on g.groupid = lg.groupid';
		$db->setQuery($query2);
		if (!$db->query())
		{
			$this->setRedirect( 'index.php?option=com_ldap&c=usergroup' );
			return JError::raiseWarning( 500, $db->getErrorMsg() );
		}
		$rows2 = $db->loadObjectList();

		$k = 0;
		foreach($rows2 as $row) {
		
			if($lists['ugroups'][$row->uid]) {
				$lists['ugroups'][$row->uid][$k]['gid'] = $row->groupid;
				$lists['ugroups'][$row->uid][$k]['gname'] = $row->groupname;
				//get groupdn and userdn
				$lquery = 'SELECT config.*, t.groupname, template.configid, template.rdn AS userrdn,template.attributes,template.objectclasses, gp.rdn AS grouprdn, gp.container AS gcontainer'
				. ' FROM #__ldap_group AS t INNER JOIN #__ldap_template AS  t2'
				. ' INNER JOIN #__ldap_template AS gp ON gp.tid=t.gtemplateid'
				. ' INNER JOIN #__ldap_template AS template ON template.tid= t.utemplateid'
				. ' INNER JOIN #__ldap_config AS config'
				. ' WHERE groupid='.$row->groupid.' && t.utemplateid=t2.tid && gp.configid=config.id'
				;
				$db->setQuery( $lquery);
				$template = $db->loadObject();
				$rdn = $template->userrdn; //user RDN
				//$configid = $template->configid;
				$attributes = $template->attributes;
				$objectclasses = $template->objectclasses;
				$groupname = $template->groupname; //LDAP Groupname

				//update userdn accorrdingly
				$container = str_replace("[groupname]", $template->groupname, $template->grouprdn) .','. $template->gcontainer;
				$userdn = $rdn .','.$container;

				$params->set('host', $template->host); //hostname
				$params->set('port', $template->port); //port
				$params->set('use_ldapV3', $template->version3); //varsion3
				$params->set('negotiate_tls', $template->negotiate_tls); //varsion3
				$params->set('no_referrals', $template->follow_referrals); //no referrals
				$params->set('base_dn', $template->basedn); //base_dn
				$params->set('username', $template->connect_username); //username
				$params->set('password', $template->connect_password); //password
				$ldap =  new JLDAP($params);
				if(!$ldap->connect()) {
					JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
					return false;
				}
				if(!$ldap->bind()) {
					JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
					return false;
				}
				$user =& JFactory::getUser( $row->uid );
				$rdnn = str_replace("[username]", $user->username, $rdn);
				$userdn = $rdnn .','.$container;
	
					
				$filters = array("(objectclass=*)");
				$result = $ldap->search($filters,$container);
				$n = 0;
				for($i = 1, $m = count($result);$i < $m; $i++ ) {
					if($result[$i]['dn'] == $userdn) {
						$lists['ugroups'][$row->uid][$k]['dn'] = $result[$i]['dn'];
						//$lists['inldap'][$n] = $result[$i]['dn']; $n++;
					} //else
				}
				$k++;
			} else {
				$k = 0;
				$lists['ugroups'][$row->uid][$k]['gid'] = $row->groupid;
				$lists['ugroups'][$row->uid][$k]['gname'] = $row->groupname;
				//get groupdn and userdn
				$lquery = 'SELECT config.*, t.groupname, template.configid, template.rdn AS userrdn,template.attributes,template.objectclasses, gp.rdn AS grouprdn, gp.container AS gcontainer'
				. ' FROM #__ldap_group AS t INNER JOIN #__ldap_template AS  t2'
				. ' INNER JOIN #__ldap_template AS gp ON gp.tid=t.gtemplateid'
				. ' INNER JOIN #__ldap_template AS template ON template.tid= t.utemplateid'
				. ' INNER JOIN #__ldap_config AS config'
				. ' WHERE groupid='.$row->groupid.' && t.utemplateid=t2.tid && gp.configid=config.id'
				;
				$db->setQuery( $lquery);
				$template = $db->loadObject();
				$rdn = $template->userrdn; //user RDN
				$attributes = $template->attributes;
				$objectclasses = $template->objectclasses;
				$groupname = $template->groupname; //LDAP Groupname

				//update userdn accorrdingly
				$container = str_replace("[groupname]", $template->groupname, $template->grouprdn) .','. $template->gcontainer;
				$userdn = $rdn .','.$container;

				$params->set('host', $template->host); //hostname
				$params->set('port', $template->port); //port
				$params->set('use_ldapV3', $template->version3); //varsion3
				$params->set('negotiate_tls', $template->negotiate_tls); //varsion3
				$params->set('no_referrals', $template->follow_referrals); //no referrals
				$params->set('base_dn', $template->basedn); //base_dn
				$params->set('username', $template->connect_username); //username
				$params->set('password', $template->connect_password); //password
				$ldap =  new JLDAP($params);
				if(!$ldap->connect()) {
					JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
					return false;
				}
				if(!$ldap->bind()) {
					JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
					return false;
				}
				$user =& JFactory::getUser( $row->uid );
				$rdnn = str_replace("[username]", $user->username, $rdn);
				//$userdnn = array();
				$userdn = $rdnn .','.$container;

				$filters = array("(objectclass=*)");
				$result = $ldap->search($filters,$container);
				$n = 0;
				for($i = 1, $m = count($result);$i < $m; $i++ ) {
					if($result[$i]['dn'] == $userdn) {
						//JError::raiseWarning(40, JText::_($userdn. 'present'));
						$lists['ugroups'][$row->uid][$k]['dn'] = $result[$i]['dn'];
						//$lists['inldap'][$n] = $result[$i]['dn']; $n++;
					} 
				}
	
				$k++;
			}
		}

		require_once(JPATH_COMPONENT.DS.'views'.DS.'usergroup.php');
		LdapViewUserGroup::users( $rows, $pageNav, $lists );

	}

	//Set group to the user
	function setgroup() {
		JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap&c=usergroup' );
		// Initialize variables
		$uid		= JRequest::getVar( 'uid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$n			= count( $uid );

		$params = &JComponentHelper::getParams( 'com_ldap' );
		$db		=& JFactory::getDBO();
		JArrayHelper::toInteger( $uid );
		$groupid		= JRequest::getVar( 'groupid', array(), 'post', 'array' );
		if($groupid[0] == 0) {
			JError::raiseWarning(1,JText::sprintf('Select a group.', 'Select a group.'));
			$this->setRedirect( 'index.php?option=com_ldap&c=usergroup' );
		}
		if ($n && $groupid[0] != 0)
		{
			
			$lquery = 'SELECT config.*, t.groupname, template.configid, template.rdn AS userrdn,template.attributes,template.objectclasses, gp.rdn AS grouprdn, gp.container AS gcontainer'
			. ' FROM #__ldap_group AS t INNER JOIN #__ldap_template AS  t2'
			. ' INNER JOIN #__ldap_template AS gp ON gp.tid=t.gtemplateid'
			. ' INNER JOIN #__ldap_template AS template ON template.tid= t.utemplateid'
			. ' INNER JOIN #__ldap_config AS config'
			. ' WHERE groupid='.$groupid[0].' && t.utemplateid=t2.tid && gp.configid=config.id'
			;
		 $db->setQuery( $lquery);
		 $template = $db->loadObject();
		 $rdn = $template->userrdn; //user RDN
		 $attributes = $template->attributes;
		 $objectclasses = $template->objectclasses;
		 $groupname = $template->groupname; //LDAP Groupname

		 //update userdn accorrdingly
		 $container = str_replace("[groupname]", $template->groupname, $template->grouprdn) .','. $template->gcontainer;
		 $userdn = $rdn .','.$container;

		 $params->set('host', $template->host); //hostname
		 $params->set('port', $template->port); //port
		 $params->set('use_ldapV3', $template->version3); //varsion3
		 $params->set('negotiate_tls', $template->negotiate_tls); //varsion3
		 $params->set('no_referrals', $template->follow_referrals); //no referrals
		 $params->set('base_dn', $template->basedn); //base_dn
		 $params->set('username', $template->connect_username); //username
		 $params->set('password', $template->connect_password); //password
		 $ldap =  new JLDAP($params);
		 if(!$ldap->connect()) {
		 	JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
		 	return false;
		 }
		 if(!$ldap->bind()) {
		 	JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
		 	return false;
		 }
	
		 foreach($uid as  $key=>$value)
		 {

		 	$query = 'insert into #__ldap_user_groups(uid,groupid)'
		 	. ' values('. $value.','.$groupid[0].')'
		 	;
		 	$db->setQuery( $query );
		 	if (!$db->query()) {
		 		JError::raiseWarning( 500, $db->getError() );
		 	}
		 	//LDAP things
		 	$user		=& JFactory::getUser($value);
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
		 	$filters = array("(objectclass=*)");
		 	$result = $ldap->search($filters,$container);
	
				$filters = array("($rdnn)");
				$result = $ldap->search($filters,$container);
				JError::raiseWarning(44, JText::sprintf($rdnn .'--' . count($result) .'==' . $dnn .' =  %s', $container));
				//$n = 0;
				for($i = 1, $m = count($result);$i <= $m; $i++ ) {
					if($result[$i]['dn'] == $dnn) {
						JError::raiseWarning(40, JText::_($dnn. ' already present.'));
							
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
						JError::raiseWarning(40, JText::_($dnn. ' In else part .'));
						if(!$this->_createUser($ldap, $dnn, $ldapuser))
						JError::raiseWarning(45, JText::sprintf('Failed to create user: %s', $ldap->getErrorMsg()));
					}
				}
		 }
		}
		if($groupid[0] != 0)
		$this->setMessage( JText::sprintf($n.' Group Set.', $n ) );
	}


	function _createUser(&$ldap, $dn, $ldapuser) {

		if(!array_key_exists('userpassword', $ldapuser)) {
			jimport('joomla.user.helper'); // just in case
			$password = JUserHelper::genRandomPassword(32);
			$ldapuser['userpassword'] = $ldap->generatePassword($password); // set a dummy password
		}
		return $ldap->create($dn,$ldapuser); // or die('Failed to add '. $dn .': ' . $ldap->getErrorMsg());
	}

	//Unset group of the user
	function unsetgroup() {
		JRequest::checkToken() or jexit( 'Invalid Token' );
		$params = &JComponentHelper::getParams( 'com_ldap' );
		$this->setRedirect( 'index.php?option=com_ldap&c=usergroup' );
		// Initialize variables
		$uid		= JRequest::getVar( 'uid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$n			= count( $uid );

		$db		=& JFactory::getDBO();
		JArrayHelper::toInteger( $uid );
		$groupid		= JRequest::getVar( 'groupid', array(), 'post', 'array' );
		if($groupid[0] == 0) {
			JError::raiseWarning(1,JText::sprintf('Select a group.', 'Select a group.'));
			$this->setRedirect( 'index.php?option=com_ldap&c=usergroup' );
		}
		if ($n && $groupid[0] != 0)
		{
			$lquery = 'SELECT config.*, t.groupname, template.configid, template.rdn AS userrdn,template.attributes,template.objectclasses, gp.rdn AS grouprdn, gp.container AS gcontainer'
			. ' FROM #__ldap_group AS t INNER JOIN #__ldap_template AS  t2'
			. ' INNER JOIN #__ldap_template AS gp ON gp.tid=t.gtemplateid'
			. ' INNER JOIN #__ldap_template AS template ON template.tid= t.utemplateid'
			. ' INNER JOIN #__ldap_config AS config'
			. ' WHERE groupid='.$groupid[0].' && t.utemplateid=t2.tid && gp.configid=config.id'
			;
		 $db->setQuery( $lquery);
		 $template = $db->loadObject();
		 $rdn = $template->userrdn; //user RDN
		 //$configid = $template->configid;
		 $attributes = $template->attributes;
		 $objectclasses = $template->objectclasses;
		 $groupname = $template->groupname; //LDAP Groupname

		 //update userdn accorrdingly
		 $container = str_replace("[groupname]", $template->groupname, $template->grouprdn) .','. $template->gcontainer;
		 $userdn = $rdn .','.$container;

		 $params->set('host', $template->host); //hostname
		 $params->set('port', $template->port); //port
		 $params->set('use_ldapV3', $template->version3); //varsion3
		 $params->set('negotiate_tls', $template->negotiate_tls); //varsion3
		 $params->set('no_referrals', $template->follow_referrals); //no referrals
		 $params->set('base_dn', $template->basedn); //base_dn
		 $params->set('username', $template->connect_username); //username
		 $params->set('password', $template->connect_password); //password
		 $ldap =  new JLDAP($params);
		 if(!$ldap->connect()) {
		 	JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
		 	return false;
		 }
		 if(!$ldap->bind()) {
		 	JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
		 	return false;
		 }
			foreach($uid as  $key=>$value)
			{

				$query = 'delete from #__ldap_user_groups'
				. ' where uid=' .$value . ' and groupid='.$groupid[0]
				;
				$db->setQuery( $query );
				if (!$db->query()) {
					JError::raiseWarning( 500, $db->getError() );
				}
				//also delete from the LDAP
				$user		=& JFactory::getUser($value);
				$dn = str_replace("[username]", $user->username, $rdn);
				$filter = $dn;
		 	//$filters = array("($filter)");
		 	$filters = array("($filter)");
		 	JError::raiseWarning(41, JText::_($filter . '<->' . $container));
		 	$result = $ldap->search($filters,$container);
		 	//First Remove All Children
		 	$app =& JFactory::getApplication();
		 	if(count($result) == 1 ) {
		 		$ldap->delete($result[0]['dn']) or die('Failed to delete: ' . $result[$i]['dn']);
		 	} else if(count($result) == 0 ) {
		 		JError::raiseWarning(41, JText::_('User not found in LDAP'));
		 	}
		
				
			}
		}
		if($groupid[0] != 0)
		$this->setMessage( JText::sprintf(' Group Unset.' ) );
	}

}
