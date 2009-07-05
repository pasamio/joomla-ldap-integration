<?php
defined('_JEXEC') or die('Restricted access');
class TableAttributemap extends JTable
{
	var $template_id = null;
	var $attribute = null;
	var $joomla_attribute = null;
	function __construct(&$db)
	{
		parent::__construct( '#__ldap_template_attribute_map', 'id', $db );
	}
}
?>