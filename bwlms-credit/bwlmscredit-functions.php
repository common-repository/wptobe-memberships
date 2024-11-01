<?php

if ( ! class_exists( 'bwlmsCREDIT_Settings' ) ) :
	class bwlmsCREDIT_Settings {

		public $core;
		public $log_table;
		public $credit_id;

		public $is_multisite = false;
		public $use_master_template = false;
		public $use_central_logging = false;


		function __construct( $type = 'bwlmscredit_default' ) {

			$this->is_multisite = is_multisite();
			$this->use_master_template = bwlmscredit_override_settings();
			$this->use_central_logging = bwlmscredit_centralize_log();

			if ( $type == '' || $type === NULL ) $type = 'bwlmscredit_default';


			$option_id = 'bwlmscredit_pref_core';
			if ( $type != 'bwlmscredit_default' && $type != '' )
				$option_id .= '_' . $type;

			$this->core = bwlmscredit_get_option( $option_id, $this->defaults() );
			
			if ( $this->core !== false ) {
				foreach ( (array) $this->core as $key => $value ) {
					$this->$key = $value;
				}
			}

			if ( $type != '' )
				$this->credit_id = $type;

			if ( defined( 'BWLMSCREDIT_LOG_TABLE' ) )
				$this->log_table = BWLMSCREDIT_LOG_TABLE;
			else {
				global $wpdb;
				
				if ( $this->is_multisite && $this->use_central_logging )
					$this->log_table = $wpdb->base_prefix . 'bwlmsCREDIT_log';
				else
					$this->log_table = $wpdb->prefix . 'bwlmsCREDIT_log';
			}

			do_action_ref_array( 'bwlmscredit_settings', array( &$this ) );
		}

		public function defaults() {
			return array(
				'credit_id'   => 'bwlmscredit_default',
				'format'    => array(
					'type'       => 'bigint',
					'decimals'   => 0,
					'separators' => array(
						'decimal'   => '.',
						'thousand'  => ','
					)
				),
				'name'      => array(
					'singular' => __( 'Point', 'wptobemem' ),
					'plural'   => __( 'Points', 'wptobemem' )
				),
				'before'    => '',
				'after'     => '',
				'caps'      => array(
					'plugin'   => 'manage_options',
					'credits'    => 'export'
				),
				'max'       => 0,
				'exclude'   => array(
					'plugin_editors' => 0,
					'cred_editors'   => 0,
					'list'           => ''
				),
				'frequency' => array(
					'rate'     => 'always',
					'date'     => ''
				),
				'delete_user' => 0
			);
		}


		public function singular() {
			if ( ! isset( $this->core['name']['singular'] ) )
				return $this->name['singular'];

			return $this->core['name']['singular'];
		}


		public function plural() {
			if ( ! isset( $this->core['name']['plural'] ) )
				return $this->name['plural'];

			return $this->core['name']['plural'];
		}


		public function zero() {
			if ( ! isset( $this->format['decimals'] ) )
				$decimals = $this->core['format']['decimals'];
			else
				$decimals = $this->format['decimals'];

			return number_format( 0, $decimals );
		}

		public function number( $number = '' ) {
			if ( $number === '' ) return $number;

			if ( ! isset( $this->format['decimals'] ) )
				$decimals = (int) $this->core['format']['decimals'];
			else
				$decimals = (int) $this->format['decimals'];

			if ( $decimals > 0 )
				return number_format( (float) $number, $decimals, '.', '' );

			return (int) $number;
		}


		public function format_number( $number = '' ) {
			if ( $number === '' ) return $number;

			$number = $this->number( $number );
			$decimals = $this->format['decimals'];
			$sep_dec = $this->format['separators']['decimal'];
			$sep_tho = $this->format['separators']['thousand'];

			// Format
			$credits = number_format( $number, (int) $decimals, $sep_dec, $sep_tho );

			return apply_filters( 'bwlmscredit_format_number', $credits, $number, $this->core );
		}

		public function format_credits( $credits = 0, $before = '', $after = '', $force_in = false ) {
			// Prefix
			$prefix = '';
			if ( ! empty( $this->before ) )
				$prefix = $this->before . ' ';

			// Suffix
			$suffix = '';
			if ( ! empty( $this->after ) )
				$suffix = ' ' . $this->after;

			// Format credits
			$credits = $this->format_number( $credits );

			// Optional extras to insert before and after
			if ( $force_in )
				$layout = $prefix . $before . $credits . $after . $suffix;
			else
				$layout = $before . $prefix . $credits . $suffix . $after;

			return apply_filters( 'bwlmscredit_format_credits', $layout, $credits, $this );
		}

		public function round_value( $amount = 0, $up_down = false, $precision = 0 ) {
			if ( $amount == 0 || ! $up_down ) return $amount;

			// Use round() for precision
			if ( $precision !== false ) {
				if ( $up_down == 'up' )
					$_amount = round( $amount, (int) $precision, PHP_ROUND_HALF_UP );
				elseif ( $up_down == 'down' )
					$_amount = round( $amount, (int) $precision, PHP_ROUND_HALF_DOWN );
			}
			// Use ceil() or floor() for everything else
			else {
				if ( $up_down == 'up' )
					$_amount = ceil( $amount );
				elseif ( $up_down == 'down' )
					$_amount = floor( $amount );
			}

			return apply_filters( 'bwlmscredit_round_value', $_amount, $amount, $up_down, $precision );
		}


		public function apply_exchange_rate( $amount = 0, $rate = 1, $round = true ) {
			if ( ! is_numeric( $rate ) || $rate == 1 ) return $amount;

			$exchange = $amount/(float) $rate;
			if ( $round ) $exchange = round( $exchange );

			return apply_filters( 'bwlmscredit_apply_exchange_rate', $exchange, $amount, $rate, $round );
		}


		public function parse_template_tags( $content = '', $log_entry ) {
			// Prep
			$reference = $log_entry->ref;
			$ref_id = $log_entry->ref_id;
			$data = $log_entry->data;

			// Unserialize if serialized
			$data = maybe_unserialize( $data );

			// Run basic template tags first
			$content = $this->template_tags_general( $content );

			// Start by allowing others to play
			$content = apply_filters( 'bwlmscredit_parse_log_entry', $content, $log_entry );
			$content = apply_filters( "bwlmscredit_parse_log_entry_{$reference}", $content, $log_entry );

			// Get the reference type
			if ( isset( $data['ref_type'] ) || isset( $data['post_type'] ) ) {
				if ( isset( $data['ref_type'] ) )
					$type = $data['ref_type'];
				elseif ( isset( $data['post_type'] ) )
					$type = $data['post_type'];

				if ( $type == 'post' )
					$content = $this->template_tags_post( $content, $ref_id, $data );
				elseif ( $type == 'user' )
					$content = $this->template_tags_user( $content, $ref_id, $data );
				elseif ( $type == 'comment' )
					$content = $this->template_tags_comment( $content, $ref_id, $data );
				
				$content = apply_filters( "bwlmscredit_parse_tags_{$type}", $content, $log_entry );
			}

			return $content;
		}

		public function template_tags_general( $content = '' ) {
			$content = apply_filters( 'bwlmscredit_parse_tags_general', $content );

			// Singular
			$content = str_replace( array( '%singular%', '%Singular%' ), $this->singular(), $content );
			$content = str_replace( '%_singular%',       strtolower( $this->singular() ), $content );

			// Plural
			$content = str_replace(  array( '%plural%', '%Plural%' ), $this->plural(), $content );
			$content = str_replace( '%_plural%',         strtolower( $this->plural() ), $content );

			// Login URL
			$content = str_replace( '%login_url%',       wp_login_url(), $content );
			$content = str_replace( '%login_url_here%',  wp_login_url( get_permalink() ), $content );

			// Logout URL
			$content = str_replace( '%logout_url%',      wp_logout_url(), $content );
			$content = str_replace( '%logout_url_here%', wp_logout_url( get_permalink() ), $content );

			// Blog Related
			if ( preg_match( '%(num_members|blog_name|blog_url|blog_info|admin_email)%', $content, $matches ) ) {
				$content = str_replace( '%num_members%',     $this->count_members(), $content );
				$content = str_replace( '%blog_name%',       get_bloginfo( 'name' ), $content );
				$content = str_replace( '%blog_url%',        get_bloginfo( 'url' ), $content );
				$content = str_replace( '%blog_info%',       get_bloginfo( 'description' ), $content );
				$content = str_replace( '%admin_email%',     get_bloginfo( 'admin_email' ), $content );
			}

			//$content = str_replace( '', , $content );
			return $content;
		}

		public function template_tags_amount( $content = '', $amount = 0 ) {
			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'amount', 'credit|cred_f', $content ) ) return $content;
			$content = apply_filters( 'bwlmscredit_parse_tags_amount', $content, $amount, $this );
			$content = str_replace( '%cred_f%', $this->format_credits( $amount ), $content );
			$content = str_replace( '%credit%',   $amount, $content );
			return $content;
		}

		public function template_tags_post( $content = '', $ref_id = NULL, $data = '' ) {
			if ( $ref_id === NULL ) return $content;
			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'post', 'post_title|post_url|link_with_title|post_type', $content ) ) return $content;

			// Get Post Object
			$post = get_post( $ref_id );

			// Post does not exist
			if ( $post === NULL ) {
				if ( ! is_array( $data ) || ! array_key_exists( 'ID', $data ) ) return $content;
				$post = new StdClass();
				foreach ( $data as $key => $value ) {
					if ( $key == 'post_title' ) $value .= ' (' . __( 'Deleted', 'wptobemem' ) . ')';
					$post->$key = $value;
				}
				$url = get_permalink( $post->ID );
				if ( empty( $url ) ) $url = '#item-has-been-deleted';
			}
			else {
				$url = get_permalink( $post->ID );
			}

			// Let others play first
			$content = apply_filters( 'bwlmscredit_parse_tags_post', $content, $post, $data );

			// Replace template tags
			$content = str_replace( '%post_title%',      $post->post_title, $content );
			$content = str_replace( '%post_url%',        $url, $content );
			$content = str_replace( '%link_with_title%', '<a href="' . $url . '">' . $post->post_title . '</a>', $content );

			$post_type = get_post_type_object( $post->post_type );
			if ( $post_type !== NULL ) {
				$content = str_replace( '%post_type%', $post_type->labels->singular_name, $content );
				unset( $post_type );
			}

			//$content = str_replace( '', $post->, $content );
			unset( $post );

			return $content;
		}

		public function template_tags_user( $content = '', $ref_id = NULL, $data = '' ) {
			if ( $ref_id === NULL ) return $content;
			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'user', 'user_id|user_name|user_name_en|display_name|user_profile_url|user_profile_link|user_nicename|user_email|user_url|balance|balance_f', $content ) ) return $content;

			// Get User Object
			if ( $ref_id !== false )
				$user = get_userdata( $ref_id );
			// User object is passed on though $data
			elseif ( $ref_id === false && is_object( $data ) && isset( $data->ID ) )
				$user = $data;
			// User array is passed on though $data
			elseif ( $ref_id === false && is_array( $data ) || array_key_exists( 'ID', (array) $data ) ) {
				$user = new StdClass();
				foreach ( $data as $key => $value ) {
					if ( $key == 'login' )
						$user->user_login = $value;
					else
						$user->$key = $value;
				}
			}
			else return $content;

			// Let others play first
			$content = apply_filters( 'bwlmscredit_parse_tags_user', $content, $user, $data );

			if ( ! isset( $user->ID ) ) return $content;

			// Replace template tags
			$content = str_replace( '%user_id%',          $user->ID, $content );
			$content = str_replace( '%user_name%',        $user->user_login, $content );
			$content = str_replace( '%user_name_en%',     urlencode( $user->user_login ), $content );

			// Get Profile URL
			if ( function_exists( 'bp_core_get_user_domain' ) )
				$url = bp_core_get_user_domain( $user->ID );
			else {
				global $wp_rewrite;
				$url = get_bloginfo( 'url' ) . '/' . $wp_rewrite->author_base . '/' . urlencode( $user->user_login ) . '/';
			}
			$url = apply_filters( 'bwlmscredit_users_profile_url', $url, $user );

			$content = str_replace( '%display_name%',       $user->display_name, $content );
			$content = str_replace( '%user_profile_url%',   $url, $content );
			$content = str_replace( '%user_profile_link%',  '<a href="' . $url . '">' . $user->display_name . '</a>', $content );

			$content = str_replace( '%user_nicename%',      ( isset( $user->user_nicename ) ) ? $user->user_nicename : '', $content );
			$content = str_replace( '%user_email%',         ( isset( $user->user_email ) ) ? $user->user_email : '', $content );
			$content = str_replace( '%user_url%',           ( isset( $user->user_url ) ) ? $user->user_url : '', $content );

			// Account Related
			$balance = $this->get_users_cred( $user->ID );
			$content = str_replace( '%balance%',            $balance, $content );
			$content = str_replace( '%balance_f%',          $this->format_credits( $balance ), $content );

			//$content = str_replace( '', $user->, $content );
			unset( $user );

			return $content;
		}

		public function template_tags_comment( $content = '', $ref_id = NULL, $data = '' ) {
			if ( $ref_id === NULL ) return $content;
			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'comment', 'comment_id|c_post_id|c_post_title|c_post_url|c_link_with_title', $content ) ) return $content;

			// Get Comment Object
			$comment = get_comment( $ref_id );

			// Comment does not exist
			if ( $comment === NULL ) {
				if ( !is_array( $data ) || !array_key_exists( 'comment_ID', $data ) ) return $content;
				$comment = new StdClass();
				foreach ( $data as $key => $value ) {
					$comment->$key = $value;
				}
				$url = get_permalink( $comment->comment_post_ID );
				if ( empty( $url ) ) $url = '#item-has-been-deleted';

				$title = get_the_title( $comment->comment_post_ID );
				if ( empty( $title ) ) $title = __( 'Deleted Item', 'wptobemem' );
			}
			else {
				$url = get_permalink( $comment->comment_post_ID );
				$title = get_the_title( $comment->comment_post_ID );
			}

			// Let others play first
			$content = apply_filters( 'bwlmscredit_parse_tags_comment', $content, $comment, $data );

			$content = str_replace( '%comment_id%',        $comment->comment_ID, $content );

			$content = str_replace( '%c_post_id%',         $comment->comment_post_ID, $content );
			$content = str_replace( '%c_post_title%',      $title, $content );

			$content = str_replace( '%c_post_url%',        $url, $content );
			$content = str_replace( '%c_link_with_title%', '<a href="' . $url . '">' . $title . '</a>', $content );

			//$content = str_replace( '', $comment->, $content );
			unset( $comment );
			return $content;
		}

		public function has_tags( $type = '', $tags = '', $content = '' ) {
			$tags = apply_filters( 'bwlmscredit_has_tags', $tags, $content );
			$tags = apply_filters( 'bwlmscredit_has_tags_' . $type, $tags, $content );
			if ( ! preg_match( '%(' . trim( $tags ) . ')%', $content, $matches ) ) return false;
			return true;
		}


