<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class LdapViewGroup
{
	function setGroupsToolbar()
	{
		JToolBarHelper::title( JText::_( 'LDAP Group Manager' ), 'generic.png' );
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		//JToolBarHelper::preferences('com_ldap', '150');
		JToolBarHelper::addNewX();
		
		JToolBarHelper::divider();

		JToolBarHelper::customX('syncfromldap','generic.png','Sync from LDAP', 'Sync from LDAP');
	}

	function groups( &$rows, &$pageNav, &$lists )
	{
		LdapViewGroup::setGroupsToolbar();
		JHTML::_('behavior.tooltip');
		?>
<form action="index.php?option=com_ldap&c=group" method="post"
	name="adminForm">
<table class="adminlist">
<?php

for ($i=0, $n=count( $lists['gtemplates'] ); $i < $n; $i++) {
	//$row 			= &$rows[$i];
	?>
	<thead>

		<tr align="left">
			<th colspan="6" nowrap="nowrap" align="left"><?php echo $lists['template_name'][$i]; ?>
			( Container: <i><?php echo $lists['container'][$i]; ?> ) </i> 
			</th>
		</tr>
		 
		<tr>
			<th width="20"><?php echo JText::_( 'Num' ) ; ?></th>
			<th width="20"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
			<th nowrap="nowrap" class="title">Group Name</th>
			<th width="20" nowrap="nowrap">Group's Template ID</th>
			<th width="20" nowrap="nowrap">User's Template Id</th>
			<!-- <th width="20" nowrap="nowrap">Synchronized</th> -->

			<th width="25%" nowrap="nowrap">LDAP Group</th>
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
	for ($j=0, $m=count( $rows ); $j < $m; $j++) {
		$row = &$rows[$j];
		$link		= JRoute::_( 'index.php?option=com_ldap&c=group&task=edit&cid[]='. $row->groupid . '&groupname='.$row->groupname);
		//$checked		= JHTML::_('grid.checkedout',   $row->tid, $i );
		$checked = '<input type="checkbox" id="cb' .$j. '" name="cid[]" value="'.$row->groupid.'" onclick="isChecked(this.checked);"/>';
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td align="center"><?php echo $pageNav->getRowOffset($j); ?></td>
			<td align="center"><?php echo $checked; ?></td>
			<td><a href="<?php echo $link; ?>"> <?php echo $row->groupname; ?></a></td>
			<td align="center"><?php echo $row->gtemplateid;?></td>
			<td align="center"><?php echo $row->utemplateid; ?></td>
			<!-- <td align="center"><?php echo '='; ?></td> -->
			<td align="center"><?php echo $lists[$row->groupid]['dn']; $lastid=$row->groupid;?></td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	$lastid++;
	for($l = 0; $l<count($lists[$lists['gtemplates'][$i]]['inldap']);$l++) {
		$checked = '<input type="checkbox" id="cb' .$i. '" name="ngid[]" value="'.$lists['gtemplates'][$i] ."-".$lastid.'-'.$lists[$lists['gtemplates'][$i]]['inldap'][$l].'" onclick="isChecked(this.checked);"/>';
		?>
	<tr class="<?php echo "row$k"; ?>">
		<td align="center"><?php echo $lastid; ?></td>
		<td align="center"><?php echo $checked; ?></td>
		<td></td>
		<td></td>
		<td></td>
		<td align="center"><b>
		<?php echo $lists[$lists['gtemplates'][$i]]['inldap'][$l]; $lastid++;?>
		</b>
		</td>

	</tr>

	<?php

	}	
}
?>
</table>

<input type="hidden" name="c" value="group" /> <input type="hidden"
	name="option" value="com_ldap" /> <input type="hidden" name="task"
	value="" /> 
<input type="hidden"
	name="boxchecked" value="0" /> <?php echo JHTML::_( 'form.token' ); ?>

</form>
	<?php
}

function setGroupToolbar()
{
	$task = JRequest::getVar( 'task', '', 'method', 'string');
	JToolBarHelper::title( $task == 'add' ? JText::_( 'LDAP Group' ) . ': <small><small>[ '. JText::_( 'New' ) .' ]</small></small>' : JText::_( 'LDAP Group' ) . ': <small><small>[ '. JText::_( 'Edit' ) .' ]</small></small>', 'generic.png' );

	JToolBarHelper::save( 'save' );
	JToolBarHelper::apply('apply');
	JToolBarHelper::cancel( 'cancel' );
}

function group( &$row, &$lists )
{
	LdapViewGroup::setGroupToolbar();
	JRequest::setVar( 'hidemainmenu', 1 );

	?>
<form action="index.php" method="post" name="adminForm">

<div class="col100">
<fieldset class="adminform"><legend><?php echo JText::_( 'Details' ); ?></legend>

<table class="admintable">
	<tbody>
		<tr>
			<td width="20%" class="key"><label for="groupname"> <?php echo JText::_( 'Group Name' ); ?>:
			</label></td>
			<td width="80%"><input class="inputbox" type="text" name="groupname"
				id="groupname" size="50" value="<?php echo $row->groupname;?>" /></td>
		</tr>
		<tr>
			<td class="key"><label for="gtemplateid"> <?php echo JText::_( 'Group\'s Template ID' ); ?>:
			</label></td>
			<td><?php echo $lists['gtemplateid'];?></td>
		</tr>
		<tr>
			<td class="key"><label for="utemplateid"> <?php echo JText::_( 'User\'s Template ID' ); ?>:
			</label></td>
			<td><?php echo $lists['utemplateid'];?></td>
		</tr>
	</tbody>
</table>
</fieldset>
</div>
<div class="clr"></div>

<input type="hidden" name="c" value="group" /> <input type="hidden"
	name="option" value="com_ldap" /> <input type="hidden" name="groupid"
	value="<?php echo $row->groupid; ?>" /> <input type="hidden"
	name="task" value="" /> <?php echo JHTML::_( 'form.token' ); ?></form>
	<?php
}
	}
