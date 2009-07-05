<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class LdapViewAttributes
{
	function setAttributesToolbar()
	{
		$task = JRequest::getVar( 'task', '', 'method', 'string');
		JToolBarHelper::title( $task == 'add' ? JText::_( 'Template Attributes Mapping' ) . ': <small><small>[ '. JText::_( 'New' ) .' ]</small></small>' : JText::_( 'LDAP Configuration' ) . ': <small><small>[ '. JText::_( 'Edit' ) .' ]</small></small>', 'generic.png' );

		//JToolBarHelper::save( 'save' );
		//JToolBarHelper::apply('apply');
		JToolBarHelper::editListX();
				JToolBarHelper::cancel( 'cancel' );
	}
	function attributes( &$rows, &$pageNav, &$lists )
	{
		LdapViewAttributes::setAttributesToolbar();
		JHTML::_('behavior.tooltip');
		?>
<form action="index.php?option=com_ldap&c=attributes" method="post" name="adminForm">
<table class="adminlist" width="50%">
	<thead>
		<tr>
			<th width="20"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
			<th nowrap="nowrap" class="title">LDAP Attribute</th>
			<th width="25%" nowrap="nowrap">JUser Attribute</th>		
		</tr>
	</thead>
	<tfoot>

	</tfoot>
	<tbody>
	<?php
	$k = 0;
	for ($i=0, $n=count( $rows ); $i < $n; $i++) {
		$row = &$rows[$i];
		$checked = '<input type="checkbox" id="cb' .$i. '" name="cid[]" value="'.$row->attribute.'" onclick="isChecked(this.checked);"/>';
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td align="center"><?php echo $checked; ?></td>
			<td align="center"><?php echo $row->attribute; ?></td>	
			<?php $list = JHTML::_('select.genericlist',  $lists, $row->attribute, 'class="inputbox" size="1" ', 'value', 'text', $row->attribute );?>
			<td align="center"><?php echo $list; ?></td>		
				
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</tbody>
</table>

<input type="hidden" name="c" value="attributes" /> 
<input type="hidden" name="option" value="com_ldap" /> 
<input type="hidden" name="task" value="" /> 
<input type="hidden" name="template_id" value="<?php echo $rows->template_id; ?>" /> 
<input type="hidden" name="boxchecked" value="0" />
<?php echo JHTML::_( 'form.token' ); ?>

</form>
	<?php
	}
	
	function setAttributeToolbar()
	{
		$task = JRequest::getVar( 'task', '', 'method', 'string');
		JToolBarHelper::title( $task == 'add' ? JText::_( 'LDAP Template' ) . ': <small><small>[ '. JText::_( 'New' ) .' ]</small></small>' : JText::_( 'LDAP Template' ) . ': <small><small>[ '. JText::_( 'Edit' ) .' ]</small></small>', 'generic.png' );
		
		JToolBarHelper::save( 'save' );
		JToolBarHelper::apply('apply');
		JToolBarHelper::cancel( 'cancel' );
	}

	function attribute( &$row, &$lists )
	{
		LdapViewAttributes::setAttributeToolbar();
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
			<td width="20%" class="key"><label for="userdn"> <?php echo JText::_( "User's DN" ); ?>:
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
