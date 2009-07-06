<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class LdapViewUser
{
	function setUsersToolbar()
	{
		JToolBarHelper::title( JText::_( 'LDAP User Synchronizer' ), 'generic.png' );

		JToolBarHelper::publishList();
		
	}

	function users( &$rows, &$pageNav, &$lists )
	{
		LdapViewUser::setUsersToolbar();
		JHTML::_('behavior.tooltip');
		?>
			<form action="index.php?option=com_ldap" method="post" name="adminForm">
				<table class="adminlist">
					<thead>
						<tr>
							<th width="20"><?php echo JText::_( 'Num' ); ?></th>
							<th width="20"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
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
					for ($i=0, $n=count( $rows ); $i < $n; $i++) {
						$row 			= &$rows[$i];
						$link 			= JRoute::_( 'index.php?option=com_ldap&task=edit&cid[]='. $row->id );
						$checked		= JHTML::_('grid.checkedout',   $row, $i );
						//JError::raiseWarning(50, JText::_("D: ".$lists[$row->id]['insync']));						
						$sync = $lists[$row->id]['insync'] ? true: false ;						
						//$img 	= $sync ? 'publish_x.png' : 'tick.png';
						$img 	= $sync ? 'tick.png': 'publish_x.png';
						$task 	= $sync ? 'notask' : 'sync';
						$alt 	= $sync ? JText::_( 'In Sync' ) : JText::_( 'Not in Sync' );
						
					?>
						<tr class="<?php echo "row$k"; ?>">
							<td align="center"><?php echo $pageNav->getRowOffset($i); ?></td>
							<td align="center"><?php echo $checked; ?></td>
							<td><a href="<?php echo $link; ?>"> <?php echo $row->name; ?></a></td>
							<td align="center"><?php echo $row->username;?></td>
							<td align="center">
								<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $task;?>')">
									<img src="images/<?php echo $img;?>" width="16" height="16" border="0" alt="<?php echo $alt; ?>" /></a>
							</td>
							<td align="center"><?php echo $lists[$row->id]['dn'];?></td>							
						</tr>
					<?php
					$k = 1 - $k;
					}
					?>
					</tbody>
			</table>

			<input type="hidden" name="c" value="user" /> 
			<input type="hidden" name="option" value="com_ldap" /> 
			<input type="hidden" name="task" value="" /> 
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" /> 
			<input type="hidden" name="boxchecked" value="0" /> 
			<?php echo JHTML::_( 'form.token' ); ?>
		</form>
		<?php
	}
}
