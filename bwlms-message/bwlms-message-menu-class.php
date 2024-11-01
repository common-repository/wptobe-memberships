<?php

if (!class_exists('bwlms_message_menu_class'))
{
  class bwlms_message_menu_class
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
			add_action ('bwlms_message_menu_button', array(&$this, 'newmessage'));
			add_action ('bwlms_message_menu_button', array(&$this, 'messagebox'));
			add_action ('bwlms_message_menu_button', array(&$this, 'settings'));
    }

	function settings() {
	 $class = 'bwlms-message-button';

	 if ( is_page( bwlms_message_page_id() ) && isset($_GET['bwlmsmessageaction']) && sanitize_text_field($_GET['bwlmsmessageaction']) == 'settings')
		 $class = 'bwlms-message-button-active';
	}

	function newmessage() {
	 $class = 'bwlms-message-button';
	 if ( is_page( bwlms_message_page_id() ) && isset($_GET['bwlmsmessageaction']) && sanitize_text_field($_GET['bwlmsmessageaction']) == 'newmessage')
		$class = 'bwlms-message-button-active';

		$newmsgbtn = '
			<!-- Button trigger modal -->
			<div class="bwlms_new_message_btn_row">
				<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">';
				$newmsgbtn .= __('New Message', 'wptobemem');
				$newmsgbtn .='
				</button>
			</div>';

		$newmsgbtn .='
			<!-- Modal -->
			<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			  <div class="modal-dialog" role="document">
				<div class="modal-content">
				  <div class="modal-header">
					';
						$newmsgbtn .= __('New Message', 'wptobemem');
						$newmsgbtn .= '
					
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					  <span aria-hidden="true">&times;</span>
					</button>
				  </div>
				  <div class="modal-body">';
					$newmsgbtn .= do_shortcode('[bwlms-message-new]');
					$newmsgbtn .= '
				  </div>

				</div>
			  </div>
			</div>
		';

		$newmsgbtn .='
		</div><!--Centered-->
		</div><!--Row bwlms-message-ui.php function bwlms_message_message_box() [202 Line] -->
		 ';
 		 echo $newmsgbtn;
	  }
	  
	  function messagebox() {
		$numNew = bwlms_message_get_new_message_button();
		$class = 'bwlms-message-button';
		 if ( is_page( bwlms_message_page_id() ) && ( !isset($_GET['bwlmsmessageaction']) || sanitize_text_field($_GET['bwlmsmessageaction']) == 'messagebox') )
		 $class = 'bwlms-message-button-active';
	  }

  } 
} 

add_action('wp_loaded', array(bwlms_message_menu_class::init(), 'actions_filters'));
?>