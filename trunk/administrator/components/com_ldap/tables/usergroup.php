<?php
defined('_JEXEC') or die('Restricted access');
class TableUsergroups extends JTable
{
	var $uid = null; //user id
	var $groupid = null; //group id
	function __construct(&$db)
	{
		parent::__construct( '#__ldap_user_groups', 'uid', $db );
	}
}
?>