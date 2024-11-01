<?php

if (!class_exists('WptobeMsgFileattachClass'))
{
  class WptobeMsgFileattachClass
  {
	private static $instance;
	
	public static function init(){
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
    }
	
    function actions_filters() {

			add_action ('bwlms_message_action_admin_setting_before_save', array(&$this, 'settings_action'));
			add_action ('bwlms_message_display_after_parent_message', array(&$this, 'display_attachment'));
			add_action ('bwlms_message_display_after_reply_message', array(&$this, 'display_attachment'));
			add_action ('bwlms_message_message_before_delete', array(&$this, 'delete_file'), 10, 2 );
			add_action ('bwlms_message_announcement_before_delete', array(&$this, 'delete_announcement_file') );
			
			add_action ('bwlms_message_announcement_form_after_content', array(&$this, 'attachment_fields'));
			add_action ('bwlms_message_action_announcement_before_add', array(&$this, 'check_upload'));
			add_action ('bwlms_message_after_add_announcement', array(&$this, 'upload_attachment'), 10, 2 );
			add_action ('bwlms_message_display_after_announcement_content', array(&$this, 'display_attachment'));
			
			if ( '1' == bwlms_message_get_option('allow_attachment',0)) {
			add_action ('bwlms_message_message_form_after_content', array(&$this, 'attachment_fields'));
			add_action ('bwlms_message_reply_form_after_content', array(&$this, 'attachment_fields'));
			add_action ('bwlms_message_action_message_before_send', array(&$this, 'check_upload'));
			add_action ('bwlms_message_action_message_after_send', array(&$this, 'upload_attachment'));//, 10, 2 );
			}
    }
	
	function settings_action ( $errors ) {
			if ( !ctype_digit($_POST['attachment_no']))
			$errors->add('invalid_att_no', __('You must enter a valid number as attachment number!', 'wptobemem'));
	}
		

	function attachment_fields() {
	
		wp_enqueue_script( 'bwlms-message-attachment-script' );
		?>
		<div class='row bwlmsmsgth-file-attach-row' >
			<div class='bwlmsmsgth-file-attach-block' id="bwlms_message_upload">
				<div class='row bwlmsmsgth-file-attach-container' id="p-0">
					<input class='file-attachment-input' type='file' name='bwlms_message_upload[]' /><a href="#" onclick="bwlms_message_remove_element('p-0'); return false;" class = 'bwlms-message-attachment-field bwlms-attached-del-btn'><?php _e('x', 'wptobemem') ; ?></a>
			</div>
		</div>

		<?php
    }
    //}
// }

function check_upload($errors) {
    $mime = get_allowed_mime_types();
    $size_limit = (int) wp_convert_hr_to_bytes(bwlms_message_get_option('attachment_size','4MB'));
    $fields = (int) bwlms_message_get_option('attachment_no', 4);

    for ($i = 0; $i < $fields; $i++) {
        $tmp_name = isset( $_FILES['bwlms_message_upload']['tmp_name'][$i] ) ? basename( $_FILES['bwlms_message_upload']['tmp_name'][$i] ) : '' ;
        $file_name = isset( $_FILES['bwlms_message_upload']['name'][$i] ) ? basename( $_FILES['bwlms_message_upload']['name'][$i] ) : '' ;

        //if file is uploaded
        if ( $tmp_name ) {
            $attach_type = wp_check_filetype( $file_name );
            $attach_size = $_FILES['bwlms_message_upload']['size'][$i];

            //check file size
            if ( $attach_size > $size_limit ) {
                $errors->add('AttachmentSize', sprintf(__( "Attachment (%s) file is too big", 'wptobemem' ),$file_name));
            }

            //check file type 
            if ( !in_array( $attach_type['type'], $mime ) ) {
                $errors->add('AttachmentType', sprintf(__( "Invalid attachment file type.Allowed Types are (%s)", 'wptobemem' ),implode(',',$mime)));
            }
        } // if $filename
    }// endfor

    //return $errors;
}

function upload_attachment( $message_id ) {
    if ( !isset( $_FILES['bwlms_message_upload'] ) ) {
        return false;
    }
	add_filter('upload_dir', array(&$this, 'upload_dir'));
	
    $fields = (int) bwlms_message_get_option('attachment_no', 4);

    for ($i = 0; $i < $fields; $i++) {
        $tmp_name = isset( $_FILES['bwlms_message_upload']['tmp_name'][$i] ) ? basename( $_FILES['bwlms_message_upload']['tmp_name'][$i] ) : '' ;

        //if ( $file_name ) {
            if ( $tmp_name ) {
                $upload = array(
                    'name' => $_FILES['bwlms_message_upload']['name'][$i],
                    'type' => $_FILES['bwlms_message_upload']['type'][$i],
                    'tmp_name' => $_FILES['bwlms_message_upload']['tmp_name'][$i],
                    'error' => $_FILES['bwlms_message_upload']['error'][$i],
                    'size' => $_FILES['bwlms_message_upload']['size'][$i]
                );

                $this->upload_file( $upload, $message_id);
            }//file exists
        }// end for
    //}
	remove_filter('upload_dir', array(&$this, 'upload_dir'));
}

	function upload_dir($upload) {
	/* Append year/month folders if that option is set */
		$subdir = '';
        if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
                $time = current_time( 'mysql' );

            $y = substr( $time, 0, 4 );
            $m = substr( $time, 5, 2 );

            $subdir = "/$y/$m";    
        }
	$upload['subdir']	= '/bwlms-message' . $subdir;
	$upload['path']		= $upload['basedir'] . $upload['subdir'];
	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
	return $upload;
	}


function upload_file( $upload_data, $message_id ) {
	global $wpdb;
	if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
    $movefile = wp_handle_upload( $upload_data, array('test_form' => false) );

    //if ($message_id && $movefile['type']&& $movefile['url'] && $movefile['file']) {
	if ($message_id && $movefile['url'] && $movefile['file']) {
	
		$serialized_file = maybe_serialize( $movefile );
		
		$result = $wpdb->insert( BWLMS_MESSAGE_META_TABLE, array( 'message_id' => $message_id, 'field_name' => 'attachment','field_value' => $serialized_file ), array ( '%d', '%s', '%s' ));
		
		if ( $result )
        return true;
    }

    return false;
}

	function display_attachment($message_id) {
	
		$attachment = bwlms_message_get_message_meta($message_id, 'attachment');
		$token = bwlms_message_create_nonce('download');
		$attached_file_print = "";

		if ($attachment) {
			  echo "
					<div class='row bwlmsmsgth-r-rowC' >
					  <div class='small-2 columns bwlmsmsgth-r-rowC2'>  
					  </div>
					  <div class='small-10 columns bwlmsmsgth-r-rowC10'>"
					   . __('Attachment', 'wptobemem') ."
					  </div>
					</div>
					";
  
				  foreach ($attachment as $meta){
				  
					  $unserialized_file = maybe_unserialize( $meta->field_value );

						if ( $unserialized_file['type'] && $unserialized_file['url'] && $unserialized_file['file'] ) {
						$attachment_id = $meta->meta_id; 
						
						$attached_file_print .= "
								<div class='row bwlmsmsgth-r-rowC' >
								  <div class='small-2 columns bwlmsmsgth-r-rowC2'>  
								  </div>
								  <div class='small-10 columns bwlmsmsgth-r-rowC10'>
									<a href='".bwlms_message_action_url("download&amp;id=$attachment_id&amp;token=$token")."' title='Download ". basename($unserialized_file['url'])."'>". basename($unserialized_file['url'])."</a>  
								  </div>
								</div>
							"; 
						}
					}//foreach
					//var_dump($attached_file_print);
					echo $attached_file_print;
			}//if
		}
		
	function delete_file( $delID, $ids ) {
		//메시지를 삭제할 때 첨부파일이 있으면 삭제하고 True 반환/ 없으면 False 반환
		global $wpdb;
		
		$id = implode(',',$ids);
		  $results = $wpdb->get_col($wpdb->prepare("SELECT field_value FROM ".BWLMS_MESSAGE_META_TABLE." WHERE field_name = %s AND message_id IN ({$id})", 'attachment' ));

		foreach ($results as $result){
			$unserialized_file = maybe_unserialize( $result );
			if ( $unserialized_file['file'] ) 	unlink($unserialized_file['file']);

		}
		
		if ( isset($result) ){
				return TRUE;
		}
		
		return FALSE;
    }
	
	function delete_announcement_file( $delID ) {
	global $wpdb;
		
	  $results = $wpdb->get_col($wpdb->prepare("SELECT field_value FROM ".BWLMS_MESSAGE_META_TABLE." WHERE field_name = %s AND message_id = %d", 'attachment', $delID ));
	 
	  foreach ($results as $result){
	  	$unserialized_file = maybe_unserialize( $result );
		if ( $unserialized_file['file'] )
		unlink($unserialized_file['file']);
		}
		
		if ( isset($result) && $result )
		{
			return true;
		}
		else
		{
			return false;
		}
    }


  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(WptobeMsgFileattachClass::init(), 'actions_filters'));
?>