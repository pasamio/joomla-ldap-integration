<?php
/**
 * @version		$Id: example.php 10094 2008-03-02 04:35:10Z instance $
 * @package		JAuthTools
 * @subpackage	LDAP
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.client.ldap');

/**
 * LDAP Integration Plugin
 *
 * @package		JAuthTools
 * @subpackage	LDAP
 * @since 		1.5
 */
class plgUserLDAP extends JPlugin {

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgUserLDAP(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}
	//before storing user check for the old username
	function onBeforeStoreUser($user, $isold, $success, $msg) {
		if($user['username']) {
			$this->params->set('oldusername', $user['username']);
		} 
		if(JRequest::getCmd('groupname')) {
		 	JError::raiseWarning(1,JText::sprintf(JRequest::getCmd('groupname')));
		 }
		//return true;
	}

	/**
	 * LDAP store user method
	 *
	 * We store the user in the default location if they are new,
	 * or save them back to where they are if they are not a new
	 * user.
	 *
	 * @param 	array		holds the new user data
	 * @param 	boolean		true if a new user is stored
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterStoreUser($user, $isnew, $success, $msg)
	{
		if(!$success) return false; // bail out if not successfully deleted
		$plugin = & JPluginHelper :: getPlugin('user', 'ldap');
		$params = new JParameter($plugin->params);
		$templateid = $params->get('templateid');

		//get the template details
		$query = 'select * from #__ldap_template where tid='.$templateid;
		$db = &JFactory::getDBO();
		$db->setQuery( $query );
		$row = $db->loadObject();

		$usercontainer = $row->container; //container
		$userdn = $row->userdn; //userdn format
		$rdn = $row->rdn; //rdn
		$attributes = $row->attributes; //attributes
		$objectclasses = $row->objectclasses; //objectclasses

		//get ldap config details
		$query2 = 'select * from #__ldap_config where id='.$row->configid;
		//$db2 = &JFactory::getDBO();
		$db->setQuery( $query2 );
		$row = $db->loadObject();

		$this->params->set('host', $row->host); //hostname
		$this->params->set('port', $row->port); //port
		$this->params->set('use_ldapV3', $row->version3); //varsion3
		$this->params->set('negotiate_tls', $row->negotiate_tls); //varsion3
		$this->params->set('no_referrals', $row->follow_referrals); //no referrals
		$this->params->set('base_dn', $row->basedn); //base_dn
		$this->params->set('username', $row->connect_username); //username
		$this->params->set('password', $row->connect_password); //password
		//connect to LDAP
		$ldap = new JLDAP($this->params);

		if(!$ldap->connect()) {
			JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
			return false;
		}
		if(!$ldap->bind()) {
			JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
			return false;
		}
		//ldap user object
		$ldapuser = Array();
		$parts = split("\r\n",$attributes);//$attributes
		foreach($parts as $key=>$value) {
			$attrmap = split("=>", $value);
			if(!$attrmap[1] ) {
				continue;
			} else {
				$ldapuser[$attrmap[0]] = $user[$attrmap[1]];
			}
		}

		//not mapped attributes
		//sn
		$parts = explode(' ',$user['name']);
		$ldapuser['sn'] = array_pop($parts); // Get the last part, ensures we at least have a value for surname (req)
		//initials
		if(count($parts)) {
			$ldapuser['initials'] = implode(' ', $parts); // abuse this; outlook does the same
		}
		//userpassword
		if(!empty($user['password_clear'])) $ldapuser['userpassword'] = Array($ldap->generatePassword($user['password_clear']));

		//Objectclass
		$parts = split("\r\n",$objectclasses);//$objectclasses
		for($i = 0; $i<count($parts);$i++) {
			$ldapuser['objectclass'][$i] = $parts[$i];
		}

		//update dn
		$dnn = str_replace("[username]", $user['username'], $userdn); // new userdn
		$dno = str_replace("[username]", $this->params->get('oldusername'), $userdn); //old userdn if changing the username
		//new RDn
		$rdnn = str_replace("[username]", $user['username'], $rdn);
		//old RDN, get the last value of username for search
		$rdno = str_replace("[username]", $this->params->get('oldusername'), $rdn);
		//search
		//$result = $ldap->simple_search($rdno);
		$filters = array("($rdno)");
		$result = $ldap->search($filters,$usercontainer);
		//$result = 
		//if(count($result) == 1) {
		if($result[0]['dn'] == $dno) {
			if($isnew) {
				unset($ldapuser['userpassword']);
				$app =& JFactory::getApplication();
				if($app->isAdmin()) {
					// don't display this to users in the front end
					JError::raiseWarning(46, JText::_('New Joomla! user already exists in LDAP. LDAP password not changed.'));
				}
			}
			//unset keys of any empty value
			foreach ($ldapuser as $key => $value) {
				if (empty($value)) {
					unset($ldapuser[$key]);
				}
			}
			//rename the RDN
			$changed = $ldap->rename($dno, $rdnn, null, true);// or die("Cannot rename");
				
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
		return true;
	}

	/**
	 * LDAP User Deletion
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param 	array		holds the user data
	 * @param	boolean		true if user was succesfully deleted in the database
	 * @param	string		message
	 */
	function onAfterDeleteUser($user, $success, $msg)
	{
		if(!$success) return false; // bail out if not successfully deleted

		//get ldap config details
		$plugin = & JPluginHelper :: getPlugin('user', 'ldap');
		$params = new JParameter($plugin->params);
		$templateid = $params->get('templateid');

		//get the template details
		$query = 'select * from #__ldap_template where tid='.$templateid;
		$db = &JFactory::getDBO();
		$db->setQuery( $query );
		$row = $db->loadObject();
		$rdn = $row->rdn;
		$query2 = 'select * from #__ldap_config where id='.$row->configid;
		$db->setQuery( $query2 );
		$row = $db->loadObject();
		//load parameters
		$this->params->set('host', $row->host); //hostname
		$this->params->set('port', $row->port); //port
		$this->params->set('use_ldapV3', $row->version3); //varsion3
		$this->params->set('negotiate_tls', $row->negotiate_tls); //varsion3
		$this->params->set('no_referrals', $row->follow_referrals); //no referrals
		$this->params->set('base_dn', $row->basedn); //base_dn
		$this->params->set('username', $row->connect_username); //username
		$this->params->set('password', $row->connect_password); //password

		//connect to LDAP
		$ldap = new JLDAP($this->params);

		if(!$ldap->connect()) {
			JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
			return false;
		}
		if(!$ldap->bind()) {
			JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
			return false;
		}
		$rdn = str_replace("[username]", $user['username'], $rdn);
		$result = $ldap->simple_search($rdn);
		$c = count($result);
		$app =& JFactory::getApplication();
		if($c == 1) {
			$ldap->delete($result[0]['dn']) or die('failed to delete user');
			if($app->isAdmin()) JError::raiseWarning(41, JText::_('LDAP User deleted'));
		} else if($c > 1) {
			// there was more than one DN returned, special situation!
			if($app->isAdmin()) JError::raiseWarning(42,JText::_('Too many users found in LDAP'));
		} else {
			// didn't find a result
			if($app->isAdmin()) JError::raiseWarning(43,JText::_('No matching LDAP user found'));
		}
		return true;
	}

	/**
	 * Create a user with the details at a DN
	 * Populates the objectclass
	 *
	 * @param string dn DN where to create the user
	 * @param array ldapuser The LDAP user details
	 * @return bool result of create
	 * @access private
	 */
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
