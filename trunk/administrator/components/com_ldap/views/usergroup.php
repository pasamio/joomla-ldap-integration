<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class LdapViewUserGroup
{
	function setUserGroupToolbar()
	{
		JToolBarHelper::title( JText::_( 'LDAP User Group' ), 'generic.png' );

		JToolBarHelper::publishList();
		JToolBarHelper::divider();
		JToolBarHelper::customX('setgroup','generic.png','Set Group', 'Set Group');
		JToolBarHelper::customX('unsetgroup','generic.png','Unset Group', 'Unset Group');
		
		
		
	}

	function users( &$rows, &$pageNav, &$lists )
	{
		LdapViewUserGroup::setUserGroupToolbar();
		$ordering = ($lists['order'] == 'u.ordering');		
		JHTML::_('behavior.tooltip');
		?>
			<form action="index.php" method="post" name="adminForm">
			<table>
			<tr>
				<td align="left" width="100%">
					<?php echo JText::_( 'Filter' ); ?>:
					<input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
					<button onclick="this.form.submit();"><?php echo JText::_( 'Go' ); ?></button>
					<button onclick="document.getElementById('search').value='';this.form.getElementById('filter_groupid').value='0';this.form.getElementById('filter_state').value='';this.form.submit();"><?php echo JText::_( 'Filter Reset' ); ?></button>
				</td>
				<td nowrap="nowrap">
					<?php
					echo $lists['groupid'];
					echo $lists['state'];
					?>
				</td>
			</tr>
			</table>
				<table class="adminlist">
					<thead>
						<tr>
							<th width="20"><?php echo JText::_( 'Num' ); ?></th>
							<th width="20"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
							<th nowrap="nowrap" class="title">Name</th>
							<th width="20%" nowrap="nowrap">Username</th>
							<th width="20%" nowrap="nowrap">Groups</th>							
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
						$link		= JRoute::_( 'index.php?option=com_ldap&c=usergroup&task=edit&uid[]='. $row->id );
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
							 <td><?php if($lists['ugroups'][$row->id]){foreach($lists['ugroups'][$row->id] as $groups) {echo '<ul>' . $groups['gname'] . '</ul>'; } }else {echo "<ul>No group set.</ul>" ; } ?></td>
						
							<td><?php if($lists['ugroups'][$row->id]){foreach($lists['ugroups'][$row->id] as $groups) {echo '<ul>' . $groups['dn'] . '</ul>'; } }else {echo "<ul>No group set.</ul>" ; } ?></td>
							
							<td align="center"><?php echo $lists[$row->id]['dn']; $lastid= $row->id; ?></td>							
						</tr>
					<?php
					$k = 1 - $k;
					}

						?>
					
					</tbody>
			</table>

			<input type="hidden" name="c" value="usergroup" /> 
			<input type="hidden" name="option" value="com_ldap" /> 
			<input type="hidden" name="task" value="" /> 
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" /> 
			<input type="hidden" name="boxchecked" value="0" /> 
			<?php echo JHTML::_( 'form.token' ); ?>
		</form>
		<?php
	}
}