//		public function available_template_tags( $available = array(), $custom = '' ) {
//			// Prep
//			$links = $template_tags = array();
//
//			// General
//			if ( in_array( 'general', $available ) )
//				$template_tags[] = array(
//					'title' => __( 'General', 'wptobemem' ),
//					'url'   => 'http://wptobe.com/memberships/category/template-tags/temp-general/'
//				);
//
//			// User
//			if ( in_array( 'user', $available ) )
//				$template_tags[] = array(
//					'title' => __( 'User Related', 'wptobemem' ),
//					'url'   => 'http://wptobe.com/memberships/category/template-tags/temp-user/'
//				);
//
//			// Post
//			if ( in_array( 'post', $available ) )
//				$template_tags[] = array(
//					'title' => __( 'Post Related', 'wptobemem' ),
//					'url'   => 'http://wptobe.com/memberships/category/template-tags/temp-post/'
//				);
//
//			// Comment
//			if ( in_array( 'comment', $available ) )
//				$template_tags[] = array(
//					'title' => __( 'Comment Related', 'wptobemem' ),
//					'url'   => 'http://wptobe.com/memberships/category/template-tags/temp-comment/'
//				);
//
//			// Widget
//			if ( in_array( 'widget', $available ) )
//				$template_tags[] = array(
//					'title' => __( 'Widget Related', 'wptobemem' ),
//					'url'   => 'http://wptobe.com/memberships/category/template-tags/temp-widget/'
//				);
//
//			// Amount
//			if ( in_array( 'amount', $available ) )
//				$template_tags[] = array(
//					'title' => __( 'Amount Related', 'wptobemem' ),
//					'url'   => 'http://wptobe.com/memberships/category/template-tags/temp-amount/'
//				);
//
//			// Video
//			if ( in_array( 'video', $available ) )
//				$template_tags[] = array(
//					'title' => __( 'Video Related', 'wptobemem' ),
//					'url'   => 'http://wptobe.com/memberships/category/template-tags/temp-video/'
//				);
//
//			if ( ! empty( $template_tags ) ) {
//				foreach ( $template_tags as $tag ) {
//					$links[] = '<a href="' . $tag['url'] . '" target="_blank">' . $tag['title'] . '</a>';
//				}
//			}
//
//			if ( ! empty( $custom ) )
//				$custom = ' ' . __( 'and', 'wptobemem' ) . ' ' . $custom;
//
//			return __( 'Available Template Tags:', 'wptobemem' ) . ' ' . implode( ', ', $links ) . $custom . '.';
//		}


		public function allowed_tags( $data = '', $allow = '' ) {
			if ( $allow === false )
				return strip_tags( $data );
			elseif ( ! empty( $allow ) )
				return strip_tags( $data, $allow );

			return strip_tags( $data, apply_filters( 'bwlmscredit_allowed_tags', '<a><br><em><strong><span>' ) );
		}

