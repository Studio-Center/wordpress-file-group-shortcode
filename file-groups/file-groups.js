var fileGroups = {
	init: function () {
		var $ = jQuery;

		// If the file upload field exists, clear it.  If a file was selected and then the page refreshed, FF will not clear the field.  IE/Chrome OK
		if ($('#file0').length)
			$('#file0').attr('type', 'file');
	
		// if the queueContainer div exists, we are editing/creating a file group.
		// hide the extraneous publish/update actions
		// TODO: we should be able to only add this script to pages where it's appropriate - mitcho
		if ($('#queueContainer').length) {
			$('#minor-publishing').hide();
		}

		var add_related = function () {
			// all the tags, comma delimited
			var tags = jQuery('#tax-input-post_tag').val();
	
			if (!tags.length) {
				alert('You may want to add some tags to this post before creating a related file group.');
				return;
			}
			
			window.open(fg_related_url + tags, '_blank');
		};
	
		var button = $('<input type="button" class="button" value="Create related file group"/>')
									.css('margin-top', '-5px')
									.click(add_related);
		if(document.getElementById('media-buttons'))
			$('#media-buttons').empty().append(button); // WP 3.1 or below
		else 
			$('#wp-content-media-buttons').empty().append(button); // WP 3.3.1 and above.  Not exactly sure when this div was renamed

	},
	
	enQueue: function (element) {
		// TODO: rewrite using jQuery
		
		// adds a file to the list of files queued for upload
		var fullpath = element.value; // IE, FF, and Chrome all handle the security issues surrounding the value of 
		var filename = fullpath.match(/[^\/\\]+$/); // an <input type='field'> differently.  We want just the actual filename. 
		// what number file is this we're adding to the queue?
		var num = parseInt(element.id.match(/\d+/));
		// alert(num);
		// add an element to the document that displays the name of the file just added
		var queueFile = document.createElement('div');
		queueFile.setAttribute('class', 'queuedFile');
		queueFile.setAttribute('id', 'queuedFile' + num);

		queueFile.innerHTML = "<div class='fg_xit' alt='remove from queue' title='remove from queue' onclick='fileGroups.deQueue(" + num + ");'></div>" + filename;
		document.getElementById('queue').appendChild(queueFile);
	
		// add the name of the file just added to what will become the post_content string
		document.getElementById('fg_filenames').value += filename + ", ";
	
		// hide the input field that called this function
		element.style.display = 'none';
	
		// make a new input field, incrementing the name and id
		nextNum = num + 1;
		nextFile = document.createElement('input');
		nextFile.setAttribute('name', 'file[' + nextNum + ']');
		nextFile.setAttribute('id', 'file' + nextNum);
		nextFile.setAttribute('type', 'file');
		nextFile.setAttribute('size', '42');
		nextFile.onchange = function () {
			fileGroups.enQueue(this);
		}
		document.getElementById('inputs').appendChild(nextFile);
	},
	
	deQueue: function (num) {
		// remove a file from the upload queue
		// delete its input element from the document
		jQuery('#file' + num).remove();
		// delete its name from the displayed list of files
		jQuery('#queuedFile' + num).remove();
	}
};

jQuery(window).load(fileGroups.init);
