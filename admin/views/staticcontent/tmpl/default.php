<form action="<?php echo JRoute::_('index.php?option=com_staticcontent&view=staticcontent'); ?>" method="post" name="adminForm" id="adminForm">
<table class="adminlist">
<thead>
	<tr>
		<th><?php echo JText::_('COM_STATICCONTENT_HTML_TITLE_HTML_FILES'); ?></th>
	</tr>
</thead>
<tbody>
	<?php if (empty($this->items)): ?>
		<tr class="">
			<td class="center">
				<p><?php echo JText::_('COM_STATICCONTENT_HTML_GENERATE_HTML_MESSAGE'); ?></p>
			</td>
		</td>	
	<?php else: ?>
		<?php foreach($this->items as $item): ?>
		<tr class="">
			<td class="center">
				<a target="_blank" href="<?php echo JURI::root(); ?>html/<?php echo $item; ?>"><?php echo $item; ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
</tbody>
</table>
	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>