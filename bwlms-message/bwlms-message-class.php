<?php
if (!class_exists("bwlms_message_main_class"))
{
  class bwlms_message_main_class
  {
    
	private static $instance;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }

	function newmsg_shortcode () 
	{
      global $user_ID;
	  if ($user_ID) {
		$out = $this->new_message_action_popup(); 
      }
      else{
        $out = "<div id='bwlms-message-error'>".__("You must be logged-in to send your message.", 'wptobemem')."</div>";
      }

      return apply_filters('bwlms_message_main_shortcode_output', $out);
	}

   function main_shortcode_output()
    {
      global $user_ID;

	  if ($user_ID)
      {
        $out = $this->Header();
        //Add Menu
		
		$switch = ( isset($_GET['bwlmsmessageaction'] ) && $_GET['bwlmsmessageaction'] ) ? $_GET['bwlmsmessageaction'] : 'messagebox';
			
        switch ($switch)
        {
			case has_action("bwlms_message_switch_{$switch}"):
				ob_start();
				do_action("bwlms_message_switch_{$switch}");
				$out .= ob_get_contents();
				ob_end_clean();
				break;
			 case 'newmessage':
				//New message popup window
				$out = $this->new_message();
				break;
			  case 'checkmessage':
				//$out .= $this->new_message_action();
				// $out .= $this->reply_message_action();
				  $out .= bwlms_message_message_box();
				  $out .= $this->Msg_newbtn();
				break;
			  case 'viewmessage':
				//메시지 개별 상세페이지
			    $out = $this->view_message();
				break;
			  case 'between':
				$out .= bwlms_message_message_box();
				break;
			  case 'deletemessage':
				$out .= $this->delete();
				
				$out .= bwlms_message_message_box();
				$out .= $this->Msg_newbtn();
				  
				break;
			  case 'settings':
				$out .= $this->user_settings();
				break;

			  default: //Message box is shown by Default
				$out = bwlms_message_message_box();
				$out .= $this->Msg_newbtn();
				break;
        }

        $out .= $this->Footer();
      }
      else
      {
        $out = "<div id='bwlms-message-error'>".__("You must be logged-in to view your message.", 'wptobemem')."</div>";
      }
      return apply_filters('bwlms_message_main_shortcode_output', $out);
    }

	
    function Header()
    {
      global $user_ID;

      $msgBoxSize = $this->getUserNumMsgs();

		$header = "";

	  ob_start();//New message counter
	  //do_action('bwlms_newmessage_counter', $user_ID); 
	  $header .= ob_get_contents();
	  ob_end_clean();
		
		$header .= "";
      return $header;
    }

    function Menu()
    {
	  ob_start();
	  //[New Message] button
	  do_action('bwlms_message_menu_button');
	  $menu = ob_get_clean();

      $menu .= "";
	  ob_start();
	  do_action('bwlms_message_display_before_content');
	  $menu .= ob_get_clean();
	  
      return $menu;
    }

    function Msg_newbtn()
    {
	  ob_start();
	  //[New Message] Button
	  do_action('bwlms_message_menu_button');
	  $newmsg = ob_get_clean();

      $newmsg .= "";
	  ob_start();
	  do_action('bwlms_message_display_before_content');
	  $newmsg .= ob_get_clean();
	  
      return $newmsg;
    }	

    function Footer()
    {
      $footer = '';
      return $footer;
    }
	
	function getUserNumMsgs()
    {
      global $wpdb, $user_ID;
      $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE ((to_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1)", $user_ID, $user_ID));
      return $wpdb->num_rows;
    }
	
	
	function user_settings()
    {
	  $subscriber_opt = "
		<div class='row fullwidthrow_bwlmsmessage'>

			<div class='small-12 medium-12 large-12 large-centered medium-centered small-centered columns bwlmsmessage_settings_col'>
				<div class='row bwlmsmessage-setting-shell-1'>

					<div class='small-3 medium-3 large-3 columns wptobememberships-settings-title'> 
						 Settings
					</div>
					<div class='small-3 medium-3 large-3 columns'> 
	  ";

		  $token = bwlms_message_create_nonce('user_settings');

		  $subscriber_opt .= "<form class='bwlmsmessage-settings-form' method='post' action='".bwlms_message_query_url('settings')."'>";
		  $subscriber_opt .= "
								<input type='hidden' name='token' value='$token' />
								<input class='wptobememberships-settings-submit' type='submit' name='bwlms-message-user-form_submit' value='".__("Save Changes", 'wptobemem')."' />";
		  $subscriber_opt .= "</div>";

			$subscriber_opt .= "<div class='small-6 medium-6 large-6 columns bwlmsmessage-shell-right'>";
			//세팅 저장성공/에러 메시지 
			if(isset($_POST['bwlms-message-user-form_submit'])){ 
				$errors = $this->user_settings_action();
				if(count($errors->get_error_messages())>0){
					
					$subscriber_opt .= "<div id='bwlms-message-error'>";
					$subscriber_opt .= bwlms_message_error($errors);
					$subscriber_opt .="</div>";
				}
				else{
					$subscriber_opt .= "<div id='bwlms-message-success'>";
					$subscriber_opt .=__('Settings updated successfully!', 'wptobemem');
					$subscriber_opt .="</div>";
				}
			}

	  $subscriber_opt .= "
					</div><!-- End 메시지 출력 -->
				</div><!--endof row-->
			</div><!--endof 10-->
		</div>";//end of row

	  
	  // 각 옵션 항목을 보여주는 Row
	  $subscriber_opt .= "
		<div class='row fullwidthrow_bwlmsmessage_settings'>

			<div class='small-12 medium-12 large-12 large-centered medium-centered small-centered columns bwlmsmessage_settings_col'>
				<div class='row bwlmsmessage-setting-shell-2'>

						<div class='small-3 medium-3 large-3 columns tabtitle_setinbox'> Inbox </div>
		";
	  $subscriber_opt .= "<div class='small-9 medium-9 large-9 columns tabcontent_setinbox'>";

	  $subscriber_opt .= "
      <input type='checkbox' name='allow_messages' value='1' ".checked(bwlms_message_get_user_option( 'allow_messages', 1), '1', false)."/>".__(" Allow others to send me messages?", 'wptobemem')."<br/>
	  
	  <input type='checkbox' name='allow_emails' value='1' ".checked(bwlms_message_get_user_option( 'allow_emails', 1), '1', false)."/>".__(" Email me when I get new messages?", 'wptobemem')."<br/>";
	  
	  ob_start();
	  do_action('bwlms_message_user_settings_form');
	  $subscriber_opt .= ob_get_contents();
	  ob_end_clean();

	  $subscriber_opt .="
      </form>";
  	
	  $subscriber_opt .= "
					</div><!--end of setting field -->
				</div><!--endof row-->
			</div><!--end of centered-->

		</div>";//end of row

      return $subscriber_opt;
    }

    function user_settings_action()
    {
      global $user_ID;
      if (isset($_POST['bwlms-message-user-form_submit']))
      {
	  $errors = new WP_Error();
	  
      $options = array(	'allow_emails' 	=> ( isset( $_POST['allow_emails'] ) )? 1 : '',
                    'allow_messages' => ( isset( $_POST['allow_messages'] ) )? 1 : ''
        );
		
	  if (!bwlms_message_verify_nonce($_POST['token'],'user_settings'))
		  $errors->add('invalidToken', __('Your Token did not verify, Please try again!', 'wptobemem'));
		  

	  do_action('bwlms_message_action_user_settings_before_save', $errors);
	  
	  $options = apply_filters('bwlms_message_filter_user_settings_before_save', $options, $errors); 
	  
		if(count($errors->get_error_codes())==0){
        update_user_option($user_ID, 'BWLMS_MESSAGE_user_options', $options);
		do_action('bwlms_message_user_settings_after_save', $user_ID);
		}
		return $errors;
      }
      return false;
    }
	
	function new_message()
    {
      global $user_ID;
	  $token = bwlms_message_create_nonce('new_message');
	  
      $to = (isset($_GET['to']))? $_GET['to']:'';
	  
		$message_to = ( isset( $_POST['message_to'] ) ) ? esc_html( $_POST['message_to'] ): bwlms_message_get_userdata( $to, 'user_login' );
		$message_top = ( isset( $_POST['message_top'] ) ) ? esc_html( $_POST['message_top'] ): bwlms_message_get_userdata($to, 'display_name');
		$message_title = ( isset( $_REQUEST['message_title'] ) ) ? esc_html( $_REQUEST['message_title'] ): '';
		$message_content = ( isset( $_REQUEST['message_content'] ) ) ? esc_textarea( $_REQUEST['message_content'] ): '';
		$parent_id = ( isset( $_POST['parent_id'] ) ) ? absint( $_POST['parent_id'] ): 0;
	
		
		// New message 페이지(플러그인) 전체를 감싸는 프레임
		$newMsg = "<div class='bwlms-newmsg-container'>";
		
		// New message 타이틀 행
		//$newMsg .= "<div class='bwlms-newmsg-titlerow'>".__( 'New Message','wptobemem')."</div>";

		// 새메시지 수신인 행
		$newMsg .= " 
			<div class='row bwlms-new-msg-title-row'>
				<div class='small-12 medium-12 large-12  newmsg-title-rowA columns'>
			";

        $newMsg .= "<form action='".bwlms_message_query_url('checkmessage')."' method='post' enctype='multipart/form-data'>";

		wp_enqueue_script( 'bwlms-message-script' );
			
		$MgsTo ="<noscript>".__('Username of recipient', 'wptobemem')."</noscript>";
		$MgsTo ="
				<input 
							type='hidden' 
							id='bwlms-message-message-to' 
							name='message_to' 
							autocomplete='off' 
							value='$message_to' />
				<input 
							type='text'
							class='bwlms-newmsg-inputbox'
							id='bwlms-message-message-top' 
							name='message_top' 
							placeholder='".__('Contact&#39;s username', 'wptobemem')."' 
							autocomplete='off' 
							value='$message_top' />
				";
		$newMsg .= apply_filters( 'bwlms_message_message_form_to_filter', $MgsTo, $message_to);

		$newMsg .= "
				</div>
			</div>
		";

		// 새메시지 타이틀 행
		$newMsg .= " 
			<div class='row bwlms-new-msg-title-row'>
				<div class='small-12  medium-12 large-12 newmsg-title-rowB columns'>
			";
		$newMsg .= "
					<input 
								type='text'
								class='bwlms-newmsg-inputbox'
								name='message_title' 
								placeholder='".__('Title', 'wptobemem')."' 
								maxlength='200' 
								value='$message_title' />
					";
		ob_start();
		do_action('bwlms_message_message_form_before_content');
		
		$newMsg .= "
				</div>
			</div>
		";

		// 에디터 행
		$newMsg .= " 
			<div class='row bwlms-new-msg-title-row'>
				<div class='small-12  medium-12 large-12 newmsg-title-rowC columns'>
			";
		wp_editor(	$message_content, 
						'message_content', 
						array(	'tinymce' => false,
								'teeny' => false,
								'media_buttons' => false, 
								'quicktags' => false,
								'editor_class' => 'bwlms_repeditbox') );
		
		do_action('bwlms_message_message_form_after_content');
		$newMsg .= ob_get_contents();
		ob_end_clean();

		$newMsg .= "
				</div>
			</div>
		";

		// Send Message 행
		$newMsg .= " 
			<div class='row bwlms-new-msg-title-row'>
				<div class='small-12  medium-12 large-12 newmsg-title-rowC columns'>
			";

        $newMsg .="
			<input type='hidden' name='message_from' value='$user_ID' />
			<input type='hidden' name='parent_id' value='$parent_id' />
			<input type='hidden' name='token' value='$token' />
			<div class='bwlms-sendmsg-btn'>
				<input class='sendmsgdefault' type='submit' name='new_message' value='".__("Send", 'wptobemem')."' />
			</div>
			</form>";

		$newMsg .= "
				</div>
			</div>
		</div><!--End of new message container -->
		";
		
        return apply_filters('bwlms_message_filter_new_message_form', $newMsg);
    }

	function new_message_action(){
		$html = '';
		if(isset($_POST['new_message'])){ 
			$errors = $this->check_message();
			if(count($errors->get_error_messages())>0){
				$html .= bwlms_message_error($errors);
				$html .= $this->new_message();
			}
			else{
				$html .= '<div id="bwlms-message-success">' .__("Message sent successfully .", 'wptobemem'). ' </div>';
			}
		}
		else{
			$html .= $this->new_message();
		}
		return $html;
	}

	function new_message_action_popup(){

		if(isset($_POST['new_message'])){ 
			$errors = $this->check_message();
			if(count($errors->get_error_messages())>0){
				$html = $this->new_message();
			}
			else{
				$html = '<div id="bwlms-message-success">' .__("Message sent successfully .", 'wptobemem'). '</div>';
			}
		}
		else{
			$html = $this->new_message();
		}
		return $html;
	}

	function reply_message_action(){
		$html = "<div class='row message_replyresult_row' id='bwlms-message-success'>
					<div class='large-2 medium-2 small-2 bwlms-dummy-2col'> </div>
					<div class='large-10 medium-10 small-10'>
		";

			$errors = $this->check_message();

			if(count($errors->get_error_messages())>0){
				$html .= bwlms_message_error($errors);
			}
			else{
				$html .= __("Message sent successfully! ", 'wptobemem');
			}

		$html .= "
						</div>
					</div>";
		echo $html;
	}


    function check_message()
    {
      global $wpdb, $user_ID;
	  $errors = new WP_Error();


		//1.발신인
		$message_from = ( isset( $_POST['message_from'] ) ) ? esc_html( $_POST['message_from'] ): '';

		//2. 수신인
		$message_to = ( isset( $_POST['message_to'] ) ) ? esc_html( $_POST['message_to'] ):  '';
		$message_top = ( isset( $_POST['message_top'] ) ) ? esc_html( $_POST['message_top'] ):  '';
		 if (!empty($message_to)) { $preTo = $message_to;  } 
		 else {  $preTo = $message_top ;}
		 $preTo = apply_filters( 'bwlms_message_preto_filter', $preTo );
		$message_totop = bwlms_message_get_userdata( $preTo );

		//3.타이틀
		$message_title = ( isset( $_POST['message_title'] ) ) ? esc_html( $_POST['message_title'] ): '';

		//4. 컨텐츠
		$message_content = ( isset( $_POST['message_content'] ) ) ? esc_textarea( $_POST['message_content'] ): '';

		//5. 부모아이디
		$parent_id = ( isset( $_POST['parent_id'] ) ) ? absint( $_POST['parent_id'] ): 0;

		//6.전송시간
		$message_senddate  = current_time('mysql');

		//7. 토큰
		$message_token = ( isset( $_POST['token'] ) ) ? $_POST['token'] : '';


	  	if (!$message_totop)
		  	$errors->add('invalidTo', __('You must enter a valid recipient!', 'wptobemem'));
        if (!$message_title)
		  $errors->add('invalidSub', __('You must enter subject.', 'wptobemem'));
        if (!$message_content)
		  $errors->add('invalidMgs', __('You must enter some message content!', 'wptobemem'));
        if ($message_from != $user_ID || $message_totop == $user_ID )
          $errors->add('NoPermission', __("You do not have permission to send this message!", 'wptobemem'));
		
      if (bwlms_message_get_user_option( 'allow_messages', 1, $message_totop ) != '1')
        $errors->add('ToDisallow', __("This user does not want to receive messages!", 'wptobemem'));

//	  if (!bwlms_message_verify_nonce($message_token, 'new_message'))
//        $errors->add('InvalidToken', __("Invalid Token. Please try again!", 'wptobemem'));

		
	  if ($parent_id != 0) {
	  $mgsInfo = $wpdb->get_row($wpdb->prepare("SELECT to_user, from_user FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d", $parent_id));

	  if ($mgsInfo->to_user != $user_ID && $mgsInfo->from_user != $user_ID && !current_user_can( 'manage_options' ))
          $errors->add('OthersMgs', __("You do not have permission to send this message!", 'wptobemem'));
		  
		  do_action('bwlms_message_before_send_new_reply', $errors);
		} 
	
	  do_action('bwlms_message_action_message_before_send', $errors);
	  
	  apply_filters('bwlms_message_filter_message_before_send', $message_title, $message_content, $errors);


	  if(count($errors->get_error_codes())==0){
      if ($parent_id == 0){
	  
	  	$wpdb->insert( BWLMS_MESSAGES_TABLE, array( 'from_user' => $message_from, 'to_user' => $message_totop, 'message_title' => $message_title, 'message_contents' => $message_content, 'parent_id' => $parent_id, 'last_sender' => $message_from, 'send_date' => $message_senddate, 'last_date' => $message_senddate ), array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )); 
		
		$message_id = $wpdb->insert_id;
		do_action('bwlms_message_after_send_new_message', $message_id);
      } else {
	  
	  	$wpdb->insert( BWLMS_MESSAGES_TABLE, array( 'from_user' => $message_from, 'to_user' => $message_totop, 'message_title' => $message_title, 'message_contents' => $message_content, 'parent_id' => $parent_id, 'send_date' => $message_senddate ), array( '%d', '%d', '%s', '%s', '%d', '%s' ));
		
		$message_id = $wpdb->insert_id; 
		
		$wpdb->update( BWLMS_MESSAGES_TABLE, array( 'status' => 0, 'last_sender' => $message_from, 'last_date' => $message_senddate, 'to_del' => 0, 'from_del' => 0 ), array( 'id' => $parent_id ), array( '%d', '%d', '%s', '%d', '%d' ), array ( '%d' ));
		
		do_action('bwlms_message_after_send_new_reply', $message_id);
      }
	  //send_email( $message_id, $mgs ) (message-email-class.php)
	  //do_action('bwlms_message_action_message_after_send', $message_id);//, $message);
	  do_action('bwlms_message_action_message_after_send', $message_id, $message_totop, $message_from, $message_title );

	  }

      return $errors;
    }

	function autoembed($string){
		  global $wp_embed;
		  if (is_object($wp_embed))
			return $wp_embed->autoembed($string);
		  else
			return $string;
	}
	
	function getWholeThread( $id, $order = 'ASC' ){
		  global $wpdb;
		  
		  $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d ORDER BY send_date $order", $id, $id));
		  return $results;
	}
	
	// 개별메시지(쓰레드) 상세페이지
	function view_message()
    {
		  global $wpdb, $user_ID;

		  $pID = absint( $_GET['id']);
		  
		  if(isset ($_GET['order'])) {$get_order = sanitize_text_field($_GET['order']);}
		  $order = (isset ( $_GET['order'] ) && strtoupper($get_order) == 'DESC' ) ? 'DESC' : 'ASC';
		  if ( 'ASC' == $order ) $anti_order = 'DESC'; else $anti_order = 'ASC';
		  
		  if ( !$pID )
		  return "<div id='bwlms-message-error'>".__("You do not have permission to view this message!", 'wptobemem')."</div>";
		  
		  $wholeThread = $this->getWholeThread( $pID, $order );
		  
			ob_start();
			  
			  do_action ('bwlms_message_display_in_message_header', $pID, $wholeThread );
			  $threadOut .= ob_get_contents();
			ob_end_clean();
			  

		  foreach ($wholeThread as $post)
		  {

				if ($post->to_user != $user_ID && $post->from_user != $user_ID && !current_user_can( 'manage_options' ))
				{
				  return "<div id='bwlms-message-error'>".__("You do not have permission to view this message!", 'wptobemem')."</div>";
				}

				if ($post->parent_id == 0) 
				{
				  $to = $post->from_user;
				  if ($to == $user_ID) 
					$to = $post->to_user;
				  $message_title = $post->message_title;
				  if (substr_count($message_title, __("Re:", 'wptobemem')) < 1) 
					$re = __("Re:", 'wptobemem');
				  else
					$re = "";
				}

				if ($post->parent_id == 0) //쓰레드의 처음(메인) 메시지
				{

					$threadOut .= "
					<div class='row'>
					<div class='large-12 medium-12 small-12 large-centered medium-centered small-centered columns'>


						<div class='row bwlmsmsgth-p-crow'>
							<div class='row bwlmsmsgth-p-rowA'  >
							  <div class='small-2 columns  bwlmsmsgth-p-rowA2'>
								".get_avatar($post->from_user, 55)."
							  </div>
							  <div class='small-10 columns  bwlmsmsgth-p-rowA10'>
								<div class='bwlmsgth-username'>
									";
									if($post->from_user == $user_ID) { 
										$threadOut .= "Me";
									}
									else { 
										$threadOut .= bwlms_message_get_userdata( $post->from_user, 'display_name', 'id' );
									}
									$threadOut .= "
								</div>
								<div class='bwlmsgth-maintitle'>
									".bwlms_message_output_filter($post->message_title, true)."
								</div>
								<div class='bwlmsgth-content'>
									".bwlms_message_output_filter($post->message_contents)."
								</div>
							  </div>
							</div>
							
							<div class='row bwlmsmsgth-p-rowB' >
							  <div class='small-8 columns bwlmsmsgth-p-rowB8'></div>
							  <div class='small-4 columns bwlmsmsgth-p-rowB4'>
									".bwlms_message_format_date($post->send_date)."
							  </div>
							</div>
						</div>

					</div>
					</div>
					";

					  ob_start();
					  do_action ('bwlms_message_display_after_parent_message', $post->id );
					  $threadOut .= ob_get_contents();
					  ob_end_clean();

					  if ($post->status == 0 && $user_ID != $post->last_sender && ( $user_ID == $post->from_user || $user_ID == $post->to_user )) //Update only if the reader is not last sender
						$wpdb->update( BWLMS_MESSAGES_TABLE, array( 'status' => 1 ), array( 'id' => $post->id ), array( '%d' ), array( '%d' ));
				}

				else
				{
						$threadOut .= "
					<div class='row'>
					<div class='large-12 medium-12 small-12 large-centered medium-centered small-centered columns'>

						<div class='row bwlmsmsgth-r-crow'>
							<div class='row bwlmsmsgth-r-rowA'  >
							  <div class='small-2 columns bwlmsmsgth-r-rowA2'>
								".get_avatar($post->from_user, 55)."
							  </div>
							  <div class='small-10 columns bwlmsmsgth-r-rowA10'>
	  							<div class='bwlmsgth-username'>
									";
									if($post->from_user == $user_ID) { 
										$threadOut .= "Me";
									}
									else { 
										$threadOut .= bwlms_message_get_userdata( $post->from_user, 'display_name', 'id' );
									}
									$threadOut .= "
								</div>
	  							<div class='bwlmsgth-content'>
									".bwlms_message_output_filter($post->message_contents)."
								</div>
							  </div>
							</div>";
							
					  ob_start();
					   do_action ('bwlms_message_display_after_reply_message', $post->id );
					  $threadOut .= ob_get_contents();
					  ob_end_clean();

						$threadOut .= "
							<div class='row bwlmsmsgth-r-rowB' >
							  <div class='small-8 columns bwlmsmsgth-r-rowB8'>  </div>
							  <div class='small-4 columns bwlmsmsgth-r-rowB4'>
									".bwlms_message_format_date($post->send_date)."
							  </div>
							</div>
						</div>
					
					</div><!--Centered11-->
					</div><!--Row-->
						";
				}
		  }//end foreach

		  $reply_args = array (
								'message_to' => bwlms_message_get_userdata( $to, 'user_login', 'id' ),
								'message_top' => bwlms_message_get_userdata( $to, 'display_name', 'id' ),
								'message_title' => $re.$message_title,
								'message_from' => $user_ID,
								'parent_id' => $pID
								);
		  
		  $threadOut .= bwlms_message_reply_form( $reply_args );

      return $threadOut;
    }

	function delete()
    {
      global $wpdb, $user_ID;

      $delID = absint( $_GET['id'] );
	  
	  if (!bwlms_message_verify_nonce($_GET['token'], 'delete_message')){
		return "<div id='bwlms-message-error'>".__("Invalid Token!", 'wptobemem')."</div>";
	  }
	  
	  $info = $wpdb->get_row($wpdb->prepare("SELECT from_user, to_user, to_del, from_del FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d", $delID));

      if ($info->to_user == $user_ID)
      {
        if ($info->from_del == 0){
			$wpdb->update( BWLMS_MESSAGES_TABLE, array( 'to_del' => 1 ), array( 'id' => $delID ), array( '%d' ), array( '%d' ));
        } else {
		$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
	  $id = implode(',',$ids);
	  
	  do_action ('bwlms_message_message_before_delete', $delID, $ids);
	  
          $wpdb->query($wpdb->prepare("DELETE FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
		  $wpdb->query("DELETE FROM ".BWLMS_MESSAGE_META_TABLE." WHERE message_id IN ({$id})");
		  }
      }
      elseif ($info->from_user == $user_ID)
      {
        if ($info->to_del == 0){
			$wpdb->update( BWLMS_MESSAGES_TABLE, array( 'from_del' => 1 ), array( 'id' => $delID ), array( '%d' ), array( '%d' ));
        } else {
		$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
	  $id = implode(',',$ids);
	  
	  do_action ('bwlms_message_message_before_delete', $delID, $ids);
	  
          $wpdb->query($wpdb->prepare("DELETE FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
		  $wpdb->query("DELETE FROM ".BWLMS_MESSAGE_META_TABLE." WHERE message_id IN ({$id})");
		  }
      } else {
	  return "<div id='bwlms-message-error'>".__("No permission!", 'wptobemem')."</div>";
	  }
		
		return "<div id='bwlms-message-success'>".__("Your message was deleted successfully!", 'wptobemem')."</div>";
    }

	function bwlms_message_setting_shortcode_output()
	{
		global $user_ID;
		
		if ($user_ID) {
		  

			$out ="";

			$switch = ( isset($_GET['bwlmsmessageaction'] ) && $_GET['bwlmsmessageaction'] ) ? sanitize_text_field($_GET['bwlmsmessageaction']) : 'settings';
			
			switch ($switch)
			{
			  case 'settings':
				$out .= $this->user_settings();
				break;
			  default: 
				$out .= $this->user_settings();
				break;
			}

		}

		return apply_filters('bwlms_message_main_shortcode_output', $out);
	 }

  } //END CLASS
} //ENDIF
add_shortcode('bwlms-message-new', array(bwlms_message_main_class::init(), 'newmsg_shortcode' )); 
add_shortcode('bwlms-message', array(bwlms_message_main_class::init(), 'main_shortcode_output' )); 
add_shortcode('bwlms-message-settings', array(bwlms_message_main_class::init(), 'bwlms_message_setting_shortcode_output' )); 
?>