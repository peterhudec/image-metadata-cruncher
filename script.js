/**
 * @author Peter Hudec
 */

jQuery(document).ready(function($) {
	var prefix = 'image_metadata_cruncher';
	
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
			var $template = $row.find('td:nth-child(2) > .hidden-input');
			
			// validate
			var value = $name.val();
			$template.attr('name', prefix + '[custom_meta]['+ value +']');
		})
	
	function getRow($element) {
		return $element.parent().parent();
	}
	
	$('#add-custom-meta').click(function(event) {
		event.preventDefault();
		
		// create cells
		
		// on keyup changes name attr of $template
		var $name = $('<input type="text" class="name" />');
		var $ce = $('<div class="ce" contenteditable="true"></div>');
		//var $template = $('<input type="hidden" class="hidden-input template" />'); // this field will be saved upon submit
		var $template = $('<textarea class="hidden-input template"></textarea>'); // this field will be saved upon submit
		var $remove = $('<button class="button">Remove</button>');
		
		// create row
		var $row = $('<tr>').append($('<td>').append($name), $('<td>').append($ce, $template), $('<td>').append($remove));
			
		$customMeta.append($row);
	});
	
	///////////////////////////////////////////
	// Tag syntax highlighting
	///////////////////////////////////////////
	
	// events
	$('#metadata-cruncher').delegate('.ce', 'keyup', function(event) {
		var $target = $(event.target);
		var text = highlight(event);
		$out = $target.parent().children('.hidden-input');
		$out.html(text);
		
		// find and replace all &nbsp; entities which break functionality
		$out.html($out.html().replace(/&nbsp;/g, ' '));
	})
	
	rangy.addInitListener(function(r){
		// triger the keyup event on content editable elements when rangy is ready
		$('#metadata-cruncher .ce').keyup();
	});
	
	$('#submit').click(function() {
		// before submitting make sure, that all textareas are properly filled out
		$('#metadata-cruncher .ce').keyup();
	});
	
	function wrap(value, className){
	    if(value) {
	        return '<span class="' + className + '">' + value + '</span>';
	    }
	}
	
	function addToResult(result, value, className){
	    if(value){
	    	if(className){
	    		result += wrap(value, className);
	    	}else{
	    		result += value;
	    	}
	    }
	    return result;
	}
	
	function re() {
		return RegExp(Array.prototype.join.call(arguments, ''), 'g');
	}
	
	function safeKeystroke(event){
		
		var unsafeShiftKeys = [
			16, // shift
			33, // page up
			34, // page down
			35, // end
			36, // home
			37, // left
			38, // up
			39, // right
			40  // down
		];
		
		var unsafeCtrlKeys = [
			17, // ctrl
			67, // c
			65, // a
			89  // y
		];
		
		var shiftDanger = event.shiftKey && jQuery.inArray(event.which, unsafeShiftKeys) > -1;
		var ctrlDanger = event.ctrlKey && jQuery.inArray(event.which, unsafeCtrlKeys) > -1;
		var tabDanger = event.which == 9;
				
		var safe = !shiftDanger && !ctrlDanger && !tabDanger;
		
		if(safe){
			return true;
		}else{
			return false;
		}
	}
	
	function highlight(event) {	
		if(safeKeystroke(event)){
			// do highlighting
			var $ = jQuery;
			
			
			var $in = $(event.target);
			
			var selection = rangy.saveSelection();
			
			// replace rangy boundary markers with ▨ and save them to temporary array
			var p = /(<span[\s]*id="selectionBoundary[^<>]*>[^<>]*<\/[\s]*span>)/g;
			var markers = [];
			var html = $in.html().replace(p, function(match){
		        // store found marker...
		        markers.push(match);
		        // ...and replace with identifier
		        return '▨';
		   });
		   // put it back to input
		   $in.html(html);
		   
		   // extract text and add markup
		   var newHTML = applyMarkup($in.text());   
		   
		   // restore rangy identifiers
		   newHTML = newHTML.replace('▨', function(match){
		        // retrieve from temp storage
		        return markers.shift();
		   });
		   
		   // update input html
		   $in.html(newHTML);
		   
		   // restore rangy selection
		   rangy.restoreSelection(selection);
		   
		   return $in.text();
		}
	}
	
	function applyMarkup(input) {
		var p = re(
			'({)', // (1) opening bracket
			
			'([\\s▨]*)', // (2) space1
			
            '(', // (3) begin keywords
            '(?:[\\w:.>▨]{2,}|[^▨\\s]{1})', // must contain at least one character
            '(?:',
            '[\\s▨]*\\|[\\s▨]*(?:[\\w:.>▨]{2,}|[^▨\\s]{1})', // zero or more ( | abcd ) groups
            ')*',
            ')', // end keywords
            
            '([\\s▨]*)', // (4) space2
			
            '(?:', // begin success group
            '(@[\\s▨]*)', // (5) success identifier "@"
            /("(?:(?:\\▨|\\)?.)*?")/.source, // (6) success value
            ')?', // end success group
            
            '([\\s▨]*)', // (7) space3
            
            '(?:', // begin default group
            '([\\?][\\s▨]*)', // (8) default identifier "?"
            /("(?:(?:\\▨|\\)?.)*?")/.source, // (9) default value
            ')?', // end default group
            
            '([\\s▨]*)', // (10) space4
            
            '(?:', // begin delimiter group
            '(:[\\s▨]*)', // (11) delimiter identifier ":"
            /("(?:(?:\\▨|\\)?.)*?")/.source, // (12) delimiter value
            ')?', // end delimiter group
            
			'([\\s▨]*)', // (13) space5
			
			'(})' // (14) closing bracket
		)
		
		return input.replace(p, function(
				m,
				openingBracket, // (1)
				space1, // (2)
				keywords, // (3)
				space2, // (4)
				successIdentifier, // (5)
				successValue, // (6)
				space3, // (7)
				defaultIdentifier, // (8)
				defaultValue, // (9)
				space4, // (10)
				delimiterIdentifier, // (11)
				delimiterValue, // (12)
				space5, // (13)
				closingBracket // (14)
			) {
			console.log(m);
			var result = '';
			result = addToResult(result, openingBracket, 'opening bracket');
			result = addToResult(result, space1);
			result = addToResult(result, processKeys(keywords), 'keys group');
			result = addToResult(result, space2);
			result = addToResult(
				result,
				wrap(successIdentifier, 'identifier') + wrap(successValue, 'value'),
				'success group'
			);
			result = addToResult(result, space3);
			result = addToResult(
				result,
				wrap(defaultIdentifier, 'identifier') + wrap(defaultValue, 'value'),
				'default group'
			);
			result = addToResult(result, space4);
			result = addToResult(
				result,
				wrap(delimiterIdentifier, 'identifier') + wrap(delimiterValue, 'value'),
				'delimiter group'
			);
			result = addToResult(result, space5);
			result = addToResult(result, closingBracket, 'closing bracket');
			return wrap(result, 'tag group');
		});
	}
	
	function processKeys(content) {
		var p = re(
			'([^:\\s]+)', // prefix
            '(?:',
            '(:)', // colon
            '([^|\\s]+)', // key
            ')?',
            '([\\s▨]*\\|)?' // pipe
		);
		return content.replace(p, function(m, prefix, colon, key, pipe){
			var result = '';
			result = addToResult(result, prefix, 'prefix');
			result = addToResult(result, colon, 'colon');
			result = addToResult(result, processKey(key), 'key');
			result = addToResult(result, pipe, 'pipe');
			return result;
		});
	}
	
	function processKey(content) {
		var p = re(
			'([^>\\s]+)', // key
            '(>)?' // gt
		);
		if(content){
			return content.replace(p, function(m, part, gt){
				var result = '';
				result = addToResult(result, part, 'part');
				result = addToResult(result, gt, 'gt');
				return result;
			});
		}
	}
	
	
});