//		public function allowed_html_tags() {
//			return apply_filters( 'bwlmscredit_allowed_html_tags', array(
//				'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ),
//				'abbr' => array( 'title' => array() ), 'acronym' => array( 'title' => array() ),
//				'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
//				'div' => array( 'class' => array(), 'id' => array() ), 'span' => array( 'class' => array() ),
//				'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
//				'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
//				'img' => array( 'src' => array(), 'class' => array(), 'alt' => array() ),
//				'br' => array( 'class' => array() )
//			), $this );
//		}

		public function edit_credits_cap() {
			if ( ! isset( $this->caps['credits'] ) || empty( $this->caps['credits'] ) )
				$this->caps['credits'] = 'delete_users';

			return apply_filters( 'bwlmscredit_edit_credits_cap', $this->caps['credits'] );
		}

		public function can_edit_credits( $user_id = '' ) {
			$result = false;

			if ( ! function_exists( 'get_current_user_id' ) )
				require_once( ABSPATH . WPINC . '/user.php' );

			// Grab current user id
			if ( empty( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! function_exists( 'user_can' ) )
				require_once( ABSPATH . WPINC . '/capabilities.php' );

			// Check if user can
			if ( user_can( $user_id, $this->edit_credits_cap() ) )
				$result = true;

			return apply_filters( 'bwlmscredit_can_edit_credits', $result, $user_id );
		}

		public function edit_plugin_cap() {
			if ( ! isset( $this->caps['plugin'] ) || empty( $this->caps['plugin'] ) )
				$this->caps['plugin'] = 'manage_options';

			return apply_filters( 'bwlmscredit_edit_plugin_cap', $this->caps['plugin'] );
		}


		public function can_edit_plugin( $user_id = '' ) {
			$result = false;

			if ( ! function_exists( 'get_current_user_id' ) )
				require_once( ABSPATH . WPINC . '/user.php' );

			// Grab current user id
			if ( empty( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! function_exists( 'user_can' ) )
				require_once( ABSPATH . WPINC . '/capabilities.php' );

			// Check if user can
			if ( user_can( $user_id, $this->edit_plugin_cap() ) )
				$result = true;
			
			return apply_filters( 'bwlmscredit_can_edit_plugin', $result, $user_id );
		}

		public function in_exclude_list( $user_id = '' ) {
			$result = false;

			// Grab current user id
			if ( empty( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! isset( $this->exclude['list'] ) )
				$this->exclude['list'] = '';

			$list = wp_parse_id_list( $this->exclude['list'] );
			if ( in_array( $user_id, $list ) )
				$result = true;

			return apply_filters( 'bwlmscredit_is_excluded_list', $result, $user_id );
		}

		public function exclude_plugin_editors() {
			return (bool) $this->exclude['plugin_editors'];
		}


		public function exclude_credits_editors() {
			return (bool) $this->exclude['cred_editors'];
		}

		public function exclude_user( $user_id = NULL ) {

			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			if ( apply_filters( 'bwlmscredit_exclude_user', false, $user_id, $this ) === true ) return true;

			if ( $this->exclude_plugin_editors() && $this->can_edit_plugin( $user_id ) ) return true;
			if ( $this->exclude_credits_editors() && $this->can_edit_credits( $user_id ) ) return true;

			if ( $this->in_exclude_list( $user_id ) ) return true;

			return false;

		}

		public function count_members() {
			global $wpdb;
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users};" );
		}

		public function get_credit_id() {
			if ( ! isset( $this->credit_id ) || $this->credit_id == '' )
				$this->credit_id = 'bwlmscredit_default';

			return $this->credit_id;
		}

		public function max() {
			if ( ! isset( $this->max ) )
				$this->max = 0;

			return $this->max;
		}

		public function get_users_balance( $user_id = NULL, $type = NULL ) {
			if ( $user_id === NULL ) return $this->zero();

			$types = bwlmscredit_get_types();
			if ( $type === NULL || ! array_key_exists( $type, $types ) ) $type = $this->get_credit_id();

			$balance = bwlmscredit_get_user_meta( $user_id, $type, '', true );
			if ( $balance == '' ) $balance = $this->zero();

			// Let others play
			$balance = apply_filters( 'bwlmscredit_get_users_cred', $balance, $this, $user_id, $type );

			return $this->number( $balance );
		}
			// Replaces
			public function get_users_cred( $user_id = NULL, $type = NULL ) {
				return $this->get_users_balance( $user_id, $type );
			}

		public function update_users_balance( $user_id = NULL, $amount = NULL, $type = 'bwlmscredit_default' ) {

			// Minimum Requirements: User id and amount can not be null
			if ( $user_id === NULL || $amount === NULL ) return $amount;
			if ( empty( $type ) ) $type = $this->get_credit_id();

			// Enforce max
			if ( $this->max() > $this->zero() && $amount > $this->max() ) {
				$amount = $this->number( $this->max() );

				do_action( 'bwlmscredit_max_enforced', $user_id, $_amount, $this->max() );
			}

			// Adjust credits
			$current_balance = $this->get_users_balance( $user_id, $type );
			$new_balance = $current_balance+$amount;

			// Update credits
			bwlmscredit_update_user_meta( $user_id, $type, '', $new_balance );

			// Update total credits
			$total = bwlmscredit_query_users_total( $user_id, $type );
			bwlmscredit_update_user_meta( $user_id, $type, '_total', $total );

			// Let others play
			do_action( 'bwlmscredit_update_user_balance', $user_id, $current_balance, $amount, $type );

			// Return the new balance
			return $this->number( $new_balance );

		}

		public function add_credits( $ref = '', $user_id = '', $amount = '', $entry = '', $ref_id = '', $data = '', $type = 'bwlmscredit_default' ) {

			if ( empty( $ref ) || empty( $user_id ) || empty( $amount ) ) return false;
			if ( $this->exclude_user( $user_id ) === true ) return false;
			$amount = $this->number( $amount );
			if ( $amount == $this->zero() || $amount == 0 ) return false;
			if ( $this->max() > $this->zero() && $amount > $this->max() ) {
				$amount = $this->number( $this->max() );

				do_action( 'bwlmscredit_max_enforced', $user_id, $_amount, $this->max() );
			}

			$execute = apply_filters( 'bwlmscredit_add', true, compact( 'ref', 'user_id', 'amount', 'entry', 'ref_id', 'data', 'type' ), $this );

			if ( $execute === true ) {

				$run_this = apply_filters( 'bwlmscredit_run_this', compact( 'ref', 'user_id', 'amount', 'entry', 'ref_id', 'data', 'type' ), $this );

				$this->add_to_log(
					$run_this['ref'],
					$run_this['user_id'],
					$run_this['amount'],
					$run_this['entry'],
					$run_this['ref_id'],
					$run_this['data'],
					$run_this['type']
				);

				$this->update_users_balance( (int) $run_this['user_id'], $run_this['amount'], $run_this['type'] );

			}
			else { $run_this = false; }

			return apply_filters( 'bwlmscredit_add_finished', $execute, $run_this, $this );
		}


		public function add_to_log( $ref = '', $user_id = '', $amount = '', $entry = '', $ref_id = '', $data = '', $type = 'bwlmscredit_default' ) {

			if ( empty( $ref ) || empty( $user_id ) || empty( $amount ) || empty( $entry ) ) return false;

			if ( $amount == $this->zero() || $amount == 0 ) return false;

			global $wpdb;

			$entry = $this->allowed_tags( $entry );

			if ( $this->max() > $this->zero() && $amount > $this->max() ) {
				$amount = $this->number( $this->max() );
			}

			if ( empty( $type ) ) $type = $this->get_credit_id();

			if ( $this->format['decimals'] > 0 )
				$format = '%f';
			elseif ( $this->format['decimals'] == 0 )
				$format = '%d';
			else
				$format = '%s';

			$time = apply_filters( 'bwlmscredit_log_time', date_i18n( 'U' ), $ref, $user_id, $amount, $entry, $ref_id, $data, $type );

			$new_entry = $wpdb->insert(
				$this->log_table,
				array(
					'ref'     => $ref,
					'ref_id'  => $ref_id,
					'user_id' => (int) $user_id,
					'credits'   => $amount,
					'ctype'   => $type,
					'time'    => $time,
					'entry'   => $entry,
					'data'    => ( is_array( $data ) || is_object( $data ) ) ? serialize( $data ) : $data
				),
				array( '%s', '%d', '%d', $format, '%s', '%d', '%s', ( is_numeric( $data ) ) ? '%d' : '%s' )
			);

			if ( ! $new_entry ) return false;
			
			delete_transient( 'bwlmscredit_log_entries' );
			return true;

		}

		function has_entry( $reference = '', $ref_id = '', $user_id = '', $data = '', $type = '' ) {
			global $wpdb;

			$where = $prep = array();
			if ( ! empty( $reference ) ) {
				$where[] = 'ref = %s';
				$prep[] = $reference;
			}

			if ( ! empty( $ref_id ) ) {
				$where[] = 'ref_id = %d';
				$prep[] = $ref_id;
			}

			if ( ! empty( $user_id ) ) {
				$where[] = 'user_id = %d';
				$prep[] = abs( $user_id );
			}

			if ( ! empty( $data ) ) {
				$where[] = 'data = %s';
				$prep[] = maybe_serialize( $data );
			}

			if ( empty( $type ) )
				$type = $this->credit_id;

			$where[] = 'ctype = %s';
			$prep[] = sanitize_text_field( $type );

			$where = implode( ' AND ', $where );

			$has = false;
			if ( ! empty( $where ) ) {
				$sql = "SELECT * FROM {$this->log_table} WHERE {$where};";
				$wpdb->get_results( $wpdb->prepare( $sql, $prep ) );
				if ( $wpdb->num_rows > 0 )
					$has = true;
			}

			return apply_filters( 'bwlmscredit_has_entry', $has, $reference, $ref_id, $user_id, $data, $type );
		}
	}
endif;

if ( ! function_exists( 'bwlmscredit_label' ) ) :
	function bwlmscredit_label( $trim = false )
	{
		global $bwlmscredit_label;
		if ( ! isset( $bwlmscredit_label ) || empty( $bwlmscredit_label ) )
			$name = apply_filters( 'bwlmscredit_label', 'Wptobe Points' );

		if ( $trim )
			$name = strip_tags( $name );

		return $name;
	}
endif;


if ( ! function_exists( 'bwlmscredit' ) ) :
	function bwlmscredit( $type = 'bwlmscredit_default' )
	{
		if ( $type != 'bwlmscredit_default' )
			return new bwlmsCREDIT_Settings( $type );

		global $bwlmscredit;

		if ( ! isset( $bwlmscredit ) || ! is_object( $bwlmscredit ) )
			$bwlmscredit = new bwlmsCREDIT_Settings();

		return $bwlmscredit;
	}
endif;


if ( ! function_exists( 'bwlmscredit_get_types' ) ) :
	function bwlmscredit_get_types()
	{
		$types = array();

		$available_types = bwlmscredit_get_option( 'bwlmscredit_types', array( 'bwlmscredit_default' => bwlmscredit_label() ) );
		if ( count( $available_types ) > 1 ) {
			foreach ( $available_types as $type => $label ) {
				if ( $type == 'bwlmscredit_default' ) {
					$_bwlmscredit = bwlmscredit( $type );
					$label = $_bwlmscredit->plural();
				}

				$types[ $type ] = $label;
			}
		}
		else {
			$types = $available_types;
		}

		return apply_filters( 'bwlmscredit_types', $types );
	}
endif;


if ( ! function_exists( 'bwlmscredit_get_settings_network' ) ) :
	function bwlmscredit_get_settings_network()
	{
		if ( ! is_multisite() ) return false;

		$defaults = array(
			'master'  => 0,
			'central' => 0,
			'block'   => ''
		);
		$settings = get_blog_option( 1, 'bwlmscredit_network', $defaults );

		return $settings;
	}
endif;


if ( ! function_exists( 'bwlmscredit_override_settings' ) ) :
	function bwlmscredit_override_settings()
	{
		// Not a multisite
		if ( ! is_multisite() ) return false;

		$bwlmscredit_network = bwlmscredit_get_settings_network();
		if ( $bwlmscredit_network['master'] ) return true;

		return false;
	}
endif;


if ( ! function_exists( 'bwlmscredit_centralize_log' ) ) :
	function bwlmscredit_centralize_log()
	{
		// Not a multisite
		if ( ! is_multisite() ) return true;

		$bwlmscredit_network = bwlmscredit_get_settings_network();
		if ( $bwlmscredit_network['central'] ) return true;

		return false;
	}
endif;


if ( ! function_exists( 'bwlmscredit_get_option' ) ) :
	function bwlmscredit_get_option( $option_id, $default = array() )
	{
		if ( is_multisite() ) {
			if ( bwlmscredit_override_settings() )
				$settings = get_blog_option( 1, $option_id, $default );
			else
				$settings = get_blog_option( $GLOBALS['blog_id'], $option_id, $default );
		}
		else {
			$settings = get_option( $option_id, $default );
		}

		return $settings;
	}
endif;

if ( ! function_exists( 'bwlmscredit_update_option' ) ) :
	function bwlmscredit_update_option( $option_id, $value = '' )
	{
		if ( is_multisite() ) {
			if ( bwlmscredit_override_settings() )
				return update_blog_option( 1, $option_id, $value );
			else
				return update_blog_option( $GLOBALS['blog_id'], $option_id, $value );
		}
		else {
			return update_option( $option_id, $value );
		}
	}
endif;

if ( ! function_exists( 'bwlmscredit_delete_option' ) ) :
	function bwlmscredit_delete_option( $option_id )
	{
		if ( is_multisite() ) {
			if ( bwlmscredit_override_settings() )
				delete_blog_option( 1, $option_id );
			else
				delete_blog_option( $GLOBALS['blog_id'], $option_id );
		}
		else {
			delete_option( $option_id );
		}
	}
endif;

if ( ! function_exists( 'bwlmscredit_get_user_meta' ) ) :
	function bwlmscredit_get_user_meta( $user_id, $key, $end = '', $unique = true )
	{
		if ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_centralize_log() && $key != 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		elseif ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_override_settings() && $key == 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		$key .= $end;

		return get_user_meta( $user_id, $key, $unique );
	}
endif;


if ( ! function_exists( 'bwlmscredit_add_user_meta' ) ) :
	function bwlmscredit_add_user_meta( $user_id, $key, $end = '', $value = '', $unique = true )
	{
		if ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_centralize_log() && $key != 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		elseif ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_override_settings() && $key == 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		$key .= $end;

		return add_user_meta( $user_id, $key, $value, $unique );
	}
endif;

if ( ! function_exists( 'bwlmscredit_update_user_meta' ) ) :
	function bwlmscredit_update_user_meta( $user_id, $key, $end = '', $value = '' )
	{
		if ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_centralize_log() && $key != 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		elseif ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_override_settings() && $key == 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		$key .= $end;

		return update_user_meta( $user_id, $key, $value );
	}
endif;

if ( ! function_exists( 'bwlmscredit_delete_user_meta' ) ) :
	function bwlmscredit_delete_user_meta( $user_id, $key, $end = '' )
	{
		if ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_centralize_log() && $key != 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		elseif ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_override_settings() && $key == 'bwlmscredit_rank' )
			$key .= '_' . $GLOBALS['blog_id'];

		$key .= $end;

		return delete_user_meta( $user_id, $key );
	}
