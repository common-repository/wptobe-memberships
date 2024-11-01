<?php

function bwlms_message_get_option( $option, $default = '', $section = 'BWLMS_MESSAGE_admin_options' ) {
	
    $options = get_option( $section );
    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }

    return $default;
}

function bwlms_message_get_user_option( $option, $default = '', $userid = '', $section = 'BWLMS_MESSAGE_user_options' ) {

    $options = get_user_option( $section, $userid ); //if $userid = '' current user option will be return

    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }

    return $default;
}

function bwlms_message_page_id() {

	global $wpdb;
	
	if ( false === ($id = get_transient('bwlms_message_page_id'))){
	
		$id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[bwlms-message]%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
		
		if ($id)
		set_transient('bwlms_message_page_id', $id, 60*60*24);
		}
		
     return apply_filters( 'bwlms_message_page_id_filter', $id);
}

function bwlms_message_action_url( $action = '' ) {
      global $wp_rewrite;
      if($wp_rewrite->using_permalinks())
        $delim = '?';
      else
        $delim = '&';
	  
	  return get_permalink(bwlms_message_page_id()).$delim."bwlmsmessageaction=$action";
}

function bwlms_message_query_url( $action, $arg = array() ) {
      
	  $args = array( 'bwlmsmessageaction' => $action );
	  $args = array_merge( $args, $arg );
	  
	  $permalink = apply_filters( 'bwlms_message_page_permalink_filter', get_permalink( bwlms_message_page_id() ), bwlms_message_page_id(), $args );

	  if ( $permalink ) {
		 
		return esc_url( add_query_arg( $args, $permalink ) );
	  }
	  else {
		return esc_url( add_query_arg( $args ) );
	  }
}

if ( !function_exists('bwlms_message_create_nonce') ) :

	function bwlms_message_create_nonce($action = -1) {
   	 $time = time();
    	$nonce = wp_create_nonce($time.$action);
    return $nonce . '-' . $time;
	}	

endif;

if ( !function_exists('bwlms_message_verify_nonce') ) :

	function bwlms_message_verify_nonce( $_nonce, $action = -1) {

    $parts = explode( '-', $_nonce );
    $nonce = $parts[0]; 
   	//isset( $parts[1] ) ?  $generated = $parts[1] : $generated='' ; 
	$generated = $parts[1] ; 
    
	$nonce_life = 60*60; 
    $expire = (int) $generated + $nonce_life;
    $time = time(); 
		
	if ( empty( $nonce ) || empty( $generated ) )
		return false;

    
    if( ! wp_verify_nonce( $nonce, $generated.$action ) || $time > $expire )
        return false;

    $used_nonces = get_option('_bwlms_message_used_nonces');

    if( isset( $used_nonces[$nonce] ) )
        return false;

    foreach ($used_nonces as $nonces => $timestamp){
        if( $timestamp < $time ){
			unset( $used_nonces[$nonces] );
		}
    }

    $used_nonces[$nonce] = $expire;
    asort( $used_nonces );
    update_option( '_bwlms_message_used_nonces',$used_nonces );
	return true;
}
endif;

function bwlms_message_error($wp_error){
	if(!is_wp_error($wp_error)){
		return '';
	}
	if(count($wp_error->get_error_messages())==0){
		return '';
	}
	$errors = $wp_error->get_error_messages();
	if (is_admin())
	$html = '<div id="message" class="error">';
	else
	$html = '<div id="bwlms-message-wp-error">';
	foreach($errors as $error){
		$html .=  __('Error', 'wptobemem') . ':'.esc_html($error).'<br />';
	}
	$html .= '</div>';
	return $html;
}

function bwlms_message_get_new_message_number() {
      global $wpdb, $user_ID;

      $get_pms = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE (to_user = %d AND parent_id = 0 AND to_del = 0 AND status = 0 AND last_sender <> %d) OR (from_user = %d AND parent_id = 0 AND from_del = 0 AND status = 0 AND last_sender <> %d)", $user_ID, $user_ID, $user_ID, $user_ID));
      return $wpdb->num_rows;
}

function bwlms_message_get_new_message_button(){
	if (bwlms_message_get_new_message_number()){
		$newmgs = bwlms_message_get_new_message_number();
		} else {
		$newmgs = '';
	}
		
	return $newmgs;
}

function bwlms_message_get_userdata($data, $need = 'ID', $type = 'login' ){
		if (!$data)
		return '';
		
		$type = strtolower($type);
		if ( !in_array($type, array ('id', 'slug', 'email', 'login' )))
		return '';
		
		$user = get_user_by( $type , $data);
		if ( $user && in_array($need, array('ID', 'user_login', 'display_name', 'user_email')))
		return $user->$need;
		else
		return 'Unknown User';
}


