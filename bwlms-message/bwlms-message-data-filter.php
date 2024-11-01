<?php

function bwlms_message_backticker_encode($text) {
	$text = $text[1];
    //$text = stripslashes($text); //already done
    $text = str_replace('&amp;lt;', '&lt;', $text);
    $text = str_replace('&amp;gt;', '&gt;', $text);
	$text = htmlspecialchars($text, ENT_QUOTES);
	$text = preg_replace("|\n+|", "\n", $text);
	$text = nl2br($text);
    $text = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $text);
	$text = preg_replace("/^ /", '&nbsp;', $text);
    $text = preg_replace("/(?<=&nbsp;| |\n) /", '&nbsp;', $text);
    
    return "<code>$text</code>";
}


function bwlms_message_backticker_display_code($text) {
    $text = preg_replace_callback("|`(.*?)`|", "bwlms_message_backticker_encode", $text);
    $text = str_replace('<code></code>', '`', $text);
    return $text;
}

function bwlms_message_message_filter_content($html) {
    $html = apply_filters('comment_text', $html);
    return $html;
}
add_filter( 'bwlms_message_filter_display_message', 'bwlms_message_message_filter_content' );

// 메시지를 워드프레스 펑션으로 필터 적용을 한다
add_filter( 'bwlms_message_filter_display_message', 'wptexturize'            );
add_filter( 'bwlms_message_filter_display_message', 'convert_chars'          );
add_filter( 'bwlms_message_filter_display_message', 'make_clickable',      9 );
add_filter( 'bwlms_message_filter_display_message', 'force_balance_tags', 25 );
add_filter( 'bwlms_message_filter_display_message', 'convert_smilies',    20 );
add_filter( 'bwlms_message_filter_display_message', 'wpautop',            30 );
add_filter( 'bwlms_message_filter_display_message', 'capital_P_dangit', 	31 );

function bwlms_message_message_filter_title($html) {
    $html = apply_filters('the_title', $html);
    return $html;
}
add_filter( 'bwlms_message_filter_display_title', 'bwlms_message_message_filter_title' );

function bwlms_newmsg_counter() {
	$numNew = bwlms_message_get_new_message_number();
	echo "($numNew)";
}
add_action ('bwlms_newmessage_counter', 'bwlms_newmsg_counter');


function bwlms_message_send_new_message_filter( $newMsg )
{
		 return $newMsg;
}

add_filter('bwlms_message_filter_new_message_form', 'bwlms_message_send_new_message_filter');

//function bwlms_message_backticker_code_input_filter( $message ) {
function bwlms_message_backticker_code_input_filter( $message_title, $message_content ) {

	$message_title = bwlms_message_backticker_display_code($message_title);
	$message_content = bwlms_message_backticker_display_code($message_content);
}
add_filter( 'bwlms_message_filter_message_before_send', 'bwlms_message_backticker_code_input_filter', 5, 2);
add_filter( 'bwlms_message_filter_announcement_before_add', 'bwlms_message_backticker_code_input_filter', 5, 2);

function bwlms_message_kses_filter($message_title, $message_content  ) {
	
	$message_title = bwlms_message_backticker_display_code($message_title);
	$message_content = bwlms_message_backticker_display_code($message_content);
	
}
add_filter( 'bwlms_message_filter_message_before_send', 'bwlms_message_kses_filter', 5, 2);
add_filter( 'bwlms_message_filter_announcement_before_add', 'bwlms_message_kses_filter', 5, 2);

function bwlms_message_delete_message_link( $pID, $wholeThread )
	{
	$token = bwlms_message_create_nonce('delete_message');
	$del_url = bwlms_message_action_url("deletemessage&id=$pID&token=$token");
	echo"
	<div class='row'>
	<div class='large-12 medium-12 small-12 large-centered medium-centered small-centered columns'>

		<div class='row bwlmsmsgth-t-row'>

			<div class='large-6 medium-6 small-6 columns bwlmsmsgth-t-row-inner'> 

				<div class='bwlms-msgthread-backspace-btn'>
					<a href='javascript:history.go(-1)'>
						<i class='bwlmsmsgth-icons material-icons'>&#xE317;</i>
					</a>
				</div>

				<div class='bwlms-msgthread-delete-btn'>
					<a 
						href='".apply_filters('bwlms_message_delete_message_url', $del_url, $pID) ."' 
						onclick='return confirm(\"".__('Are you sure?', 'wptobemem')."\");'>
							".__("Delete", 'wptobemem')."	
					</a>
				</div>
			</div>
			
			<div class='large-6 medium-6 small-6 columns'></div>

		</div>
	
	</div>
	</div>
	";
	}
	
add_action('bwlms_message_display_in_message_header', 'bwlms_message_delete_message_link', 10, 2 );


function bwlms_theme_message_delete_message_link( $pID, $wholeThread )
	{
	$token = bwlms_message_create_nonce('delete_message');
	$del_url = bwlms_theme_message_action_url("deletemessage&id=$pID&token=$token");
	echo "<p><a href='".apply_filters('bwlms_theme_message_delete_message_url', $del_url, $pID) ."' onclick='return confirm(\"".__('Are you sure?', 'wptobemem')."\");'>".__("Delete", 'wptobemem')."</a></p>";
	}
	
add_action('bwlms_theme_message_display_in_message_header', 'bwlms_theme_message_delete_message_link', 10, 2 );
