<?php
defined('_JEXEC') or die('Restricted access');
class TableConfig extends JTable
{
	var $id = null;
	var $name = null;
	var $host = null;
	var $port = null;
	var $version3 = null;
	var $negotiate_tls = null;
	var $follow_referrals = null;
	var $basedn = null;
	var $connect_username = null;
	var $connect_password = null;
	function __construct(&$db)
	{
		parent::__construct( '#__ldap_config', 'id', $db );
	}
}
?>