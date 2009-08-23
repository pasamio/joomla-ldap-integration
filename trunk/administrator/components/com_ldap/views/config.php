<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class LdapViewConfig
{
	function setConfigsToolbar()
	{
		JToolBarHelper::title( JText::_( 'LDAP Configuration Manager' ), 'generic.png' );
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}

	function configs( &$rows, &$pageNav, &$lists )
	{
		LdapViewConfig::setConfigsToolbar();
		JHTML::_('behavior.tooltip');
		?>
			<form action="index.php?option=com_ldap" method="post" name="adminForm">
				<table class="adminlist">
					<thead>
						<tr>
							<th width="20"><?php echo JText::_( 'Num' ); ?></th>
							<th width="20"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
							<th nowrap="nowrap" class="title">Name</th>
							<th width="10%" nowrap="nowrap">Host</th>
							<th width="10%" nowrap="nowrap">Port</th>
							<th width="5%" nowrap="nowrap">Version 3</th>
							<th width="8%" nowrap="nowrap">Negotiate TLS</th>
							<th width="5%" nowrap="nowrap">Follow Referrals</th>
							<th width="5%" nowrap="nowrap">Base DN</th>
							<th width="80">Connect Username</th>
							<th width="1%" nowrap="nowrap">Connect Password</th>
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
						$row->checked_out = 0;
						$checked		= JHTML::_('grid.checkedout',   $row, $i );
					?>
						<tr class="<?php echo "row$k"; ?>">
							<td align="center"><?php echo $pageNav->getRowOffset($i); ?></td>
							<td align="center"><?php echo $checked; ?></td>
							<td><a href="<?php echo $link; ?>"> <?php echo $row->name; ?></a></td>
							<td align="center"><?php echo $row->host;?></td>
							<td align="center"><?php echo $row->port; ?></td>
							<td align="center"><?php echo $row->version3 ? JText::_( 'Yes' ) : JText::_( 'No' );?></td>
							<td class="order"><?php echo $row->negotiate_tls ? JText::_( 'Yes' ) : JText::_( 'No' );?></td>
							<td align="center"><?php echo $row->follow_referrals ? JText::_( 'Yes' ) : JText::_( 'No' );?></td>
							<td align="center"><?php echo $row->basedn;?></td>
							<td align="center"><?php echo $row->connect_username;?></td>
							<td><?php echo $row->connect_password;?></td>
						</tr>
					<?php
					$k = 1 - $k;
					}
					?>
					</tbody>
			</table>

			<input type="hidden" name="c" value="config" /> 
			<input type="hidden" name="option" value="com_ldap" /> 
			<input type="hidden" name="task" value="" /> 
			<input type="hidden" name="boxchecked" value="0" /> 
			<?php echo JHTML::_( 'form.token' ); ?>
		</form>
		<?php
	}

	function setConfigToolbar()
	{
		$task = JRequest::getVar( 'task', '', 'method', 'string');
		JToolBarHelper::title( $task == 'add' ? JText::_( 'LDAP Configuration' ) . ': <small><small>[ '. JText::_( 'New' ) .' ]</small></small>' : JText::_( 'LDAP Configuration' ) . ': <small><small>[ '. JText::_( 'Edit' ) .' ]</small></small>', 'generic.png' );

		JToolBarHelper::save( 'save' );
		JToolBarHelper::apply('apply');
		JToolBarHelper::cancel( 'cancel' );
	}

	function config( &$row, &$lists )
	{
		LdapViewConfig::setConfigToolbar();
		JRequest::setVar( 'hidemainmenu', 1 );
		?>
			<form action="index.php" method="post" name="adminForm">
				<div class="col100">
					<fieldset class="adminform"><legend><?php echo JText::_( 'Details' ); ?></legend>
						<table class="admintable">
							<tbody>
								<tr>
									<td width="20%" class="key">
										<label for="name"> <?php echo JText::_( 'Name' ); ?>:</label>
									</td>
									<td width="80%">
										<input class="inputbox" type="text" name="name" id="name" size="50" value="<?php echo $row->name;?>" />
									</td> 
								</tr>
								<tr>
									<td class="key">
										<label for="host"> <?php echo JText::_( 'Host' ); ?>:</label>
									</td>
									<td>
										<input class="inputbox" type="text" name="host" id="host" size="50" value="<?php echo $row->host;?>" />
									</td>
								</tr>
								<tr>
									<td class="key">
										<label for="port"> <?php echo JText::_( 'Port' ); ?>:</label>
									</td>
									<td>
										<input class="inputbox" type="text" name="port" id="port" size="6" value="<?php echo $row->port;?>" />
									</td>
								</tr>
								<tr>
									<td width="100" align="right" class="key">Allow Version3:</td>
									<td><?php echo $lists['version3']; ?></td>
								</tr>
								<tr>
									<td width="100" align="right" class="key">Negotiate TLS:</td>
									<td><?php echo $lists['negotiate_tls']; ?></td>
								</tr>
								<tr>
									<td width="100" align="right" class="key">Follow Referrals:</td>
									<td><?php echo $lists['follow_referrals']; ?></td>
								</tr>
								<tr>
									<td width="100" align="right" class="key">Base DN:</td>
									<td>
										<input class="text_area" type="text" name="basedn" id="basedn" size="50" maxlength="250" value="<?php echo $row->basedn;?>" />
									</td>
								</tr>
								<tr>
									<td width="100" align="right" class="key">Connect username:</td>
									<td>
										<input class="text_area" type="text" name="connect_username" id="connect_username" size="50" maxlength="250" value="<?php echo $row->connect_username;?>" />
									</td>
								</tr>
								<tr>
									<td width="100" align="right" class="key">Connect password:</td>
									<td>
										<input class="text_area" type="text" name="connect_password" id="connect_password" size="50" maxlength="250" value="<?php echo $row->connect_password;?>" />
									</td>
								</tr>
							</tbody>
						</table>
					</fieldset>
				</div>
				<div class="clr"></div>

				<input type="hidden" name="c" value="config" /> 
				<input type="hidden" name="option" value="com_ldap" /> 
				<input type="hidden" name="id" value="<?php echo $row->id; ?>" /> 
				<input type="hidden" name="task" value="" /> 
				<?php echo JHTML::_( 'form.token' ); ?>
			</form>
		<?php
	}
}
