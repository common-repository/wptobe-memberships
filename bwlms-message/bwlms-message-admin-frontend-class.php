<?php

if (!class_exists('bwlms_message_admin_frontend_class'))
{
  class bwlms_message_admin_frontend_class
  {
	private static $instance;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
	
    function actions_filters()
    {
	if ( current_user_can('manage_options'))
		{
			add_action('bwlms_message_menu_button', array(&$this, 'menu'));
			add_filter('bwlms_message_message_headline', array(&$this, "title"), 10, 2 );
			add_filter('bwlms_message_user_total_message_count_allmessages', array(&$this, "total"), 10, 2 );
			add_filter('bwlms_message_user_messages_allmessages', array(&$this, "messages"), 10, 2 );
			add_filter('bwlms_message_delete_message_url', array(&$this, "delete_url"), 10, 2 );
			add_filter('bwlms_message_filter_status_display', array(&$this, "status"), 10, 3 );
			add_action('bwlms_message_switch_deletemessageadmin', array(&$this, "delete"));

			add_filter('bwlms_theme_message_delete_message_url', array(&$this, "theme_delete_url"), 10, 2 );
		}
    }
	
	function menu() {
	 $class = 'bwlms-message-button';
	 if (isset($_GET['bwlmsmessageaction']) && sanitize_text_field($_GET['bwlmsmessageaction']) == 'allmessages')
	 $class = 'bwlms-message-button-active';
	}
		
	function title( $title, $action ) {
	
		if ( $action == 'allmessages' && current_user_can('manage_options') ) {
				$title =  __("All Messages", 'wptobemem' )  ;
		}
		return $title;
		}
		
	function total( $count, $action ) {
		global $wpdb;
		
		if ( current_user_can('manage_options') ) {
		$get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE parent_id = %d AND (status = %d OR status = %d)", 0, 0, 1 ));
	  $count = $wpdb->num_rows;
	  } else
	  $count = 1; //Not to show empty message error. 
		
		return $count;
		}
	
	function total_unread() {
	
		global $wpdb;
		
		if ( current_user_can('manage_options') ) {
		$get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE parent_id = %d AND status = %d", 0, 0 ));
	  $count = $wpdb->num_rows;
	  } else 
	  $count = 0;
	  
	   if ( $count )
	   $button = " (<font color='red'>$count</font>)";
	   else
	   $button = '';
		
		return $button;
	}
		
	function messages( $messages, $action ) {
		global $wpdb;
		
		$page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;
		$start = $page * bwlms_message_get_option('messages_page', 50);
        $end = bwlms_message_get_option('messages_page', 50);
		
		if ( current_user_can('manage_options') ) {
		$messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGES_TABLE." WHERE parent_id = %d AND (status = %d OR status = %d) ORDER BY last_date DESC LIMIT %d, %d", 0, 0, 1, $start, $end ));
	  } else
	  $messages = array();
		
		return $messages;
	}
		
	function delete_url( $del_url, $id ) {
	
		
		if ( current_user_can('manage_options') ) {
		$token = bwlms_message_create_nonce('delete_message_admin');
		$del_url = bwlms_message_action_url("deletemessageadmin&id=$id&token=$token");
		
		}
		return $del_url;
	}
		
	function status( $status, $msg, $action ) {
	
		if ( $action == 'allmessages' && current_user_can('manage_options') ) {
			
			if ( $msg->status == 0 )
            $status = "<font color='#FF0000'>".__("Unread", 'wptobemem')."</font>";
          else
            $status = __("Read", 'wptobemem');
		
		}
		return $status;
	}
		
	function delete() {
      global $wpdb;

      $delID = absint( $_GET['id'] );
	  
	  if (!bwlms_message_verify_nonce( $_GET['token'], 'delete_message_admin')){
		echo "<div id='bwlms-message-error'>".__("Invalid Token!", 'wptobemem')."</div>";
		return;
	  }
	  if ( 0 == $delID ){
		echo "<div id='bwlms-message-error'>".__("Invalid message id!", 'wptobemem')."</div>";
		return;
	  }

      if ( current_user_can('manage_options') ){
			$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
			$id = implode(',',$ids);
	  
			do_action ('bwlms_message_message_before_delete', $delID, $ids);
	  
			$wpdb->query($wpdb->prepare("DELETE FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
			$wpdb->query("DELETE FROM ".BWLMS_MESSAGE_META_TABLE." WHERE message_id IN ({$id})");
	  }
	  else {
			echo "<div id='bwlms-message-error'>".__("No permission!", 'wptobemem')."</div>";
			return;
	  }
		
		echo "<div id='bwlms-message-success'>".__("The message was deleted successfully.", 'wptobemem')."</div>";

		return;
    }
	
	function theme_delete_url( $del_url, $id ) {
	
		
		if ( current_user_can('manage_options') ) {
		$token = bwlms_message_create_nonce('delete_message_admin');
		$del_url = bwlms_theme_message_action_url("deletemessageadmin&id=$id&token=$token");
		
		}
		return $del_url;
	}
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(bwlms_message_admin_frontend_class::init(), 'actions_filters'));
?>