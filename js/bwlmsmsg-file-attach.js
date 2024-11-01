    var bwlms_message_Index = 1;
    function bwlms_message_get_by_id(id) { return document.getElementById(id); }
    function bwlms_message_create_element(name) { return document.createElement(name); }
    function bwlms_message_remove_element(id) {
        var e = bwlms_message_get_by_id(id);
        e.parentNode.removeChild(e);
    }
    function bwlms_message_add_new_file_field() {
        var maximum = bwlms_message_attachment_script.maximum;
        var num_img = jQuery('input[name="bwlms_message_upload[]"]').size() + jQuery("a.delete").size();
        if((maximum!=0 && num_img<maximum) || maximum==0) {
            var id = 'p-' + bwlms_message_Index++;

            var i = bwlms_message_create_element('input');
            i.setAttribute('type', 'file');
            i.setAttribute('name', 'bwlms_message_upload[]');

            var a = bwlms_message_create_element('a');
			a.setAttribute('class', 'bwlms-message-attachment-field bwlms-attached-del-btn');
            a.setAttribute('href', '#');
            a.setAttribute('divid', id);
            a.onclick = function() { bwlms_message_remove_element(this.getAttribute('divid')); return false; }
            a.appendChild(document.createTextNode(bwlms_message_attachment_script.remove));

            var d = bwlms_message_create_element('div');
            d.setAttribute('id', id);
            //d.setAttribute('style','width:100%;')
            d.setAttribute('class','row bwlmsmsgth-file-attach-container');

            d.appendChild(i);
            d.appendChild(a);

            bwlms_message_get_by_id('bwlms_message_upload').appendChild(d);

        } else {
            alert(bwlms_message_attachment_script.max_text+' '+bwlms_message_attachment_script.maximum);
        }
    }
	function bwlms_message_listener() {
		bwlms_message_add_file_field();
		bwlms_message_hide_file_field();
	}
		
    setInterval("bwlms_message_listener()", 1000);

    function bwlms_message_add_file_field() {
        var count = 0;
        jQuery('input[name="bwlms_message_upload[]"]').each(function(index) {
            if ( jQuery(this).val() == '' ) {
                count++;
            }
        });
        var maximum = bwlms_message_attachment_script.maximum;
        var num_img = jQuery('input[name="bwlms_message_upload[]"]').size() + jQuery("a.delete").size();
        if (count == 0 && (maximum==0 || (maximum!=0 && num_img<maximum))) {
            bwlms_message_add_new_file_field();
        }
    }
	function bwlms_message_hide_file_field() {
        var maximum = bwlms_message_attachment_script.maximum;
        var num_img = jQuery('input[name="bwlms_message_upload[]"]').size() + jQuery("a.delete").size();
        if (maximum!=0 && num_img>maximum-1) {
            jQuery('#bwlms-message-attachment-field-add').hide();
        } else {
			jQuery('#bwlms-message-attachment-field-add').show();
			jQuery('#bwlms-message-attachment-note').html('');
		}
    }