function bwlms_message_message_box($action = '', $title = '', $total_message = false, $messages = false )
{
	global $user_ID;
	global $show_flag;

	$current_page = sanitize_post( $GLOBALS['wp_the_query']->get_queried_object() );
	// Get the page slug
	$slug = $current_page->post_name;


	  if ( !$action )
	  $action = ( isset( $_GET['bwlmsmessageaction']) && $_GET['bwlmsmessageaction'] )? sanitize_text_field($_GET['bwlmsmessageaction']): 'messagebox';

		// 송수신 총메시지 개수
		if( $total_message === false )
		$total_message = bwlms_message_get_user_total_message( $action );
		//echo "총메시지개수:".$total_message;

		// 받은 총 메시지
		$total_received_message=bwlms_message_get_user_total_received_message($action);
		//echo "총수신:".$total_received_message;
		//보낸 총 메시지
		$total_sent_message = bwlms_message_get_user_total_sent_message($action);
		//echo "총발신:".$total_sent_message;

		$show_flag = "R";

	if(isset($_GET['show_flag'])){
		$show_flag = sanitize_text_field($_GET['show_flag']);
	}

	  if(($messages === false) && ($show_flag == 'R') )
	   $messages = bwlms_message_get_user_received_messages( $action );	  
	  else if (($messages === false) && ($show_flag == 'S'))
		$messages = bwlms_message_get_user_sent_messages( $action );	 
	  else if ($messages === false)
	   $messages = bwlms_message_get_user_received_messages( $action );	  

	  $Newmsg_counter = bwlms_message_get_new_message_number();

	  $msgsOut ="

	<div class='row'>
	<div class='col-xs-12 wptobemem-inbox-wrapper'>

		<div class='row message_titlerow'><!-- 1 3 1 4 3 -->
			<div class='large-1 medium-1 small-1 columns bwlmsmsg_title_cols'>
				
				<div class='dropdown'>
				  <button class='btn btn-secondary dropdown-toggle npbutton' type='button' id='dropdownMenuButton' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
					 &Or;
				  </button>
				  <div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>
					<a class='dropdown-item wptobemem-inbox-btn-alink' href='./?show_flag=R'>Received</a>
					<a class='dropdown-item wptobemem-inbox-btn-alink' href='./?show_flag=S'>Sent</a>
				  </div>
				</div>

			</div><!-- large-1 -->
			";

		$bwlmsmessage_inboxtitle = __('Inbox','wptobemem');
		$msgsOut .="
			<div class='large-3 medium-3  small-3  columns inbox_counter_titlecol' id='msg_ajax_frame'>
				<div class='inbox_counter_title'>";

		if($show_flag == 'S') {//Total number of sent message
		  $msgsOut .="Sent(<span class='new_msg_counter'>$total_sent_message</span>)</div>";
		}
		else { // Total number of  new message
			$msgsOut .= $bwlmsmessage_inboxtitle . "( <span class='new_msg_counter'>$Newmsg_counter</span> )</div>";
		}
		
		$msgsOut .="
			</div><!-- large-3 -->
			<div class='large-1 medium-1  small-1 columns message_title_gapcol'>&nbsp</div><!--large-1-->
		";

	  // A. 보낸 메시지 출력 
	  if($total_sent_message && ($show_flag == 'S')) {
				ob_start();
				do_action('bwlms_message_display_before_messagebox', $action);
				$msgsOut .= ob_get_clean();

			$numPgs = $total_sent_message / bwlms_message_get_option('messages_page',50);
			$page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;
			$current_page = $page+1;
			$total_page = intval($numPgs)+1;

		   if ($numPgs > 1) // [메시지가 1페이지 이상일 때 페이지네이션 작업]
			{
			  $msgsOut .= "<div class='large-4 medium-4  small-4  columns inbox_counter_titlepagecol clearfix'>";
				$msgsOut .= "<div class='right'>$current_page of $total_page Pages</div>";
			  $msgsOut .= "</div>";

			  $msgsOut .= "<div class='large-3 medium-3  small-3  columns inbox_counter_titlepagebtn clearfix'>";

			  $msgsOut .= "
				<ul class=\"pagination right\" role=\"menubar\" aria-label=\"Pagination\">
			  ";

				if($page!=0) // 첫페이지가 아니면 <--(Back) 버튼을 활성화 시킨다
					$msgsOut .= "<li class=\"arrow\">
								 <a class='npbutton back' href='".esc_url( bwlms_message_action_url($action) )."&bwlmsmessagepage=".($page-1)."&show_flag=".$show_flag."'>
								 "."<b>&#10229;</b></a> </li>";
				else //첫페이지면 <-- (Back) 버튼을 비활성화 시킨다
					$msgsOut .= "<li class=\"arrow unavailable\" aria-disabled=\"true\">
								 <a class='npbutton back'><b>&#10229;</b></a></li>";

				if($page>=(int)$numPgs) //마지막페이지미면 -->(Next)버튼을 비활성화
					$msgsOut .= "<li class=\"arrow unavailable\" aria-disabled=\"true\">
								 <a class='npbutton next'><b>&#x027F6;</b></a></li>";
				else // 마지막페이지가 아니면 -->(Next)버튼을 활성화
					$msgsOut .= "<li class=\"arrow\">
								 <a class='npbutton next' href='".esc_url( bwlms_message_action_url($action) )."&bwlmsmessagepage=".($page+1)."&show_flag=".$show_flag."'>"."<b>&#x027F6;</b></a> </li>";

				  $msgsOut .= "</ul>";

				$msgsOut .= "
						</div><!--large-3-->
					</div><!--row message_titlerow-->
				";
			}//End of Pagination [if]
			else {
			//
			  $msgsOut .= "<div class='large-4 medium-4  small-4  columns inbox_counter_titlepagecol clearfix'></div>";
			  $msgsOut .= "<div class='large-3 medium-3  small-3  columns inbox_counter_titlepagebtn clearfix'>
						</div><!--large-3-->
					</div><!--[No msg]row message_titlerow-->";
			//
			}
		

			foreach ($messages as $msg)
			{
				$msgsOut .= "

					<div class='row message_row'>
						";

				$msgsOut .= "<div class='large-2 medium-2 columns show-for-medium-up message_row_idcol'>
						<a href='".bwlms_message_action_url()."between&with=".bwlms_message_get_userdata( $msg->to_user, 'user_login', 'id' )."'>
						".bwlms_message_get_userdata( $msg->to_user, 'display_name', 'id' ). 
						"</a> </div>";
		

				$msgsOut .= "<div class='large-8 medium-8 small-12 columns message_row_msgcol' >
					  <a class='read_msg_title' href='".bwlms_message_action_url()."viewmessage&id=".$msg->id."'>"
					  .bwlms_message_output_filter($msg->message_title,true)."
					  </a>
					  </div>";

				$msgsOut .= "<div class='large-2 medium-2 columns show-for-medium-up message_row_datecol'>
								<div class='msg_received_date'>"
								.bwlms_message_format_date($msg->last_date).
								"</div>
							   </div>";

				$msgsOut .=  "
					</div><!-- message_row -->
				";
			}//end foreach

			return apply_filters('bwlms_message_messagebox', $msgsOut, $action);

	  }//end of (A) total_sent_message
		
	  // B. 받은메시지 출력
	  else if ($total_received_message && ($show_flag == 'R')) {
			ob_start();
	  		do_action('bwlms_message_display_before_messagebox', $action);
	  		$msgsOut .= ob_get_clean();

		//================================================================================
		// 총 메시지 개수, 페이지당 메시지 개수를 찾아서 페이지 수를 계산하고 출력해준다.
		//================================================================================
        $numPgs = $total_received_message / bwlms_message_get_option('messages_page',50);

		$page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;

		$current_page = $page+1;
		$total_page = intval($numPgs)+1;

//       if ($numPgs > 1) // [메시지가 1페이지 이상일 때 페이지네이션 작업]
//        {
		  $msgsOut .= "<div class='large-4 medium-4  small-4 columns inbox_counter_titlepagecol clearfix'>";
		  $msgsOut .= "<div class='right'>$current_page of $total_page Pages</div>";
		  $msgsOut .= "</div>";

		  $msgsOut .= "<div class='large-3 medium-3  small-3 columns inbox_counter_titlepagebtn clearfix'>";

		  $msgsOut .= "
			<ul class=\"pagination right\" role=\"menubar\" aria-label=\"Pagination\">
		  ";

			if($page!=0) // 첫페이지가 아니면 <--(Back) 버튼을 활성화 시킨다
				$msgsOut .= "<li class=\"arrow\">
							 <a class='npbutton back' href='".esc_url( bwlms_message_action_url($action) )."&bwlmsmessagepage=".($page-1)."&show_flag=".$show_flag."'>
							 "."<b>&#10229;</b></a> </li>";
			else //첫페이지면 <-- (Back) 버튼을 비활성화 시킨다
				$msgsOut .= "<li class=\"arrow unavailable\" aria-disabled=\"true\">
							 <a class='npbutton back'><b>&#10229;</b></a></li>";

			if($page>=(int)$numPgs) //마지막페이지미면 -->(Next)버튼을 비활성화
				$msgsOut .= "<li class=\"arrow unavailable\" aria-disabled=\"true\">
							 <a class='npbutton next'><b>&#x027F6;</b></a></li>";
			else // 마지막페이지가 아니면 -->(Next)버튼을 활성화
				$msgsOut .= "<li class=\"arrow\">
							 <a class='npbutton next' href='".esc_url( bwlms_message_action_url($action) )."&bwlmsmessagepage=".($page+1)."&show_flag=".$show_flag."'>"."<b>&#x027F6;</b></a> </li>";

			  $msgsOut .= "</ul><!--Pagination-->";

			$msgsOut .= "
					</div><!--large-3-->
				</div><!-- row message_titlerow -->	
			";

//          }//End of Pagination [if]


		//================================================================================
		// Each message
		//================================================================================
        foreach ($messages as $msg)
        {

          if ($msg->status == 0 && $msg->last_sender != $user_ID)
            $status = __("Unread", 'wptobemem');
          else
            $status = __("Read", 'wptobemem');
			
			//bwlms_message_filter_status_display : 유저가 manage_options 권한이 있을 경우
			$status = apply_filters ('bwlms_message_filter_status_display', $status, $msg, $action );

			$msgsOut .= "
						<div class='row message_row'>";

			//===========================
			// 발신자 아이디 출력 
			//===========================
			$msgsOut .= "<div class='large-2 medium-2 columns show-for-medium-up message_row_idcol'>
					<a href='".bwlms_message_action_url()."between&with=".bwlms_message_get_userdata( $msg->from_user, 'user_login', 'id' )."'>"
					.bwlms_message_get_userdata( $msg->from_user, 'display_name', 'id' ). "</a>
					</div>";

			/*==========================*/
			/* 메시지 컨텐츠 출력 */
			/*==========================*/
			if(strcmp($status,'Read')) { // Unread
			  $msgsOut .= "<div class='large-8 medium-8 small-12 columns message_row_msgcol' >
				  <a class='unread_msg_title' href='".bwlms_message_action_url()."viewmessage&id=".$msg->id."'>"
				  .bwlms_message_output_filter($msg->message_title,true)."
				  </a> 
				  </div>";
			}
			else { // Read
			  $msgsOut .= "<div class='large-8 medium-8 small-12 columns message_row_msgcol' >
				  <a class='read_msg_title' href='".bwlms_message_action_url()."viewmessage&id=".$msg->id."'>"
				  .bwlms_message_output_filter($msg->message_title,true)."
				  </a>
				  </div>";
			}
		  
			$msgsOut .= "<div class='large-2 medium-2 columns show-for-medium-up message_row_datecol'>
							<div class='msg_received_date'>"
							//.bwlms_message_get_userdata( $msg->last_sender, 'display_name', 'id' ). 
							.bwlms_message_format_date($msg->last_date).
							"</div>
						   </div>";

			$msgsOut .=  "</div><!--[Inbox] message_row -->
			"; 
		}//end foreach

        return apply_filters('bwlms_message_messagebox', $msgsOut, $action);

      }//end: Total received message 



	  else //메시지가 없는 경우
	  {

			  $msgsOut .= "<div class='large-4 medium-4  small-4  columns inbox_counter_titlepagecol clearfix'></div>";
			  $msgsOut .= "<div class='large-3 medium-3  small-3  columns inbox_counter_titlepagebtn clearfix'>
						</div><!--large-3-->
					</div><!--[No msg]row message_titlerow-->";
				
				$msgsOut .= "
						<div class='row message_row'>";
				$msgsOut .= "<div class='large-2 medium-2 columns show-for-medium-up message_row_idcol'>
							<a>". __('Administrator', 'wptobemem') ."</a>
							</div>";
				$msgsOut .= "<div class='large-8 medium-8 small-12 columns message_row_msgcol' >
							<a class='unread_msg_title'>". __('You do not have any message yet.', 'wptobemem') ."</a>
							</div>";
				$msgsOut .= "<div class='large-2 medium-2 columns show-for-medium-up message_row_datecol'>
								<div class='msg_received_date'>
									". __('Jan 01, 00:00 AM', 'wptobemem') ."
								</div>
							</div>";
		  $msgsOut .= "</div><!--여기-->
		  ";

		  return apply_filters('bwlms_message_messagebox', $msgsOut, $action);
      }
}

