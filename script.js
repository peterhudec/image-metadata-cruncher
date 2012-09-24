/**
 * @author Peto
 */

jQuery(document).ready(function($) {
	
	// table with custom metadata templates
	var $customMeta = $('#custom-meta-list');
	
	$customMeta.delegate('button', 'click', function (event){
			event.preventDefault();
			
			// remove row
			var $row = getRow($(event.target));
			var $name = $row.find('.name');
			
			// if template has a name ask for confirmation
			var value = $name.val();
			if(value){
				// if not empty mark and ask
				$row.addClass('to-be-removed');
				if(confirm('Are you sure you want to remove the template "' + value + '"?')){
					// if confirmed remove row
					$row.remove();
				}else{
					// unmark
					$row.removeClass('to-be-removed');
				}
			}else{
				// remove
				$row.remove();
			}
			
		});
	
	$customMeta.delegate('input.name', 'keyup', function(event) {
			$name = $(event.target)
			var $row = getRow($name);
			var $template = $row.find('td:nth-child(2) > input');
			
			var value = $name.val();
			// validate name
			// if (value.match(/^[^\s]+$/)) {
				// $name.removeClass('error');
				// // update name attribute of template input field
				// $template.attr('name', 'mc[custom_meta]['+ value +']');
			// } else{
				// $name.addClass('error');
			// };
			$template.attr('name', 'mc[custom_meta]['+ value +']');
		})
	
	function getRow($element) {
		return $element.parent().parent();
	}
	
	$('#add-custom-meta').click(function(event) {
		event.preventDefault();
		
		// create cells
		
		// on keyup changes name attr of $template
		var $name = $('<input type=text class="name" />');		
		var $template = $('<input type=text class="template" />'); // this field will be saved upon submit
		var $remove = $('<button class="button">Remove</button>');
		
		// create row
		var $row = $('<tr>').append($('<td>').append($name), $('<td>').append($template), $('<td>').append($remove));
			
		$customMeta.append($row);
	});
});

