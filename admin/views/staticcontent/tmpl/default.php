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
				<a target="_blank" href="<?php echo $this->baseUri; ?><?php echo $item; ?>"><?php echo $item; ?></a>
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
<script type="text/javascript">
function doRequest(object)
{
	var myRequest = new Request({
	    url: object.url,
	    onRequest: function(){
	    	$$('td.center').set('html','<br /><img src="components/com_staticcontent/images/loading.gif" /><br /><?php echo JText::_('COM_STATICCONTENT_AJAX_LOADING_MESSAGE'); ?>');
	    },
	    onFailure: function(){
	    	$$('td.center').set('html','<?php echo JText::_('COM_STATICCONTENT_AJAX_FAILURE'); ?>');
	    },
	    onTimeout: function(){
	    	$$('td.center').set('html','<?php echo JText::_('COM_STATICCONTENT_AJAX_TIMEOUT'); ?>');
	    },
	    onSuccess: function(responseText, responseXML){
		    alert(responseText);
			$('adminForm').submit();
	    }
	}).send(object.data);
}
function requestCustomItems(list)
{
	if (list.length == 0) {
		alert('<?php echo JText::_('COM_STATICCONTENT_NO_MENU_ITEMS_SELECTED'); ?>');
		return false;
	}

	var data = [];
	list.each(function(item){
		data.push(item.name+'='+item.value);
	});
	
	doRequest({url: '<?php echo JURI::root(); ?>index.php?option=com_staticcontent&task=menuitems',data: data.join('&') });
}
function requestAllItems()
{
	doRequest({url: '<?php echo JURI::root(); ?>index.php?option=com_staticcontent&task=allpages'});
}

</script>