endif;


if ( ! function_exists( 'bwlmscredit_is_admin' ) ) :
	function bwlmscredit_is_admin( $user_id = NULL, $type = 'bwlmscredit_default' )
	{
		$bwlmscredit = bwlmscredit( $type );
		if ( $user_id === NULL ) $user_id = get_current_user_id();

		if ( $bwlmscredit->can_edit_credits( $user_id ) || $bwlmscredit->can_edit_plugin( $user_id ) ) return true;

		return false;
	}
endif;


if ( ! function_exists( 'bwlmscredit_exclude_user' ) ) :
	function bwlmscredit_exclude_user( $user_id = NULL, $type = 'bwlmscredit_default' )
	{
		$bwlmscredit = bwlmscredit( $type );
		if ( $user_id === NULL ) $user_id = get_current_user_id();
		return $bwlmscredit->exclude_user( $user_id );
	}
endif;


if ( ! function_exists( 'bwlmscredit_get_users_cred' ) ) :
	function bwlmscredit_get_users_cred( $user_id = NULL, $type = 'bwlmscredit_default' )
	{
		if ( $user_id === NULL ) $user_id = get_current_user_id();

		if ( $type == '' )
			$type = 'bwlmscredit_default';

		$bwlmscredit = bwlmscredit( $type );
		return $bwlmscredit->get_users_cred( $user_id, $type );
	}
