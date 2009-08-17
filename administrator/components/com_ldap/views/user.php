<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class LdapViewUser
{
	function setUsersToolbar()
	{
		JToolBarHelper::title( JText::_( 'LDAP User Synchronizer' ), 'generic.png' );

		JToolBarHelper::customX('synctoldap','generic.png','Sync to LDAP', 'Sync to LDAP');
		JToolBarHelper::divider();

		JToolBarHelper::customX('syncfromldap','generic.png','Sync from LDAP', 'Sync from LDAP');
		//JToolBarHelper::publishList();
		//JToolBarHelper::divider();
		//JToolBarHelper::customX('getuser','generic.png','Get User from LDAP', 'Get User from LDAP');


	}

	function users( &$rows, &$pageNav, &$lists )
	{
		LdapViewUser::setUsersToolbar();
		JHTML::_('behavior.tooltip');
		?>
<form action="index.php" method="post" name="adminForm">
<table class="adminlist">


<?php

for ($i=0, $n=count( $rows ); $i < $n; $i++) {
	$row 			= &$rows[$i];
	?>
	<thead>
		<tr align="left">
			<th colspan="6" nowrap="nowrap" align="left"><?php echo $row->template_name; ?>
			( Container: <i><?php echo $row->container; ?> ) </i> <?php echo $lists['servererror'][$row->tid] ?>
			</th>
		</tr>
		<tr>
			<th width="20"><?php echo JText::_( 'Num' ); ?></th>
			<th width="20"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
			<th nowrap="nowrap" class="title">Name</th>
			<th width="20%" nowrap="nowrap">Username</th>
			<th width="10%" nowrap="nowrap">Synchronized</th>
			<th width="30%" nowrap="nowrap">LDAP User</th>
		</tr>
	</thead>
	<?php
	for($u=0, $m=count($lists['user']['id']); $u < $m ; $u++) {
		//echo "name: " . $lists['user']['name'][$u];
		//$link 			= JRoute::_( 'index.php?option=com_ldap&task=sync&id[]='. $row->id );
		$link		= JRoute::_( 'index.php?option=com_ldap&c=user&task=edit&uid[]='. $lists['user']['id'][$u] );
		$checked = '<input type="checkbox" id="cb' .$i. '" name="uid[]" value="'.$row->tid ."-".$lists['user']['id'][$u].'" onclick="isChecked(this.checked);"/>';
		//$checked		= JHTML::_('grid.checkedout',   $row, $i );
		//JError::raiseWarning(50, JText::_("D: ".$lists[$row->id]['insync']));
		$sync = $lists[$row->tid][$lists['user']['id'][$u]]['insync'] ? true: false ;
		//$img 	= $sync ? 'publish_x.png' : 'tick.png';
		$img 	= $sync ? 'tick.png': 'publish_x.png';
		//$task 	= $sync ? 'sync' : 'notask';
		//$alt 	= $sync ? JText::_( 'In Sync' ) : JText::_( 'Not in Sync' );

		?>
	<tr class="<?php echo "row$k"; ?>">
		<td align="center"><?php echo $lists['user']['id'][$u]; ?></td>
		<td align="center"><?php echo $checked; ?></td>
		<td><a href="<?php echo $link; ?>"> <?php echo $lists['user']['name'][$u]; ?></a></td>
		<td align="center"><?php echo $lists['user']['username'][$u];?></td>
		<!-- 
							<td align="center">
								<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $task;?>')">
									<img src="images/<?php echo $img;?>" width="16" height="16" border="0" alt="<?php echo $alt; ?>" /></a>
							</td>
							 -->
		<td align="center"><img src="images/<?php echo $img;?>" width="16"
			height="16" border="0" alt="<?php echo $alt; ?>" /></td>
		<td align="center"><?php echo $lists['dn'][$row->tid][$lists['user']['id'][$u]]; $lastid= $lists['user']['id'][$u]; ?></td>
	</tr>
	<?php
	$k = 1 - $k;
	}
	//display users those are in ldap only
	$lastid++;
	for($j = 0; $j<count($lists[$row->tid]['inldap']);$j++) {
		$checked = '<input type="checkbox" id="cb' .$i. '" name="nuid[]" value="'.$row->tid ."-".$lastid.'-'.$lists[$row->tid]['inldap'][$j].'" onclick="isChecked(this.checked);"/>';
		?>
	<tr class="<?php echo "row$k"; ?>">
		<td align="center"><?php echo $lastid; ?></td>
		<td align="center"><?php echo $checked; ?></td>
		<td></td>
		<td></td>
		<td></td>
		<td align="center"><b>
		<?php echo $lists[$row->tid]['inldap'][$j]; $lastid++;?>
		</b>
		</td>

	</tr>

	<?php

	}
}
?>
</table>

<input type="hidden" name="c" value="user" /> <input type="hidden"
	name="option" value="com_ldap" /> <input type="hidden" name="task"
	value="" /> <input type="hidden" name="id"
	value="<?php echo $row->id; ?>" /> <input type="hidden"
	name="boxchecked" value="0" /> <?php echo JHTML::_( 'form.token' ); ?>
</form>
<?php


	}
	function users2( &$rows, &$pageNav, &$lists )
	{
		LdapViewUser::setUsersToolbar();
		JHTML::_('behavior.tooltip');
		?>
<form action="index.php" method="post" name="adminForm">
<table class="adminlist">
	<thead>
		<tr>
			<th width="20"><?php echo JText::_( 'Num' ); ?></th>
			<th width="20"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
			<th nowrap="nowrap" class="title">Name</th>
			<th width="20%" nowrap="nowrap">Username</th>
			<th width="10%" nowrap="nowrap">Synchronized</th>
			<th width="30%" nowrap="nowrap">LDAP User</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="13"><?php echo $pageNav->getListFooter(); ?></td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$k = 0;
	$lastid = -1;
	for ($i=0, $n=count( $rows ); $i < $n; $i++) {
		$row 			= &$rows[$i];
		//$link 			= JRoute::_( 'index.php?option=com_ldap&task=sync&id[]='. $row->id );
		$link		= JRoute::_( 'index.php?option=com_ldap&c=user&task=edit&uid[]='. $row->id );
		$checked = '<input type="checkbox" id="cb' .$i. '" name="uid[]" value="'.$row->id.'" onclick="isChecked(this.checked);"/>';
		//$checked		= JHTML::_('grid.checkedout',   $row, $i );
		//JError::raiseWarning(50, JText::_("D: ".$lists[$row->id]['insync']));
		$sync = $lists[$row->id]['insync'] ? true: false ;
		//$img 	= $sync ? 'publish_x.png' : 'tick.png';
		$img 	= $sync ? 'tick.png': 'publish_x.png';
		//$task 	= $sync ? 'sync' : 'notask';
		//$alt 	= $sync ? JText::_( 'In Sync' ) : JText::_( 'Not in Sync' );

		?>
		<tr class="<?php echo "row$k"; ?>">
			<td align="center"><?php echo $pageNav->getRowOffset($i); ?></td>
			<td align="center"><?php echo $checked; ?></td>
			<td><a href="<?php echo $link; ?>"> <?php echo $row->name; ?></a></td>
			<td align="center"><?php echo $row->username;?></td>
			<!-- 
							<td align="center">
								<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $task;?>')">
									<img src="images/<?php echo $img;?>" width="16" height="16" border="0" alt="<?php echo $alt; ?>" /></a>
							</td>
							 -->
			<td align="center"><img src="images/<?php echo $img;?>" width="16"
				height="16" border="0" alt="<?php echo $alt; ?>" /></td>
			<td align="center"><?php echo $lists[$row->id]['dn']; $lastid= $row->id; ?></td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	//$checked = '<input type="checkbox" id="cb' .$i. '" name="uid[]" value="'.$row->id.'" onclick="isChecked(this.checked);"/>';
	$lastid++;
	for($l=$i,$j = 0; $j<count($lists['inldap']); $j++, $l++) {
		$checked = '<input type="checkbox" id="cb' .$l. '" name="nuid[]" value="'.$row->tid.'-'.$lastid.'-'.$lists['inldap'][$j].'" onclick="isChecked(this.checked);"/>';
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td align="center"><?php echo $pageNav->getRowOffset($l); ?></td>
			<td align="center"><?php echo $checked; ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo $lists['inldap'][$j]; ?></td>
		</tr>
		<?php
		$k = 1 - $k;
		$lastid++;
	}
	?>

	</tbody>
</table>

<input type="hidden" name="c" value="user" /> <input type="hidden"
	name="option" value="com_ldap" /> <input type="hidden" name="task"
	value="" /> <input type="hidden" name="id"
	value="<?php echo $row->id; ?>" /> <input type="hidden"
	name="boxchecked" value="0" /> <?php echo JHTML::_( 'form.token' ); ?>
</form>
	<?php
	}

	function setUserToolbar()
	{
		$task = JRequest::getVar( 'task', '', 'method', 'string');
		JToolBarHelper::title( $task == 'add' ? JText::_( 'User' ) . ': <small><small>[ '. JText::_( 'New' ) .' ]</small></small>' : JText::_( 'User' ) . ': <small><small>[ '. JText::_( 'Edit' ) .' ]</small></small>', 'generic.png' );

		JToolBarHelper::save( 'save' );
		JToolBarHelper::apply('apply');
		JToolBarHelper::cancel( 'cancel' );
	}
	function user( &$row, &$lists )
	{
		LdapViewUser::setUserToolbar();
		JRequest::setVar( 'hidemainmenu', 1 );

		?>
<form action="index.php" method="post" name="adminForm">

<div class="col100">
<fieldset class="adminform"><legend><?php echo JText::_( 'Details' ); ?></legend>

<table class="admintable">
	<tbody>
		<tr>
			<td width="20%" class="key"><label for="template_name"> <?php echo JText::_( 'Template Name' ); ?>:
			</label></td>
			<td width="80%"><input class="inputbox" type="text"
				name="template_name" id="template_name" size="50"
				value="<?php echo $row->template_name;?>" /></td>
		</tr>
		<tr>
			<td class="key"><label for="config_name"> <?php echo JText::_( 'LDAP Configuration' ); ?>:
			</label></td>
			<td><?php echo $lists['configid'];?></td>
		</tr>
		<tr>
			<td width="20%" class="key"><label for="container"> <?php echo JText::_( 'User Container' ); ?>:
			</label></td>
			<td width="80%"><input class="inputbox" type="text" name="container"
				id="container" size="50" value="<?php echo $row->container;?>" /></td>
		</tr>
		<tr>
			<td width="20%" class="key"><label for="rdn"> <?php echo JText::_( 'RDN' ); ?>:
			</label></td>
			<td width="80%"><input class="inputbox" type="text" name="rdn"
				id="rdn" size="50" value="<?php echo $row->rdn;?>" /></td>
		</tr>
		<tr>
			<td width="20%" class="key"><label for="userdn"> <?php echo JText::_( "User's DN" ); ?>:
			</label></td>
			<td width="80%"><input class="inputbox" type="text" name="userdn"
				id="userdn" size="50" value="<?php echo $row->userdn;?>" /></td>
		</tr>

		<tr>
			<td class="key"><label for="attributes"> <?php echo JText::_( 'Attributes' ); ?>:
			</label></td>
			<td><textarea class="inputbox" cols="70" rows="8" name="attributes"
				id="attributes"><?php echo $row->attributes;?></textarea></td>
		</tr>
		<tr>
			<td width="100" align="right" class="key"><label for="objectclasses">
			<?php echo JText::_( 'Objectclasses' ); ?>: </label></td>
			<td><textarea class="inputbox" cols="70" rows="8"
				name="objectclasses" id="objectclasses"><?php echo $row->objectclasses;?></textarea>
			</td>
		</tr>
	</tbody>
</table>
</fieldset>
</div>
<div class="clr"></div>

<input type="hidden" name="c" value="template" /> <input type="hidden"
	name="option" value="com_ldap" /> <input type="hidden" name="tid"
	value="<?php echo $row->tid; ?>" /> <input type="hidden" name="task"
	value="" /> <?php echo JHTML::_( 'form.token' ); ?></form>
			<?php
	}
}