function bwlms_message_get_user_total_message( $action = 'messagebox', $userID = 0 )
{
      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;
	  
	  if ( has_filter("bwlms_message_user_total_message_count_{$action}") ){
	  
	  $count = apply_filters( "bwlms_message_user_total_message_count_{$action}" , 0, $action );
	  
	  } elseif ( 'inbox' == $action ){
	  
      $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE (to_user = %d AND parent_id = 0 AND to_del = 0) AND (status = 0 OR status = 1)", $userID));
	  $count = $wpdb->num_rows;
	  
	  } elseif ( 'outbox' == $action ){
	  
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE (from_user = %d AND parent_id = 0 AND from_del = 0) AND (status = 0 OR status = 1)", $userID));
	  $count = $wpdb->num_rows;
	  
	  } else {
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." WHERE ((to_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1)", $userID, $userID));
	  $count = $wpdb->num_rows;
	  }
	  
      return $count;
}

function bwlms_message_get_user_total_received_message( $action = 'messagebox', $userID = 0 )
{
      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;

	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." 
	  WHERE (
		(to_user = %d AND parent_id = 0 AND to_del = 0) ) AND 
		(status = 0 OR status = 1)", $userID));

	  $count = $wpdb->num_rows;

      return $count;
}

function bwlms_message_get_user_total_sent_message( $action = 'messagebox', $userID = 0 )
{
      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;

	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".BWLMS_MESSAGES_TABLE." 
	  WHERE (
		(from_user = %d AND parent_id = 0 AND to_del = 0) ) AND 
		(status = 0 OR status = 1)", $userID));

	  $count = $wpdb->num_rows;

      return $count;
}