endif;

if ( ! function_exists( 'bwlmscredit_format_number' ) ) :
	function bwlmscredit_format_number( $value = NULL, $type = 'bwlmscredit_default' )
	{
		$bwlmscredit = bwlmscredit( $type );
		if ( $value === NULL )
			return $bwlmscredit->zero();

		return $bwlmscredit->format_number( $value );
	}
endif;


if ( ! function_exists( 'bwlmscredit_format_credits' ) ) :
	function bwlmscredit_format_credits( $value = NULL, $type = 'bwlmscredit_default' )
	{
		$bwlmscredit = bwlmscredit( $type );
		if ( $value === NULL ) $bwlmscredit->zero();

		return $bwlmscredit->format_credits( $value );
	}
endif;


if ( ! function_exists( 'bwlmscredit_add' ) ) :
	function bwlmscredit_add( $ref = '', $user_id = '', $amount = '', $entry = '', $ref_id = '', $data = '', $type = 'bwlmscredit_default' )
	{
		// $ref, $user_id and $credit is required
		if ( $ref == '' || $user_id == '' || $amount == '' ) return false;

		// Init bwlmsCREDIT
		$bwlmscredit = bwlmscredit( $type );

		// Add credits
		return $bwlmscredit->add_credits( $ref, $user_id, $amount, $entry, $ref_id, $data, $type );
	}
