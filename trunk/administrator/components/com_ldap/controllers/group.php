<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.application.component.controller' );
jimport('joomla.client.ldap');


class LdapControllerGroup extends JController
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
		$params = &JComponentHelper::getParams( 'com_ldap' );
		$db =& JFactory::getDBO();
		$limit		= $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart = $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );

		$where = array();
		// get the total number of records
		$query = 'SELECT COUNT(*)'
		. ' FROM #__ldap_group'
		. $where
		;
		$db->setQuery( $query );
		$total = $db->loadResult();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit );

		$query = 'SELECT * '
		. ' FROM #__ldap_group '
		;
		$db->setQuery( $query, $pageNav->limitstart, $pageNav->limit );
		$rows = $db->loadObjectList();
		$groups = $rows;
		//iterate over number of groups
		$lists = array();
		//group according to the Group Template
		$lists['gtemplates'] = array();
		for($i = 0, $n=count($rows); $i<$n; $i++) {

			$row = &$rows[$i];
			if( count($lists['gtemplates']) == 0 )
			{
				$lists['gtemplates'][0] = $row->gtemplateid;// = $row->groupid;
			} else {
				if( !in_array($row->gtemplateid, $lists['gtemplates']) ) {
					array_push($lists['gtemplates'], $row->gtemplateid);
				}
			}

		}
		for($i=0,$n=count($lists['gtemplates']);$i<$n;$i++) {
			$template = array();
			$this->__setTemplateConfig($lists['gtemplates'][$i], $params, $template);
			//container
			$lists['container'][$i] = $template['container']; //container
			$lists['template_name'][$i] = $template['template_name'];//template name
			//contact LDAP
			$connect = $bind = true;
			$ldap =  new JLDAP($params);
			if(!$ldap->connect()) {
				$connect = false;
			} else {$connect = true; }
			if(!$ldap->bind()) {
				$bind = false;
			} else {$bind = true;}
			//search groups in LDAP Directory
			if($connect && $bind) {

				$jgroupsdn = array();
				for($j=0,$m=count($groups);$j<$m;$j++) {
					$group = &$groups[$j];
					$rdn = str_replace("[groupname]", $group->groupname, $template['rdn']);
					$dn = $rdn .','.$template['container'];
					$filters = array("($rdn)");
					$result = $ldap->search($filters,$template['container']);
					if($result[0]['dn'] == $dn) {
						$lists[$group->groupid]['dn'] = $result[0]['dn'];
					} else {
						$lists[$group->groupid]['dn'] = "<b><i>Not in LDAP</i></b>";
					}

					$rdnn = str_replace("[groupname]", $group->groupname, $template['rdn']);
					$gdn = $rdnn .','. $template['container'];
					//for next check
					$jgroupsdn[$j] = $gdn;
				}
				//search for the groups those are extra in the ldap in this container
				$rdnprefix = str_replace("[groupname]", "*", $template['rdn']);
				$filters = array("($rdnprefix)");
				$result = $ldap->search($filters, $template['container']);
				$count = count($result);
				$lists[$lists['gtemplates'][$i]]['inldap'] = array();
				for($l = 0, $m = count($result);$l < $m; $l++ ) {
					if(!in_array($result[$l]['dn'], $jgroupsdn)) {
						if( count($lists[$lists['gtemplates'][$i]]['inldap']) == 0 )
						{
							$lists[$lists['gtemplates'][$i]]['inldap'][0] = $result[$l]['dn'];
						} else {
							array_push($lists[$lists['gtemplates'][$i]]['inldap'], $result[$l]['dn']);
						}
					}
				}
			}
		}
		require_once(JPATH_COMPONENT.DS.'views'.DS.'group.php');
		LdapViewGroup::groups( $rows, $pageNav, $lists );
	}

	function &__setTemplateConfig($tid, &$params, &$template) {
		$db =& JFactory::getDBO();
		//fetch template details from the database
		$query = "select * from #__ldap_template where tid=".$tid;
		$db->setQuery( $query);
		$temp = $db->loadObject();
		$template['rdn'] = $temp->rdn;
		$template['container'] = $temp->container;
		$template['configid'] = $temp->configid;
		$template['attributes'] = $temp->attributes;
		$template['template_name'] = $temp->template_name;
		$template['objectclasses'] = $temp->objectclasses;

		$template['userdn'] = $temp->userdn;
		$template['template_type'] = $temp->template_type;

		//fetch ldap configuration from database;
		$query = "select * from #__ldap_config where id=".$template['configid'];
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
		return $template;
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

		$row =& JTable::getInstance('group', 'Table');
		$row->load( $cid[0] );

		// 	Build Configuration select list
		$sql = 'SELECT tid as gtemplateid, tid as utemplateid, template_name as gtemplatename, template_name as utemplatename'
		. ' FROM #__ldap_template'
		;
		$db->setQuery($sql);
		if (!$db->query())
		{
			$this->setRedirect( 'index.php?option=com_ldap&c=group' );
			return JError::raiseWarning( 500, $db->getErrorMsg() );
		}
		//Users's Template List
		$utemplatelist[]		= JHTML::_('select.option',  '0', JText::_( 'Select User\'s Template' ), 'utemplateid', 'utemplatename' );
		$utemplatelist			= array_merge( $utemplatelist, $db->loadObjectList() );
		$lists['utemplateid']		= JHTML::_('select.genericlist',   $utemplatelist, 'utemplateid', 'class="inputbox" size="1"','utemplateid', 'utemplatename', $row->utemplateid );
		//Group's TemplateList
		$gtemplatelist[]		= JHTML::_('select.option',  '0', JText::_( 'Select Group\'s Template' ), 'gtemplateid', 'gtemplatename' );
		$gtemplatelist			= array_merge( $gtemplatelist, $db->loadObjectList() );
		$lists['gtemplateid']		= JHTML::_('select.genericlist',   $gtemplatelist, 'gtemplateid', 'class="inputbox" size="1"','gtemplateid', 'gtemplatename', $row->gtemplateid );


		require_once(JPATH_COMPONENT.DS.'views'.DS.'group.php');
		LdapViewGroup::group( $row, $lists );
	}

	/**
	 * Save method
	 */
	function save()
	{
		global $mainframe;


		$this->setRedirect( 'index.php?option=com_ldap&c=group' );

		// Initialize variables
		$gid		= JRequest::getVar( 'gtemplateid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$oldgroupname = JRequest::getCmd('groupname');

		$n			= count( $gid );
		$db =& JFactory::getDBO();

		$post	= JRequest::get( 'post' );
		$row =& JTable::getInstance('group', 'Table');

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


		//add group to the ldap directory
		//parameters
		$params = &JComponentHelper::getParams( 'com_ldap' );
		//get the template details
		$query = 'select * from #__ldap_template where tid='.$post['gtemplateid'];;
		$db->setQuery( $query );
		$template = $db->loadObject();
		$groupcontainer = $template->container; //container
		$groupdn = $template->userdn; //userdn format
		$rdn = $template->rdn; //rdn
		$attributes = $template->attributes; //attributes
		$objectclasses = $template->objectclasses; //objectclasses
		$configid = $template->configid; //configid
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
		$group = array();
		//for ($i=0; $i < $n; $i++) {
		$group['groupname'] = $post['groupname'];

		//ldap group object
		$ldapgroup = Array();
		$parts = split("\r\n",$attributes);//$attributes
		foreach($parts as $key=>$value) {
			$attrmap = split("=>", $value);
			if(!$attrmap[1] ) {
				continue;
			} else {
				$ldapgroup[$attrmap[0]] = $group[$attrmap[1]];
			}
		}

		//Objectclass
		$parts = split("\r\n",$objectclasses);//$objectclasses
		for($j = 0; $j<count($parts);$j++) {
			$ldapgroup['objectclass'][$j] = $parts[$j];
		}

		//update dn

		$dnn = str_replace("[groupname]", $group['groupname'], $groupdn); // new userdn
		$dno = str_replace("[groupname]", $oldgroupname, $groupdn); //old userdn if changing the username

		//new RDn
		$rdnn = str_replace("[groupname]", $group['groupname'], $rdn);

		//Old RDN
		$rdno = str_replace("[groupname]", $oldgroupname, $rdn);
			
		//search
		$filters = array("($rdno)");
		$result = $ldap->search($filters, $groupcontainer);
		if($result[0]['dn'] == $dnn) {

			$changed = $ldap->rename($dno, $rdnn, null, true);// or die("Cannot rename");

			if($changed) {
				$gname = split('=',$rdnn);
				unset($ldapgroup[$gname[0]]); // to make AD allow change the RDN
				unset($ldapgroup['objectclass']); //AD doesn't allow modifying objectclasses
				if(!$ldap->replace($dnn, $ldapgroup)){ // or die("Cannot Modify");
					JError::raiseWarning(44, JText::sprintf('LDAP Modify failed: %s', $ldap->getErrorMsg()));
				}
			}
		} else {

			if(!$this->_createGroup($ldap, $dnn, $ldapgroup))
			JError::raiseWarning(45, JText::sprintf('Failed to create group: %s', $ldap->getErrorMsg()));
		}
		switch ($task)
		{
			case 'apply':
				{
					$link = 'index.php?option=com_ldap&c=group&task=edit&cid[]='. $row->groupid . '&groupname='.$row->groupname;
				}
				break;
			case 'save':
			default:
				{
					$link = 'index.php?option=com_ldap&c=group';
				}
				break;
		}
		$this->setRedirect( $link, JText::_( 'Item Saved' ) );
	}

	function _createGroup(&$ldap, $dn, $ldapgroup) {

		return $ldap->create($dn,$ldapgroup); // or die('Failed to add '. $dn .': ' . $ldap->getErrorMsg());
	}

	function cancel()
	{
		$this->setRedirect( 'index.php?option=com_ldap&c=group' );
		// Initialize variables
		$db		=& JFactory::getDBO();
		$post	= JRequest::get( 'post' );
		$row	=& JTable::getInstance('group', 'Table');
		$row->bind( $post );
		$row->checkin();
	}

	function remove()
	{

		$this->setRedirect( 'index.php?option=com_ldap&c=group' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$n		= count( $cid );
		JArrayHelper::toInteger( $cid );
		$params = &JComponentHelper::getParams( 'com_ldap' );
		if ($n)
		{
			foreach($cid as  $key=>$groupid)
		 {
		 	//get the template and config details of the the group
		 	$query1 = 'SELECT c.*, t.*,g.*'
		 	.' FROM #__ldap_config as c'
		 	.' INNER JOIN #__ldap_template AS t ON t.configid = c.id'
		 	.' INNER JOIN #__ldap_group AS g ON g.gtemplateid=t.tid'
		 	.' WHERE g.groupid='.$groupid;
		 	$db->setQuery( $query1 );
		 	if (!$db->query()) {
		 		JError::raiseWarning( 500, $db->getError() );
		 	}
		 	$row = $db->loadObject();
		 	$gcontainer = $row->container; //group container for search scope
		 	$groupname = $row->groupname; //groupname to be deleted from LDAP
		 	$grouprdn = str_replace("[groupname]", $groupname, $row->rdn); //grourdn

		 	$groupdn = $grouprdn .','. $gcontainer;
		 	//get all the details
		 	$params->set('host', $row->host); //hostname
		 	$params->set('port', $row->port); //port
		 	$params->set('use_ldapV3', $row->version3); //varsion3
		 	$params->set('negotiate_tls', $row->negotiate_tls); //varsion3
		 	$params->set('no_referrals', $row->follow_referrals); //no referrals
		 	$params->set('base_dn', $row->basedn); //base_dn
		 	$params->set('username', $row->connect_username); //username
		 	$params->set('password', $row->connect_password); //password
		 	$ldap =  new JLDAP($params);
		 	if(!$ldap->connect()) {
		 		JError::raiseWarning(39, JText::_($query1.' Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
		 		return false;
		 	}
		 	if(!$ldap->bind()) {
		 		JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
		 		return false;
		 	}
		 	//remove from the ldap directory
		 	$filter = $grouprdn;
		 	//$filters = array("($filter)");
		 	$filters = array("(objectclass=*)");
		 	$result = $ldap->search($filters,$groupdn);
		 	//First Remove All Children
		 	$app =& JFactory::getApplication();
		 	for($i=1;$i<count($result);$i++) {
		 		$ldap->delete($result[$i]['dn']) or die('Failed to delete: ' . $result[$i]['dn']);
		 	}
				//delete the group container
				$ldap->delete($result[0]['dn']) or die('Failed to delete: ' . $result[0]['dn']);
				if($app->isAdmin()) JError::raiseWarning(41, JText::_('LDAP Group deleted.'));
		 	//$query
		 	//remove from user-group map table
		 	$query = 'DELETE FROM #__ldap_user_groups'
		 	. ' WHERE groupid = ' .$groupid
		 	;
		 	$db->setQuery( $query );
		 	if (!$db->query()) {
		 		JError::raiseWarning( 500, $db->getError() );
		 	}
		 	$query = 'DELETE FROM #__ldap_group'
		 	. ' WHERE groupid = ' .$groupid
		 	;
		 	$db->setQuery( $query );
		 	if (!$db->query()) {
		 		JError::raiseWarning( 500, $db->getError() );
		 	}

		 }
		}

		$this->setMessage( JText::sprintf( 'Items removed', $n ) );
	}

	/*
	 * Synchronise user from the LDAP to Joomla User
	 *
	 */
	function syncfromldap() {
		jimport('joomla.user.helper');
		global $mainframe;

		JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap&c=group' );
		// Initialize variables
		$gid		= JRequest::getVar( 'ngid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$n			= count( $gid );
		$db		=& JFactory::getDBO();

		$templategroup = array();
		for($i = 0; $i<$n; $i++) {
			$tgid = split('-',$gid[$i]);
			$grouptid = $tgid[0];
			$groupid = $tgid[1]; //no need here...
			$groupdn = $tgid[2];
			if(count($templategroup[$grouptid]) == 0)
			{
				$templategroup[$grouptid] = array();
				$templategroup[$grouptid][0]= $groupdn;
			} else {
				array_push($templategroup[$grouptid], $groupdn);
			}
		}
		$params = &JComponentHelper::getParams( 'com_ldap' );

		foreach($templategroup as $key => $value) {
			$template = array();
			$gtemplateid = $key;
			$this->__setTemplateConfig($gtemplateid, $params, $template);
			//contact LDAP
			$connect = $bind = true;
			$ldap =  new JLDAP($params);
			if(!$ldap->connect()) {
				$connect = false;
			} else {$connect = true; }
			if(!$ldap->bind()) {
				$bind = false;
			} else {$bind = true;}
				
			//search users in LDAP Directory
			if($connect && $bind) {
				for($i = 0, $n = count($templategroup[$key]); $i<$n; $i++) {
					$ldapgroup = split(',',$templategroup[$key][$i]);
					$rdn = $ldapgroup[0];
					$dn = $rdn . ','. $template['container'];
					//search
					$jgroup = array();
					$filters = array("($rdn)");
					$result = $ldap->search($filters,$template['container']);

					if($result[0]['dn'] == $dn) {
						//check for the mapped attributes
						$parts = split("\r\n",$template['attributes']);//$attributes
						foreach($parts as $part) {
							$attrmap = split("=>", $part);
							if(!$attrmap[1] ) {
								continue;
							} else {
								$jgroup[$i][$attrmap[1]] = $result[0][$attrmap[0]][0];
									
							}
						}
						$jgroup[$i]['gtemplateid'] = $gtemplateid;

						$this->__saveGroup($jgroup[$i]);
					}
				}
			}
		}
		$this->setMessage( JText::sprintf($n.' LDAP Group synched', $n ) );
	}

	/*
	 *
	 * Save Group in Joomla Tables
	 */
	function __saveGroup(&$jgroup) {
		$db		=& JFactory::getDBO();
		$attr = '';
		$values = '';
		$query = 'INSERT INTO #__ldap_group(groupname,gtemplateid) values('
		. $db->Quote($jgroup['groupname']).','
		. $jgroup['gtemplateid'] .')'
		;
		$db->setQuery( $query );
			
		if (!$db->query()) {
			JError::raiseWarning( 500, $db->getError() );
		}
	}
}
