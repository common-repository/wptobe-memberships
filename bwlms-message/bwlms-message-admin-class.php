<?php

if (!class_exists('WptobeMsgadminClass'))
{
  class WptobeMsgadminClass
  {
	private static $instance;
	
	public static function init(){
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
    }


    function addAdminPage()
    { 
//		add_menu_page(
//			'Wptobe Message', 
//			'Wptobe Message', 
//			'manage_options', 
//			'bwlms-message-admin-settings', 
//			array(&$this, 'admin_settings')
//		);
		
		add_submenu_page(
			'wptobemem-menu', 
			__('Message Setting','wptobemem'),
			__('Messages','wptobemem'), 
			'manage_options', 
			'bwlms-message-admin-settings',//menu-slug 
			//'bwlmslevel-membershiplevels', 
			array(&$this, 'admin_settings')
		);
    }

	function admin_settings(){

		  $token = bwlms_message_create_nonce();
		  //$actionURL = admin_url( 'admin.php?page=bwlmslevel-membershiplevels' );
		  $actionURL = admin_url( 'admin.php?page=wptobemem-menu' );
		  $ReviewURL = '';
		  $capUrl = '';
		  
		  if(isset($_POST['bwlms-message-admin-settings_submit'])){ 

			$errors = $this->admin_settings_action();
			if(count($errors->get_error_messages())>0)
			{
				echo bwlms_message_error($errors);
			}
			else
			{
				//echo'<div id="message" class="updated fade">' .__("Options successfully saved.", 'wptobemem'). ' </div>';
			}
		  }

		 // Admin Membership Settings>Messages settings
		echo "
			<form method='post' action='$actionURL'>
				  <input type='hidden' name='num_messages' value='".bwlms_message_get_option('num_messages',50)."' /> 
				  <input type='hidden' name='messages_page' value='".bwlms_message_get_option('messages_page',15)."' /> 
				  <input type='hidden' name='user_page' value='".bwlms_message_get_option('user_page',50)."' />
				  <input type='hidden' id='bwlms-message-attachment-checkbox' name='allow_attachment' value='1'  />

				<input class='button-primary' type='submit' name='bwlms-message-admin-settings_submit' value='".__("Save Changes", 'wptobemem')."' />
				<input type='hidden' name='token' value='$token' />
				  ";
				?>
				
				<div class="bwlmsmsg_admin_settings_wrapper">
					<div class="row fullwidthrow_msg_admin bwlmsmsg_admin_content_row">
						<div class="row">
							<div class="small-4 large-4 columns bwlmsmsg-field-title"><?php echo __("Maximum attachment size", 'wptobemem')?> </div>
							<div class="small-4 large-4 columns"><input type='text' name='attachment_size' value='<?php echo bwlms_message_get_option('attachment_size','4MB')?>' /></div>
							<div class="small-4 large-4 columns"> 	</div>
						</div>
						<div class="row">
							<div class="small-4 large-4 columns bwlmsmsg-field-title"><?php echo __("Maximum number of attachment?", 'wptobemem')?> </div>
							<div class="small-4 large-4 columns"><input type='text' name='attachment_no' value='<?php echo bwlms_message_get_option('attachment_no','4')?>' /></div>
							<div class="small-4 large-4 columns"> 	</div>
						</div>
					</div>
				</div>

			<?php 

			echo "
			</form>";
	}


	function admin_settings_action(){

			if (isset($_POST['bwlms-message-admin-settings_submit']))
			{

			  $errors = new WP_Error();

			  if (!bwlms_message_verify_nonce($_POST['token']))
			  $errors->add('invalid_token', __('Invalid Token. Please try again!', 'wptobemem'));
			  
			  do_action('bwlms_message_action_admin_setting_before_save', $errors);//첨부파일 갯수 에러체크
			  
				if( current_user_can('manage_options') && (count($errors->get_error_codes())==0))
				{

					$msgadmin_options = get_option('BWLMS_MESSAGE_admin_options');
					$msgadmin_options["attachment_size"] =  sanitize_text_field($_POST['attachment_size']);
					$msgadmin_options["attachment_no"] = intval($_POST['attachment_no']);
					$msgadmin_options["messages_page"] = 15;//한페이지에 출력할 메시지 개수
					update_option('BWLMS_MESSAGE_admin_options', $msgadmin_options);

				}
			
				return $errors;
			}

			return false;
	}

  } 
}
	add_action('wp_loaded', array ( 'WptobeMsgadminClass', 'init' )); 
?>