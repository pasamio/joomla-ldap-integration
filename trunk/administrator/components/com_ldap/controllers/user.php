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

	function display() {
		$db =& JFactory::getDBO();
		$context = 'com_ldap.user.list.';

		$query = 'SELECT COUNT(*)'
		. ' FROM #__users'
		;

		$db->setQuery( $query );
		$usertotal = $db->loadResult(); //total joomla users
		//get all joomla users
		$query = 'SELECT * '
		. ' FROM #__users '
		;
		//$db->setQuery( $query, $pageNav->limitstart, $pageNav->limit );
		$db->setQuery( $query);
		$users = $db->loadObjectList();
		//populate users in the $lists
		for ($i=0, $n=count( $users ); $i < $n; $i++) {
			$row 			= &$users[$i];
			$lists['user']['name'][$i] = $row->name; //name
			$lists['user']['username'][$i] = $row->username; //username
			$lists['user']['id'][$i] = $row->id; //id

		}
		$params = &JComponentHelper::getParams( 'com_ldap' );
		//get all -User- templates
		$query = "SELECT COUNT(*) from #__ldap_template where template_type='user'";
		$db->setQuery( $query );
		$totalutemp = $db->loadResult(); //total user templates
		//get all template ids
		$query = "SELECT template_name, tid, container from #__ldap_template where template_type='user'";
		$db->setQuery( $query );

		$rows = $db->loadObjectList(); //total user templates

		for ($i=0, $n=count( $rows ); $i < $n; $i++) {
			$row 			= &$rows[$i];
			$template = array();
			$this->__setTemplateConfig($row->tid, $params, $template);
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
				$jusersdn = array();
				for ($j=0, $m=count( $users ); $j < $m; $j++) {

					$user 			= &$users[$j];
					$row->tid; //template id
					$rdnn = str_replace("[username]", $user->username, $template['rdn']);
					$dn = $rdnn .','. $template['container'];
					//for next check
					$jusersdn[$j] = $dn;
					$filters = array("($rdnn)");
					$result = $ldap->search($filters,$template['container']);
					$sync = true;
					if($result[0]['dn'] == $dn) {
						$lists['dn'][$row->tid][$user->id] = $dn;
						$this->__checkUserSync($ldap,$result[0], $user->id, $template['attributes'], $lists, $row->tid);

						foreach($lists[$row->tid][$user->id] as $key => $value){
							$sync = $sync && $value;
						}

						$lists[$row->tid][$user->id]['insync'] = $sync;

					} else {
						$lists[$row->tid][$user->id]['insync'] = false;
						$lists['dn'][$row->tid][$user->id] = "<b><i>Not in LDAP</i></b>";
					}
				}
				//search for the users those are extra in the ldap in this container
				$rdnprefix = str_replace("[username]", "*", $template['rdn']);

				$filters = array("($rdnprefix)");
				$result = $ldap->search($filters, $template['container']);
				$count = count($result);
				$lists[$row->tid]['inldap'] = array();
				for($l = 0, $m = count($result);$l < $m; $l++ ) {
					if(!in_array($result[$l]['dn'], $jusersdn)) {
						if( count($lists[$row->tid]['inldap']) == 0 )
						{
							$lists[$row->tid]['inldap'][0] = $result[$l]['dn'];
						} else {
							array_push($lists[$row->tid]['inldap'], $result[$l]['dn']);
						}
					}
				}
			} else {

				$lists['servererror'][$row->tid] = "LDAP Server not available:". $params->get('host').':' .$params->get('port');
			}

		}
		require_once(JPATH_COMPONENT.DS.'views'.DS.'user.php');
		LdapViewUser::users( $rows, $pageNav, $lists );

	}
	/**
	 * Set LDAP Template configurations and LDAP connection Parameters  
	 * @access 	private
	 * @param	string 	$tid		templateid
	 * @param	array	$params		LDAP Connection Parameters
	 * @param	array	$template	Template Parameters
	 * 
	 */
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
		$template['objectclasses'] = $temp->objectclasses;

		$template['userdn'] = $temp->userdn;
		$template['template_type'] = $temp->template_type;

		//fetch ldap configuration from database;
		$query = "select * from #__ldap_config where id=".$template['configid'];
		$db->setQuery( $query);
		$config = $db->loadObject();
		//JError::raiseWarning(39, JText::_('hostf: ' . $config->host));

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
	
	/**
	 * Check whether the LDAP user and Joomla User are synchronised  
	 * @access 	private
	 * @param	array	$ldapuser		the LDAP user array
	 * @param	int		$userid			The Joomla userid
	 * @param	string	$attributes		The Attribute Map of Joomla user and LDAP user
	 * @param	array	$lists			The lists array
	 * @param	int		$tid			Template id
	 * 
	 */
	
	function &__checkUserSync(&$ldap, $ldapuser, $userid, $attributes, &$lists, $tid) {
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
					$lists[$tid][$userid][$attrmap[0]] = true;
				} else {
					$lists[$tid][$userid][$attrmap[0]] = false; //can capture the details if required
				}
			}
		}
		return $lists;
	}

	/**
	 * Sync user to LDAP
	 * 
	 */
	function synctoldap()
	{
		// Check for request forgeries
		jimport('joomla.user.helper');
		JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap&c=user' );
		// Initialize variables
		$uid		= JRequest::getVar( 'uid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$n			= count( $uid );
		$templateuser = array();
		for($i = 0; $i<$n; $i++) {
			//$uid[$i] = [$tid]-[$uid];
			$tuid = split('-',$uid[$i]);
			$tid = $tuid[0];
			$userid = $tuid[1];
			if(count($templateuser[$tid]) == 0)
			{
				$templateuser[$tid] = array();
				$templateuser[$tid][0]= $userid;
			} else {
				array_push($templateuser[$tid], $userid);
			}
		}
		$params = &JComponentHelper::getParams( 'com_ldap' );

		foreach($templateuser as $key => $value) {
			$template = array();
			$templateid = $key;
			$this->__setTemplateConfig($templateid, $params, $template);
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
				for($i = 0, $n = count($templateuser[$key]); $i<$n; $i++) {
					$user =& JFactory::getUser( $templateuser[$key][$i] );
					$ldapuser = Array();
					$parts = split("\r\n",$template['attributes']);//$attributes
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
					$ldapuser['userPassword'] = $user->password; //this is wrong
					//$salt  = JUserHelper::genRandomPassword(32);
					//$crypt = JUserHelper::getCryptedPassword($array['password'], $salt);
					//$array['password'] = $crypt.':'.$salt;

					//Objectclass
					$parts = split("\r\n",$template['objectclasses']);//$objectclasses
					for($j = 0; $j<count($parts);$j++) {
						$ldapuser['objectclass'][$j] = $parts[$j];
					}
					//new RDn
					$rdnn = str_replace("[username]", $user->username, $template['rdn']);
					//update dn
					$dnn = $rdnn . ',' . $template['container'];
					//search
					$filters = array("($rdnn)");
					$result = $ldap->search($filters,$template['container']);

					if($result[0]['dn'] == $dnn) {
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
						JError::raiseWarning(1,JText::sprintf('$dnn: ' . $dnn));
						if(!$this->_createUser($ldap, $dnn, $ldapuser))
						JError::raiseWarning(45, JText::sprintf('Failed to create user: %s', $ldap->getErrorMsg()));
					}
				}

				if (empty( $uid )) {
					return JError::raiseWarning( 500, JText::_( 'No items selected' ) );
				}

				JArrayHelper::toInteger( $uid );
				$uids = implode( ',', $uid );
			}

		}

		$this->setMessage( JText::sprintf(' Item synched' ) );
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

	/**
	 * Synchronise user from the LDAP to Joomla User
	 *
	 */
	function syncfromldap() {
		jimport('joomla.user.helper');
		global $mainframe;
		JRequest::checkToken() or jexit( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_ldap&c=user' );
		// Initialize variables
		$uid		= JRequest::getVar( 'nuid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$n			= count( $uid );

		$db		=& JFactory::getDBO();

		$templateuser = array();
		for($i = 0; $i<$n; $i++) {
			//$uid[$i] = [$tid]-[$uid];
			$tuid = split('-',$uid[$i]);
			$tid = $tuid[0];
			$userid = $tuid[1]; //no need of this here...
			$userdn = $tuid[2];
			if(count($templateuser[$tid]) == 0)
			{
				$templateuser[$tid] = array();
				$templateuser[$tid][0]= $userdn;
			} else {
				array_push($templateuser[$tid], $userdn);
			}
		}
		$params = &JComponentHelper::getParams( 'com_ldap' );

		foreach($templateuser as $key => $value) {			
			$template = array();
			$templateid = $key;
			$this->__someFunction($templateid, $params, $template);
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
				for($i = 0, $n = count($templateuser[$key]); $i<$n; $i++) {
					$ldapuser = split(',',$templateuser[$key][$i]);
					$rdn = $ldapuser[0];
					$dn = $rdn . ','. $template['container'];
					//get the ldap user details
					//search in LDAP
					$juser = array();
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
								$juser[$i][$attrmap[1]] = $result[0][$attrmap[0]][0];									
							}
						}

						//unmapped attributes map here password: password, group, blockuser, registerdate, access
						//$juser[$i]['password'] = $result[0]['userPassword'][0]; //be careful:need to check this one
						$plain = "Welcome123"; //hardcoded: TODO: find the proper way
						$salt  = JUserHelper::genRandomPassword(32);
						$crypt = JUserHelper::getCryptedPassword($plain, $salt);
						$juser[$i]['password'] = $crypt.':'.$salt;
						$juser[$i]['usertype'] = "Registered";
						$juser[$i]['gid'] = 18; //Registered
						$now =& JFactory::getDate();
						$juser[$i]['registerDate'] = $now->toMySQL(); //get current date time
						$juser[$i]['block'] = 0;
						$juser[$i]['lastvisitDate'] = '0000-00-00 00:00:00';
						$juser[$i]['sendEmail'] = 0;

						$this->__saveUser($juser[$i]);
					}
				}
			}
		}
		$this->setMessage( JText::sprintf($n.' JUser synched', $n ) );
	}

	/**
	 * Save Imported User in Joomla Tables
	 * @access private
	 * @param array		$juser	The User Object Imported from LDAP
	 */
	function __saveUser(&$juser) {
		$db		=& JFactory::getDBO();
		$attr = '';
		$values = '';
		$query = 'INSERT INTO #__users(name,username,email,password,usertype,block,sendEmail,gid,registerDate,lastvisitDate,params) values('
		. $db->Quote($juser['name']).','
		. $db->Quote($juser['username']).','
		. $db->Quote($juser['email']).','
		. $db->Quote($juser['password']).','
		. $db->Quote($juser['usertype']).','
		. $juser['block'].','
		. $juser['sendEmail'].','
		. $juser['gid'].','
		. $db->Quote($juser['registerDate']).','
		. $db->Quote($juser['lastvisitDate']).','
		. $db->Quote($juser['params']).')'
		;
		$db->setQuery( $query );
			
		if (!$db->query()) {
			JError::raiseWarning( 500, $db->getError() );
		}
		
		$query = 'SELECT id FROM #__users WHERE username = ' . $db->Quote( $juser['username'] );

		$db->setQuery($query);
		$id =  $db->loadResult();
		$users = 'users';
		$query = 'INSERT INTO #__core_acl_aro(section_value,value,name) values('
				.$db->Quote($users) .','
				.$id.','
				.$db->Quote($juser['name']) .')'
				;		
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning( 500, $db->getError() );
		}	
		$query = 'SELECT id FROM #__core_acl_aro WHERE value = ' . $id;
		
		$db->setQuery($query);
		$aro_id =  $db->loadResult();
		$users = 'users';
		$query = 'INSERT INTO #__core_acl_groups_aro_map(group_id,aro_id) values('
				.$juser['gid'] .','
				.$aro_id.')'				
				;
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning( 500, $db->getError() );
		}	
	}
}
