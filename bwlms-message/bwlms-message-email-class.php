<?php

if (!class_exists('bwlms_message_email_class'))
{
  class bwlms_message_email_class
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
		add_action ('bwlms_message_action_message_after_send', array(&$this, 'send_email'));//, 10, 2);
		add_action ('bwlms_message_action_message_after_send', array(&$this, 'send_email'), 10, 4);

    }
	
	function send_email( $message_id, $message_to, $message_from, $message_title )
    {

      $notify = bwlms_message_get_user_option( 'allow_emails', 1, $message_to );
      if ($notify == '1')
      {
        $sendername = get_bloginfo("name");
        $sendermail = get_bloginfo("admin_email");
        $headers = "MIME-Version: 1.0\r\n" .
          "From: ".$sendername." "."<".$sendermail.">\r\n" . 
          "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\r\n";
		$subject =  get_bloginfo("name").': '.__('New Message', 'wptobemem');
		$message = __('You have received a new message in', 'wptobemem'). "\r\n";
		$message .= get_bloginfo("name")."\r\n";
		$message .= sprintf(__("From: %s", 'wptobemem'), bwlms_message_get_userdata($message_from, 'display_name', 'id') ). "\r\n";
		$message .= sprintf(__("Subject: %s", 'wptobemem'), $message_title ). "\r\n";
		$message .= __('Please Click the following link to view full Message.', 'wptobemem')."\r\n";
		$message .= bwlms_message_action_url('messagebox')."\r\n";
        $mailTo = bwlms_message_get_userdata( $message_to, 'user_email', 'id');
		
        wp_mail($mailTo, $subject, $message);
      }
    }

  } 
}

add_action('wp_loaded', array(bwlms_message_email_class::init(), 'actions_filters'));
?>