function bwlms_message_get_user_messages( $action = 'messagebox', $userID = 0 ){

      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;
	  
	  $page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;
	  
      $start = $page * bwlms_message_get_option('messages_page', 50);
      $end = bwlms_message_get_option('messages_page', 50);
	  
	  if ( has_filter("bwlms_message_user_messages_{$action}") ){
	  
		  $get_messages = apply_filters( "bwlms_message_user_messages_{$action}" , array(), $action );
	  
	  } 
	  elseif ( 'inbox' == $action ){

	      $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGES_TABLE." WHERE (to_user = %d AND parent_id = 0 AND to_del = 0) AND (status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", $userID, $start, $end));
	  
	  } 
	  elseif ( 'outbox' == $action ){
	  
		  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGES_TABLE." WHERE (from_user = %d AND parent_id = 0 AND from_del = 0) AND (status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", $userID, $start, $end));
	  
	  } 
	  else {

		  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGES_TABLE." WHERE ((to_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", $userID, $userID, $start, $end));
	  }
	  
      return $get_messages;
}

function bwlms_message_get_user_received_messages( $action = 'messagebox', $userID = 0 ){

      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;
	  
	  $page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;
	  
      $start = $page * bwlms_message_get_option('messages_page', 50);
      $end = bwlms_message_get_option('messages_page', 50);
	  

	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGES_TABLE." 
	  WHERE 
		(to_user = %d AND parent_id = 0 AND to_del = 0)  AND 
		(status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", 
		$userID, $start, $end));

      return $get_messages;
}