endif;


if ( ! function_exists( 'bwlmscredit_get_log_exports' ) ) :
	function bwlmscredit_get_log_exports()
	{
		$defaults = array(
			'all'      => array(
				'label'    => __( 'Entire Log', 'wptobemem' ),
				'my_label' => '',
				'class'    => 'btn btn-primary button button-secondary'
			),
			'display'  => array(
				'label'    => __( 'Displayed Rows', 'wptobemem' ),
				'my_label' => __( 'Displayed Rows', 'wptobemem' ),
				'class'    => 'btn btn-default button button-secondary'
			)
		);

		if ( isset( $_REQUEST['ctype'] ) || isset( $_REQUEST['show'] ) )
			$defaults['search'] = array(
				'label'    => __( 'Search Results', 'wptobemem' ),
				'my_label' => __( 'My Entire Log', 'wptobemem' ),
				'class'    => 'btn btn-default button button-secondary'
			);

		return apply_filters( 'bwlmscredit_log_exports', $defaults );
	}
endif;


if ( ! function_exists( 'bwlmscredit_get_users_total' ) ) :
	function bwlmscredit_get_users_total( $user_id = '', $type = 'bwlmscredit_default' )
	{
		if ( $user_id == '' ) return 0;

		if ( $type == '' ) $type = 'bwlmscredit_default';
		$bwlmscredit = bwlmscredit( $type );

		$total = bwlmscredit_get_user_meta( $user_id, $type, '_total' );
		if ( $total == '' ) {
			$total = bwlmscredit_query_users_total( $user_id, $type );
			bwlmscredit_update_user_meta( $user_id, $type, '_total', $total );
		}

		$total = apply_filters( 'bwlmscredit_get_users_total', $total, $user_id, $type );
		return $bwlmscredit->number( $total );
	}
