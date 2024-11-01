<?php

function bwlmsfields_do_install()
{

	$chk_force = false;
	// Warning! in case of $chk_force=true all existing data will remove
	if( !get_option( 'bwlmsfields_settings' ) || $chk_force == true ) {

		$bwlmsfields_settings = array( 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
		update_option( 'bwlmsfields_settings', $bwlmsfields_settings, '', 'yes' );

		// order, label, optionname, type, display, required, native, checked value, checked by default
		$bwlmsfields_fields_options_arr = array(
			array( 1,  'Profile Picture',    'bwlmsf_pic',		 'file',     'y', 'n', 'n', 'n', 'n', 'n','n'),	
			array( 2,  'First Name',         'first_name',       'text',     'y', 'n', 'y', 'n', 'n', 'n','n'),	
			array( 3,  'Last Name',          'last_name',        'text',     'y', 'n', 'y', 'n', 'n', 'n','n'),
			array( 4,  'Address 1',          'addr1',            'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 5,  'Address 2',          'addr2',            'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),	
			array( 6,  'City',               'city',             'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 7,  'State',              'thestate',         'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 8,  'Zip',                'zip',              'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 9,  'Country',            'country',          'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 10,  'Day Phone',          'phone1',           'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 11, 'Email',              'user_email',       'text',     'y', 'y', 'y', 'n', 'n', 'n','n'),
			array( 12, 'Confirm Email',      'confirm_email',    'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 13, 'Website',            'user_url',         'text',     'n', 'n', 'y', 'n', 'n', 'n','n'),
			array( 14, 'Biographical Info',  'description',      'textarea', 'n', 'n', 'y', 'n', 'n', 'n','n'),
			array( 15, 'Password',           'password',         'password', 'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 16, 'Confirm Password',   'confirm_password', 'password', 'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 17,  'Additional 01',      'bwlmsf01',        'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),	
			array( 18,  'Additional 02',      'bwlmsf02',        'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),	
			array( 19,  'Additional 03',      'bwlmsf03',        'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),	
			array( 20,  'Additional 04',      'bwlmsf04',        'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),	
			array( 21,  'Additional 05',      'bwlmsf05',        'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),	
			array( 22,  'Additional 06',      'bwlmsf06',        'text',     'n', 'n', 'n', 'n', 'n', 'n','n'),
			array( 23,  'Captcha',			'bwlms_captcha',     'captcha',  'n', 'n', 'y', 'n', 'n', 'n','n')
		);
		update_option( 'bwlmsfields_fieldsopt', $bwlmsfields_fields_options_arr ); 

		//Default action messages 
		$bwlmsfields_dialogs_arr = array(
			__("This content is restricted to site members.  If you are an existing user, please log in.  New users may register below.","wptobemem"),
			__("Sorry, that username is taken, please try another.","wptobemem"),
			__("Sorry, that email address already has an account.","wptobemem"),
			__("Congratulations! Your registration was successful.","wptobemem"),
			__("Profile updated successfully!","wptobemem"),
			__("Passwords did not match.","wptobemem"),
			__("Password successfully changed!","wptobemem"),
			__("Either the username or email address do not exist in our records.","wptobemem"),
			__("Password reset successfully! <br /><br />An email containing a new password has been sent to the email address on file for your account.","wptobemem"),
		);
		
		// Initilize event messages
		update_option( 'bwlmsfields_dialogs', $bwlmsfields_dialogs_arr); 

		//append_tos( 'new' );

		append_email();
		

	} 
	else {
	// If it is not first installation
		update_dialogs();
		append_email();
	
		$bwlmsfields_settings = get_option( 'bwlmsfields_settings' );

		$bwlmsfields_newsettings = array(
				'0', 							//  0 version
				$bwlmsfields_settings[1],		//  1 block posts
				$bwlmsfields_settings[2],		//  2 block pages
				$bwlmsfields_settings[3],		//  3 show excerpts on posts/pages
				$bwlmsfields_settings[4],		//  4 notify admin
				$bwlmsfields_settings[5],		//  5 moderate registration
				'0',							//  6 toggle captcha
				$bwlmsfields_settings[6],		//  7 turn off registration
				'1',							//  8 add use legacy forms (tables)
				$bwlmsfields_settings[7],		//  9 time based expiration
				$bwlmsfields_settings[8],		// 10 offer trial period
				$bwlmsfields_settings[9]		// 11 ignore warnings
		);
		update_option( 'bwlmsfields_settings', $bwlmsfields_newsettings );
	}
}

function append_email()
{
	$subj = __('Your registration info for [blogname]','wptobemem');		
	$body = __('Thank you for registering for [blogname]

	Your registration information is below.
	You may wish to retain a copy for your records.

	username: [username]
	password: [password]

	You may login here:
	[reglink]

	You may change your password here:
	[members-area]
	','wptobemem');
		
	$arr = array( 
		"subj" => $subj,
		"body" => $body
	);
	
	if( ! get_option( 'bwlmsfields_email_newreg' ) ) { 
		update_option( 'bwlmsfields_email_newreg', $arr, false ); 
	}
	
	$arr = $subj = $body = '';
	
	$subj = __('Thank you for registering for [blogname]','wptobemem');
	$body =	__('Thank you for registering for [blogname]. 
	Your registration has been received and is pending approval.
	You will receive login instructions upon approval of your account
	','wptobemem');

	$arr = array( 
		"subj" => $subj,
		"body" => $body
	);
	
	if( ! get_option( 'bwlmsfields_email_newmod' ) ) { 
		update_option( 'bwlmsfields_email_newmod', $arr, false );
	}
	
	$arr = $subj = $body = '';
	
	$subj = __('Your registration for [blogname] has been approved','wptobemem');
	$body = __('Your registration for [blogname] has been approved.

		Your registration information is below.
		You may wish to retain a copy for your records.

		username: [username]
		password: [password]

		You may login and change your password here:
		[members-area]

		You originally registered at:
		[reglink]
		','wptobemem');
	
	$arr = array( 
		"subj" => $subj,
		"body" => $body
	);
	
	if( ! get_option( 'bwlmsfields_email_appmod' ) ) { 
		update_option( 'bwlmsfields_email_appmod', $arr, false );
	}
	
	$arr = $subj = $body = '';
	
	$subj = __('Your password reset for [blogname]','wptobemem');
	$body = __('Your password for [blogname] has been reset

	Your new password is included below. You may wish to retain a copy for your records.

	password: [password]
	','wptobemem');

	$arr = array( 
		"subj" => $subj,
		"body" => $body
	);
	
	if( ! get_option( 'bwlmsfields_email_repass' ) ) { 
		update_option( 'bwlmsfields_email_repass', $arr, false );
	}
	
	$arr = $subj = $body = '';

	$subj = __('New user registration for [blogname]','wptobemem');
	$body = __('The following user registered for [blogname]:
	
	username: [username]
	email: [email]

	[fields]
	This user registered here:
	[reglink]

	user IP: [user-ip]
		
	activate user: [activate-user]
	','wptobemem');
	
		$arr = array( 
		"subj" => $subj,
		"body" => $body
	);
	
	if( ! get_option( 'bwlmsfields_email_notify' ) ) { 
		update_option( 'bwlmsfields_email_notify', $arr, false );
	}
	
	$arr = $subj = $body = '';

	$body = __('
	This is an automated message from [blogname]
	Please do not reply to this address','wptobemem');

	if( ! get_option( 'bwlmsfields_email_footer' ) ) { 
		update_option( 'bwlmsfields_email_footer', $body, false );
	}
	
	return true;
}

function update_dialogs()
{
	$bwlmsfields_dialogs_arr = get_option( 'bwlmsfields_dialogs' );

	$do_update = false;
	
	if( $bwlmsfields_dialogs_arr[0] == 
		"This content is restricted to site members.  If you are an existing user, please login.  New users may register below." ) {
		$bwlmsfields_dialogs_arr[0] = "This content is restricted to site members.  If you are an existing user, please log in.  New users may register below.";
		$do_update = true;
	}
	
	if( $bwlmsfields_dialogs_arr[3] == "Congratulations! Your registration was successful." ) {
		$bwlmsfields_dialogs_arr[3] = "Congratulations! Your registration was successful.";
		$do_update = true;
	}
	
	if( $do_update ) {
		update_option( 'bwlmsfields_dialogs', $bwlmsfields_dialogs_arr, '', 'yes' );
	}
	
	return;
}