function bwlms_message_get_user_sent_messages( $action = 'messagebox', $userID = 0 ){

      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;
	  
	  $page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;
	  
      $start = $page * bwlms_message_get_option('messages_page', 50);
      $end = bwlms_message_get_option('messages_page', 50);
	  


	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGES_TABLE." 
	  WHERE 
		(from_user = %d AND parent_id = 0 AND to_del = 0)  AND 
		(status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", 
		$userID, $start, $end));

      return $get_messages;
}

function bwlms_message_get_message_meta($message_id, $name = ''){
	global $wpdb;
		if (is_array($name)){
			$string_name = implode (',',$name);
					$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGE_META_TABLE." WHERE message_id = %d AND field_name IN (%s)",								 						$message_id, $string_name));
						} elseif ($name) {
							$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGE_META_TABLE." WHERE message_id = %d AND field_name = %s",	 							$message_id, $name));
							} else {
						$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGE_META_TABLE." WHERE message_id = %d",$message_id));
					}
			
				if ($results){
			return $results;
		} else {
	return array();}
}

function bwlms_message_format_date($date){

		$now = current_time('mysql');
      return date('M d, h:i a', strtotime($date));
}
	
	function bwlms_message_output_filter($string, $title = false)
    {
		$string = stripslashes($string);
		
	  if ($title) {
	  $html = apply_filters('bwlms_message_filter_display_title', $string);
	  } else {
	  $html = apply_filters('bwlms_message_filter_display_message', $string);
	  }
	  
      return $html;
    }

	
	function bwlms_message_reply_form($args = '') {
		global $user_ID;
		$defaults = array (
							'message_from' => 	$user_ID,
							'message_to' =>		'',
							'message_top' =>	'',
							'message_title' =>	'',
							'parent_id' => 		0,
							'token' => 			bwlms_message_create_nonce('new_message')
							);
							
		$args = wp_parse_args($args, $defaults);
		
		$reply_form = "
		<div class='row'>
		<div class='col-xs-12'>

			<div class='bwlmsmsgth-editor-container-row'>
				<div class='row'  >";

		$reply_form .= "
		  <form action='".bwlms_message_query_url('checkmessage')."' method='post' enctype='multipart/form-data'>";
		  
		  ob_start();
			do_action('bwlms_message_reply_form_before_content');

			// 워드프레스 에디터 세팅 ...
			$reply_settings = array( 
				'media_buttons' => false,
				'teeny' => false, 
				'media_buttons' => false,
				'editor_class' => 'bwlms_repeditbox',
				'tinymce' => false,
				'quicktags' => false
			);
			
			wp_editor( '', 'message_content',$reply_settings  );

			do_action('bwlms_message_reply_form_after_content');

			$reply_form .= ob_get_contents();
			ob_end_clean();

			// 메시지 응답 텍스트 박스: bwlms-message-attachment-class.php 파일 attachment_fields() 에서 이어짐
			$reply_form .="

			</div>
			";

		$reply_form .= "
			<div class='row bwlmsmsgth-send-container-row'>
				";

				$reply_form .="
				  <input type='hidden' name='message_to' value='".$args['message_to']."' />
				  <input type='hidden' name='message_top' value='".$args['message_top']."' />
				  <input type='hidden' name='message_title' value='".$args['message_title']."' />
				  <input type='hidden' name='message_from' value='".$args['message_from']."' />
				  <input type='hidden' name='parent_id' value='".$args['parent_id']."' />
				  <input type='hidden' name='token' value='".$args['token']."' />
				  <div class='bwlms-sendmsg-btn'>
					  <input class='sendmsgdefault' type='submit' name='new_message' value='Send' />
				  </div>
				  </form>

			</div>
		</div>

		</div><!-- Centered -->
		</div><!-- Row -->
			";
		
		return apply_filters('bwlms_message_reply_form', $reply_form );
	}
	


