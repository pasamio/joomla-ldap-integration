<?php
defined('_JEXEC') or die('Restricted access');
class TableTemplate extends JTable
{
	var $tid = null; //template id
	var $configid = null; //configuration id
	var $template_name = null; //template name
	var $container = null; //container where the user/groups has to added
	var $rdn = null; //rdn
	var $userdn = null; //user's dn
	var $attributes = null; //template attributes
	var $objectclasses = null; //template objectclasses
	function __construct(&$db)
	{
		parent::__construct( '#__ldap_template', 'tid', $db );
	}
}
?>
