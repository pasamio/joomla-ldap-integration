<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class LdapViewTemplate
{
	function setTemplatesToolbar()
	{
		JToolBarHelper::title( JText::_( 'LDAP Template Manager' ), 'generic.png' );
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::preferences('com_ldap', '150');
		JToolBarHelper::addNewX();
	}

	function templates( &$rows, &$pageNav, &$lists )
	{
		LdapViewTemplate::setTemplatesToolbar();
		JHTML::_('behavior.tooltip');
		?>
<form action="index.php?option=com_ldap&c=template" method="post" name="adminForm">
<table class="adminlist">
	<thead>
		<tr>
			<th width="20"><?php echo JText::_( 'Num' ); ?></th>
			<th width="20"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
			<th nowrap="nowrap" class="title">Name</th>
			<th width="15%" nowrap="nowrap">Configuration</th>
			<th width="25%" nowrap="nowrap">User/Group Container</th>		
			<th width="25%" nowrap="nowrap">User/Group DN</th>
			<th width="10%" nowrap="nowrap">RDN</th>
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
		$row = &$rows[$i];
		$link		= JRoute::_( 'index.php?option=com_ldap&c=template&task=edit&cid[]='. $row->tid );
		//$checked		= JHTML::_('grid.checkedout',   $row->tid, $i );
		$checked = '<input type="checkbox" id="cb' .$i. '" name="cid[]" value="'.$row->tid.'" onclick="isChecked(this.checked);"/>';
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td align="center"><?php echo $pageNav->getRowOffset($i); ?></td>
			<td align="center"><?php echo $checked; ?></td>
			<td><a href="<?php echo $link; ?>"> <?php echo $row->template_name; ?></a></td>
			<td align="center"><?php echo $row->configid;?></td>
			<td align="center"><?php echo $row->container; ?></td>	
			<td align="center"><?php echo $row->userdn; ?></td>		
			<td align="center"><?php echo $row->rdn; ?></td>						
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</tbody>
</table>

<input type="hidden" name="c" value="template" /> 
<input type="hidden" name="option" value="com_ldap" /> 
<input type="hidden" name="task" value="" /> 
<input type="hidden" name="tid" value="<?php echo $row->tid; ?>" /> 
<input type="hidden" name="boxchecked" value="0" />
<?php echo JHTML::_( 'form.token' ); ?>

</form>
	<?php
	}
	
	function setTemplateToolbar()
	{
		$task = JRequest::getVar( 'task', '', 'method', 'string');
		JToolBarHelper::title( $task == 'add' ? JText::_( 'LDAP Template' ) . ': <small><small>[ '. JText::_( 'New' ) .' ]</small></small>' : JText::_( 'LDAP Template' ) . ': <small><small>[ '. JText::_( 'Edit' ) .' ]</small></small>', 'generic.png' );
		
		JToolBarHelper::save( 'save' );
		JToolBarHelper::apply('apply');
		JToolBarHelper::cancel( 'cancel' );
	}

	function template( &$row, &$lists )
	{
		LdapViewTemplate::setTemplateToolbar();
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
			<td width="80%"><input class="inputbox" type="text" name="template_name"
				id="template_name" size="50" value="<?php echo $row->template_name;?>" /></td>
		</tr>
		<tr>
			<td width="20%" class="key"><label for="template_type"> <?php echo JText::_( 'Template Type' ); ?>:
			</label></td>
			<td width="80%"><input class="inputbox" type="text" name="template_type"
				id="template_type" size="50" value="<?php echo $row->template_type;?>" /></td>
		</tr>
		<tr>
			<td class="key"><label for="config_name"> <?php echo JText::_( 'LDAP Configuration' ); ?>:
			</label></td>
			<td><?php echo $lists['configid'];?></td>
		</tr>
		<tr>
			<td width="20%" class="key"><label for="container"> <?php echo JText::_( 'User/Group Container' ); ?>:
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
			<td width="20%" class="key"><label for="userdn"> <?php echo JText::_( "User/Group DN" ); ?>:
			</label></td>
			<td width="80%"><input class="inputbox" type="text" name="userdn"
				id="userdn" size="50" value="<?php echo $row->userdn;?>" /></td>
		</tr>
		
		<tr>
			<td class="key"><label for="attributes"> <?php echo JText::_( 'Attributes' ); ?>:
			</label></td>
			<td>
				<textarea class="inputbox" cols="70" rows="8" name="attributes" id="attributes"><?php echo $row->attributes;?></textarea>
			</td>
		</tr>
		<tr>
			<td width="100" align="right" class="key"><label for="objectclasses"> <?php echo JText::_( 'Objectclasses' ); ?>:
			</label></td>
			<td>
				<textarea class="inputbox" cols="70" rows="8" name="objectclasses" id="objectclasses"><?php echo $row->objectclasses;?></textarea>
			</td>
		</tr>		
	</tbody>
</table>
</fieldset>
</div>
<div class="clr"></div>

<input type="hidden" name="c" value="template" /> 
<input type="hidden" name="option" value="com_ldap" /> 
<input type="hidden" name="tid" value="<?php echo $row->tid; ?>" /> 
<input type="hidden" name="task" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
		<?php
	}
}