add_action('template_redirect', 'bwlms_message_download_file');

function bwlms_message_download_file()
		{
		if ( !isset($_GET['bwlmsmessageaction']) || sanitize_text_field($_GET['bwlmsmessageaction']) != 'download')
		return;
		
			global $wpdb, $user_ID;
	$id = absint($_GET['id']);

	if ( !bwlms_message_verify_nonce($_GET['token'], 'download') )
	wp_die(__('Invalid token', 'wptobemem'));

	$msgsMeta = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".BWLMS_MESSAGE_META_TABLE." WHERE meta_id = %d", $id));
	if (!$msgsMeta)
	wp_die(__('No attachment found', 'wptobemem'));

	$message_id = $msgsMeta->message_id;

	$unserialized_file = maybe_unserialize( $msgsMeta->field_value );
		  
	if ( $msgsMeta->field_name != 'attachment' || !$unserialized_file['type'] || !$unserialized_file['url'] || !$unserialized_file['file'] )
	wp_die(__('Invalid Attachment', 'wptobemem'));

		$attachment_type = $unserialized_file['type'];
		$attachment_url = $unserialized_file['url'];
		$attachment_path = $unserialized_file['file'];
		$attachment_name = basename($attachment_url);

	$msgsInfo = $wpdb->get_row($wpdb->prepare("SELECT from_user, to_user, status FROM ".BWLMS_MESSAGES_TABLE." WHERE id = %d", $message_id));

	if (!$msgsInfo)
	wp_die(__('Message already deleted', 'wptobemem'));

	if ( $msgsInfo->from_user != $user_ID && $msgsInfo->to_user != $user_ID && $msgsInfo->status != 2 && !current_user_can('manage_options') )
	wp_die(__('No permission', 'wptobemem'));

	if(!file_exists($attachment_path)){
	$wpdb->query($wpdb->prepare("DELETE FROM ".BWLMS_MESSAGE_META_TABLE." WHERE meta_id = %d", $id));
	wp_die(__('Attachment already deleted', 'wptobemem'));
	}
		
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
		header("Content-Type: $attachment_type", true, 200);
		header("Content-Disposition: attachment; filename=\"$attachment_name\"");
		header("Content-Length: " . filesize($attachment_path));
		nocache_headers();
		
		//clean all levels of output buffering
		while (ob_get_level()) {
    		ob_end_clean();
		}
		
		readfile($attachment_path);
		
			exit;
		}

	function bwlms_theme_message_box($action = '', $title = '', $total_message = false, $messages = false )
	{
		global $user_ID;
			
			
			if ( !$action )
				$action = ( isset( $_GET['bwlmsmessageaction']) && $_GET['bwlmsmessageaction'] )? sanitize_text_field($_GET['bwlmsmessageaction']): 'messagebox';
		
			if ( !$title )
				$title = __('Your Messages', 'wptobemem');
			$title = apply_filters('bwlms_message_message_headline', $title, $action );
			
			if( false === $total_message )
				$total_message = bwlms_message_get_user_total_message( $action );
		  
			if( false === $messages )
				$messages = bwlms_message_get_user_messages( $action );
		  
			$msgsOut = '';
			if ($total_message)
			{
				do_action('bwlms_message_display_before_messagebox', $action);
				//$msgsOut .= "<p><strong>$title: ($total_message)</strong></p>";
			
				$numPgs = $total_message / bwlms_message_get_option('messages_page',50);
				$page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;
			
				if ($numPgs > 1)
				{
					?>
					<p><strong><?php echo __("Page", 'wptobemem')?>: </strong> 
					<?php
					for ($i = 0; $i < $numPgs; $i++)
						if ( $page != $i)
						{
					?>
						<a href='<?php echo esc_url( bwlms_theme_message_action_url($action) )?>&bwlmsmessagepage=<?php echo $i?>'><?php echo ($i+1)?></a>
					<?php
						} else {
					?>
							[<b><?php echo ($i+1)?></b>]
					<?php
						}
					?>
						</p>
					<?php
				}
			?>
				<table>
			<?php
				$a = 0;
				foreach ($messages as $msg)
				{
					$status ="";
					if ($msg->status == 0 && $msg->last_sender != $user_ID)
					{
						apply_filters ('bwlms_message_filter_status_display', $status, $msg, $action );
					?>
						<tr class='bwlms-message-trodd<?php echo $a?>'>
					<?php
						if ($msg->from_user != $user_ID)
						{
					?>
							<td><a href='<?php echo bwlms_theme_message_action_url()?>between&with=<?php echo bwlms_message_get_userdata( $msg->from_user, 'user_login', 'id' )?>'><b> <?php echo bwlms_message_get_userdata( $msg->from_user, 'display_name', 'id' )?></b></a></td> 
					<?php
						}
						else
						{
					?>
							<td><b><?php echo bwlms_message_get_userdata( $msg->from_user, 'display_name', 'id' )?></b></td> 
					<?php
						}

					?>
							<td><a href='<?php echo bwlms_theme_message_action_url()."viewmessage&id=".$msg->id?>'><b><?php echo bwlms_message_output_filter($msg->message_title,true)?></b></a><br/><small><b><?php echo $status?></b></small></td>
							<td><b><?php echo bwlms_message_format_date($msg->last_date)?></b><br/><small><b><?php echo $msg->last_date?></b></small></td><br/><small><?php echo bwlms_message_format_date($msg->last_date)?></small></td>
						</tr>
					<?php
					}
					else
					{
						$status = apply_filters ('bwlms_message_filter_status_display', $status, $msg, $action );
					?>
							<tr class='bwlms-message-trodd<?php echo $a?>'>
					<?php
						if ($msg->from_user != $user_ID){
					?>
								<td><a href='<?php echo bwlms_theme_message_action_url()?>between&with=<?php echo bwlms_message_get_userdata( $msg->from_user, 'user_login', 'id' )?>'><?php echo bwlms_message_get_userdata( $msg->from_user, 'display_name', 'id' )?></a></td>
					<?php
						}
						else 
						{
					?>
								<td><?php echo bwlms_message_get_userdata( $msg->from_user, 'display_name', 'id' )?></td>
					<?php
						}
					?>
								<td><a href='<?php echo bwlms_theme_message_action_url()."viewmessage&id=".$msg->id?>'><?php echo bwlms_message_output_filter($msg->message_title,true)?></a><br/><small><?php echo $status?></small></td>
								<td><?php echo bwlms_message_format_date($msg->last_date)?><br/><small><?php echo $msg->last_date?></small></td>
							</tr>
					<?php
					}
				
					if ($a) $a = 0; else $a = 1;
				}
			?>
				</table>
			<?php
			

				apply_filters('bwlms_message_messagebox', $msgsOut, $action);
			}
			else
			{
				?>
					<div id='bwlms-message-error'><?php echo apply_filters('bwlms_message_filter_messagebox_empty', sprintf(__("%s empty", 'wptobemem'), $title ), $action)?></div>
				<?php
			}
	
	}

	function bwlms_theme_message_action_url( $action = '' ) {
		global $wp_rewrite;
		if($wp_rewrite->using_permalinks())
			$delim = '?';
		else
			$delim = '&';
		return get_permalink().$delim."bwlmsmessageaction=$action";
	}

	function bwlms_theme_message_query_url( $action, $arg = array() ) {
	 
		$args = array( 'bwlmsmessageaction' => $action );
		$args = array_merge( $args, $arg );
		
		$permalink = apply_filters( 'bwlms_message_page_permalink_filter', get_permalink(), bwlms_message_page_id(), $args );

	
	  
		if ( $permalink )
		return esc_url( add_query_arg( $args, $permalink ) );
		else
		return esc_url( add_query_arg( $args ) );
	}