endif;

if ( ! function_exists( 'bwlmscredit_query_users_total' ) ) :
	function bwlmscredit_query_users_total( $user_id, $type = 'bwlmscredit_default' )
	{
		global $wpdb;

		$bwlmscredit = bwlmscredit( $type );

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( credits ) 
			FROM {$bwlmscredit->log_table} 
			WHERE user_id = %d
				AND ( ( credits > 0 ) OR ( credits < 0 AND ref = 'manual' ) )
				AND ctype = %s;", $user_id, $type ) );

		if ( $total === NULL ) {

			if ( is_multisite() && $GLOBALS['blog_id'] > 1 && ! bwlmscredit_centralize_log() )
				$type .= '_' . $GLOBALS['blog_id'];

			$total = $wpdb->get_var( $wpdb->prepare( "
				SELECT meta_value 
				FROM {$wpdb->usermeta} 
				WHERE user_id = %d 
				AND meta_key = %s;", $user_id, $type ) );

			if ( $total === NULL )
				$total = 0;
		}

		return apply_filters( 'bwlmscredit_query_users_total', $total, $user_id, $type, $bwlmscredit );
	}
endif;


if ( ! function_exists( 'bwlmscredit_update_users_total' ) ) :
	function bwlmscredit_update_users_total( $type = 'bwlmscredit_default', $request = NULL, $bwlmscredit = NULL )
	{
		if ( $request === NULL || ! is_object( $bwlmscredit ) || ! isset( $request['user_id'] ) || ! isset( $request['amount'] ) ) return false;

		if ( $type == '' )
			$type = $bwlmscredit->get_credit_id();

		$amount = $bwlmscredit->number( $request['amount'] );
		$user_id = absint( $request['user_id'] );

		$users_total = bwlmscredit_get_user_meta( $user_id, $type, '_total', true );
		if ( $users_total == '' )
			$users_total = bwlmscredit_query_users_total( $user_id, $type );

		$new_total = $bwlmscredit->number( $users_total+$amount );
		bwlmscredit_update_user_meta( $user_id, $type, '_total', $new_total );

		return apply_filters( 'bwlmscredit_update_users_total', $new_total, $type, $request, $bwlmscredit );
	}
endif;


if ( ! function_exists( 'bwlmscredit_apply_defaults' ) ) :
	function bwlmscredit_apply_defaults( &$pref, $set )
	{
		$set = (array) $set;
		$return = array();
		foreach ( $pref as $key => $value ) {
			if ( array_key_exists( $key, $set ) ) {
				if ( is_array( $value ) && ! empty( $value ) )
					$return[ $key ] = bwlmscredit_apply_defaults( $value, $set[ $key ] );
				else
					$return[ $key ] = $set[ $key ];
			}
			else $return[ $key ] = $value;
		}
		return $return;
	}
endif;


if ( ! function_exists( 'bwlmscredit_is_site_blocked' ) ) :
	function bwlmscredit_is_site_blocked( $blog_id = NULL )
	{
		// Only applicable for multisites
		if ( ! is_multisite() || ! isset( $GLOBALS['blog_id'] ) ) return false;

		$reply = false;

		if ( $blog_id === NULL )
			$blog_id = $GLOBALS['blog_id'];

		// Get Network settings
		$network = bwlmscredit_get_settings_network();

		// Only applicable if the block is set and this is not the main blog
		if ( ! empty( $network['block'] ) && $blog_id > 1 ) {

			// Clean up list to make sure no white spaces are used
			$list = explode( ',', $network['block'] );
			$clean = array();
			foreach ( $list as $blog_id ) {
				$clean[] = trim( $blog_id );
			}

			// Check if blog is blocked from using bwlmsCREDIT.
			if ( in_array( $blog_id, $clean ) ) $reply = true;

		}

		return apply_filters( 'bwlmscredit_is_site_blocked', $reply, $blog_id );

	}
endif;


if ( ! function_exists( 'bwlmscredit_install_log' ) ) :
	function bwlmscredit_install_log( $decimals = 0, $table = NULL )
	{
		if ( is_multisite() && get_blog_option( $GLOBALS['blog_id'], 'bwlmscredit_version_db', false ) !== false ) return true;
		elseif ( ! is_multisite() && get_option( 'bwlmscredit_version_db', false ) !== false ) return true;//멀티사이트ㅏ 아니고 bwlmscredit_version_db 이 없으면

		global $wpdb;

		if ( $table === NULL ) {
			$bwlmscredit = bwlmscredit();
			$table = $bwlmscredit->log_table;
		}

		if ( $decimals > 0 ) {
			if ( $decimals > 4 )
				$cred_format = "decimal(32,$decimals)";
			else
				$cred_format = "decimal(22,$decimals)";
		}
		else {
			$cred_format = 'bigint(22)';
		}

		$wpdb->hide_errors();

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) )
				$collate .= "DEFAULT CHARACTER SET {$wpdb->charset}";
			if ( ! empty( $wpdb->collate ) )
				$collate .= " COLLATE {$wpdb->collate}";
		}

		// Log structure
		$sql = "
			id int(11) NOT NULL AUTO_INCREMENT, 
			ref VARCHAR(256) NOT NULL, 
			ref_id int(11) DEFAULT NULL, 
			user_id int(11) DEFAULT NULL, 
			credits $cred_format DEFAULT NULL, 
			ctype VARCHAR(64) DEFAULT 'bwlmscredit_default', 
			time bigint(20) DEFAULT NULL, 
			entry LONGTEXT DEFAULT NULL, 
			data LONGTEXT DEFAULT NULL, 
			PRIMARY KEY  (id), 
			UNIQUE KEY id (id)"; 

		// Insert table
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( "CREATE TABLE IF NOT EXISTS {$table} ( " . $sql . " ) $collate;" );
		if ( is_multisite() )
			add_blog_option( $GLOBALS['blog_id'], 'bwlmscredit_version_db', '1.0' );
		else
			add_option( 'bwlmscredit_version_db', '1.0' );

		return true;
	}
