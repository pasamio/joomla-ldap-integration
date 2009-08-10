<?php
defined('_JEXEC') or die('Restricted access');
class TableGroup extends JTable
{
	var $groupid = null; //group id
	var $groupname = null; //group name
	var $gtemplateid = null; //group template id
	var $utemplateid = null; //user template id	
	function __construct(&$db)
	{
		parent::__construct( '#__ldap_group', 'groupid', $db );
	}
}
?>