add_action('wp_ajax_bwlms_message_message_box_ajax', 'bwlms_message_message_box_ajax');
function bwlms_message_message_box_ajax($action = '', $title = '', $total_message = false, $messages = false )
{
	global $user_ID;


	  if ( !$action )
	  $action = ( isset( $_GET['bwlmsmessageaction']) && $_GET['bwlmsmessageaction'] )? sanitize_text_field($_GET['bwlmsmessageaction']): 'messagebox';

	  if( $total_message === false )
	  $total_message = bwlms_message_get_user_total_message( $action );

	  if($messages === false )
	  $messages = bwlms_message_get_user_messages( $action );

	  $Newmsg_counter = bwlms_message_get_new_message_number();

		$msgsOut = '';
	  if ($total_message)
      {
			  	ob_start();
	  			do_action('bwlms_message_display_before_messagebox', $action);
	  			$msgsOut .= ob_get_clean();

			if($total_message==0)	$msgsOut .= "You have no message.";
			//else $msgsOut .= "$title ($total_message)";
		
        $numPgs = $total_message / bwlms_message_get_option('messages_page',50);
		$page = ( isset ($_GET['bwlmsmessagepage']) && $_GET['bwlmsmessagepage']) ? absint($_GET['bwlmsmessagepage']) : 0;
		
		$current_page = $page+1;
		$total_page = intval($numPgs)+1;

       if ($numPgs > 1) 
        {

			if($page!=0)
				$msgsOut .= "<li class=\"arrow\">
							 <a class='npbutton back' href='".esc_url( bwlms_message_action_url($action) )."&bwlmsmessagepage=".($page-1)."'>
							 "."<b>&#10229;</b></a> </li>";
			else
				$msgsOut .= "<li class=\"arrow unavailable\" aria-disabled=\"true\">
							 <a class='npbutton back'><b>&#10229;</b></a></li>";

				$msgsOut_tmpa ="";
				$msgsOut_tmpb ="";

			  for ($i = 0; $i < $numPgs; $i++){
					
				if ( $page != $i){
				  $msgsOut_tmpa = "<a href='".esc_url( bwlms_message_action_url($action) )."&bwlmsmessagepage=".$i."'>".($i+1)."</a> ";
					//$msgsOut .= "<li class=\"next\"><a href=\"\">$msgsOut_tmpa</a></li>";
				}
				else {
				  $msgsOut_tmpb = "<b>[".($i+1)."]</b>";
				  //$msgsOut .= "<li class=\"current\"><a href=\"\">$msgsOut_tmpb</a></li>";
				}
			  }

			  if($page>=(int)$numPgs)

				$msgsOut .= "<li class=\"arrow unavailable\" aria-disabled=\"true\">
							 <a class='npbutton next'><b>&#x027F6;</b></a></li>";
			  else
				$msgsOut .= "<li class=\"arrow\">
							 <a class='npbutton next' href='".esc_url( bwlms_message_action_url($action) )."&bwlmsmessagepage=".($page+1)."'>"."<b>&#x027F6;</b></a> </li>";

			  $msgsOut .= "</ul>";

			$msgsOut .= "
					</div>
				</div>	
			";

          }//End of Pagination

        foreach ($messages as $msg)
        {

          if ($msg->status == 0 && $msg->last_sender != $user_ID)
            $status = __("Unread", 'wptobemem');
          else
            $status = __("Read", 'wptobemem');
			
			//bwlms_message_filter_status_display : 유저가 manage_options 권한이 있을 경우
			$status = apply_filters ('bwlms_message_filter_status_display', $status, $msg, $action );


	if($msg->to_user == $user_ID) { // 받은메시지 

			/*==================*/
			/* 수신자/발신자 아이디 출력 */
			/*==================*/
			if ($msg->from_user != $user_ID){ // (받은메시지)일때 발신자의 아이디를 찍는다
				  
				$msgsOut .= "<div class='large-2 medium-2 show-for-medium-up message_row_idcol'>
								<a href='".bwlms_message_action_url()."between&with=".bwlms_message_get_userdata( $msg->from_user, 'user_login', 'id' )."'>"
								.bwlms_message_get_userdata( $msg->from_user, 'display_name', 'id' ). "</a>
							</div>";
			}

			if ( $msg->to_user != $user_ID ){ // (보낸메시지)일때 수신자의 아이디를 찍는다

				$msgsOut .= "<div class='large-2 medium-2 show-for-medium-up message_row_idcol'>
					<a href='".bwlms_message_action_url()."between&with=".bwlms_message_get_userdata( $msg->to_user, 'user_login', 'id' )."'>
					".bwlms_message_get_userdata( $msg->to_user, 'display_name', 'id' ). 
					"</a> </div>";
			}
	
			/*==============*/
			/* 메시지 컨텐츠 출력 */
			/*==============*/
			if(strcmp($status,'Read')) { // 안읽은 메시지...
			  $msgsOut .= "<div class='large-8 medium-8 small-12 message_row_msgcol' >
				  <a class='unread_msg_title' href='".bwlms_message_action_url()."viewmessage&id=".$msg->id."'>"
				  .bwlms_message_output_filter($msg->message_title,true)."
				  </a> 
				  </div>";
			}
			else { // 읽은 메시지
			  $msgsOut .= "<div class='large-8 medium-8 small-12 message_row_msgcol' >
				  <a class='read_msg_title' href='".bwlms_message_action_url()."viewmessage&id=".$msg->id."'>"
				  .bwlms_message_output_filter($msg->message_title,true)."
				  </a>
				  </div>";
			}
		  
			$msgsOut .= "<div class='large-2 medium-2 show-for-medium-up message_row_datecol'>
							<div class='msg_received_date'>"
							//.bwlms_message_get_userdata( $msg->last_sender, 'display_name', 'id' ). 
							.bwlms_message_format_date($msg->last_date).
							"</div>
						   </div>";

		}

	}

		// 메시지 출력부분 끝
		 return apply_filters('bwlms_message_received', $msgsOut, $action);

        echo $msgsOut;
		exit;

      }//end: Total message
      
	  else
	  {
        return "<div id='bwlms-message-error'>".apply_filters('bwlms_message_filter_messagebox_empty', sprintf(__("%s empty", 'wptobemem'), $title ), $action)."</div>";
      }
}