endif;

if ( ! function_exists( 'bwlmscredit_create_token' ) ) :
	function bwlmscredit_create_token( $string = '' )
	{
		if ( is_array( $string ) )
			$string = implode( ':', $string );

		$protect = bwlmscredit_protect();
		if ( $protect !== false )
			$encoded = $protect->do_encode( $string );
		else
			$encoded = $string;

		return apply_filters( 'bwlmscredit_create_token', $encoded, $string );

	}
endif;

if ( ! function_exists( 'bwlmscredit_verify_token' ) ) :
	function bwlmscredit_verify_token( $string = '', $length = 1 )
	{
		$reply = false;

		$protect = bwlmscredit_protect();
		if ( $protect !== false ) {
			$decoded = $protect->do_decode( $string );
			$array = explode( ':', $decoded );
			if ( count( $array ) == $length )
				$reply = $array;
		}
		else {
			$reply = $string;
		}

		return apply_filters( 'bwlmscredit_verify_token', $reply, $string, $length );

	}
endif;

if ( ! function_exists( 'bwlmscredit_translate_limit_code' ) ) :
	function bwlmscredit_translate_limit_code( $code = '' ) {

		if ( $code == '' ) return '-';

		if ( $code == '0/x' || $code == 0 )
			return __( 'No limit', 'wptobemem' );

		$result = '-';
		$check = explode( '/', $code );
		if ( count( $check ) == 2 ) {
			if ( $check[1] == 'd' )
				$per = __( 'per day', 'wptobemem' );
			elseif ( $check[1] == 'w' )
				$per = __( 'per week', 'wptobemem' );
			elseif ( $check[1] == 'm' )
				$per = __( 'per month', 'wptobemem' );
			else
				$per = __( 'in total', 'wptobemem' );

			$result = sprintf( _n( 'Maximum once', 'Maximum %d times', $check[0], 'wptobemem' ), $check[0] ) . ' ' . $per;

		}
		elseif ( is_numeric( $code ) ) {
			$result = sprintf( _n( 'Maximum once', 'Maximum %d times', $code, 'wptobemem' ), $code );
		}

		return apply_filters( 'bwlmscredit_translate_limit_code', $result, $code );

	}
endif;

?>