<?php

if ( ! class_exists( 'bwlmsCREDIT_Module' ) ) {
	abstract class bwlmsCREDIT_Module {

		public $module_id;
		public $core;
		public $module_name;
		public $option_id;
		public $labels;
		public $register;
		public $screen_id;
		public $menu_pos;
		public $is_main_type = true;
		public $default_prefs = array();
		public $bwlmscredit_type;
		public $point_types;
		public $current_user_id;
		public $pages = array();

		function __construct( $module_id = '', $args = array(), $type = 'bwlmscredit_default' ) {
			if ( empty( $module_id ) )
				wp_die( __( 'bwlmsCREDIT_Module() Error. A Module ID is required!', 'wptobemem' ) );

			$this->module_id = $module_id;
			$this->core = bwlmscredit( $type );

			if ( ! empty( $type ) ) {
				$this->core->credit_id = sanitize_text_field( $type );
				$this->bwlmscredit_type = $this->core->credit_id;
			}
			
			if ( $this->bwlmscredit_type != 'bwlmscredit_default' )
				$this->is_main_type = false;

			$this->point_types = bwlmscredit_get_types();

			$defaults = array(
				'module_name' => '',
				'option_id'   => '',
				'defaults'    => array(),
				'labels'      => array(
					'menu'        => '',
					'page_title'  => ''
				),
				'register'    => true,
				'screen_id'   => '',
				'add_to_core' => false,
				'accordion'   => false,
				'cap'         => 'plugin',
				'menu_pos'    => 10
			);
			$args = wp_parse_args( $args, $defaults );

			$this->module_name = $args['module_name'];
			$this->option_id = $args['option_id'];
			
			if ( ! $this->is_main_type )
				$this->option_id .= '_' . $this->bwlmscredit_type;
			
			$this->settings_name = 'bwlmsCREDIT-' . $this->module_name;
			if ( ! $this->is_main_type )
				$this->settings_name .= '-' . $this->bwlmscredit_type;
			
			$this->labels = $args['labels'];
			$this->register = $args['register'];
			$this->screen_id = $args['screen_id'];
			
			if ( ! $this->is_main_type && ! empty( $this->screen_id ) )
				$this->screen_id = 'bwlmsCREDIT_' . $this->bwlmscredit_type . substr( $this->screen_id, 6 );

			$this->add_to_core = $args['add_to_core'];
			$this->accordion = $args['accordion'];
			$this->cap = $args['cap'];
			$this->menu_pos = $args['menu_pos'];

			$this->default_prefs = $args['defaults'];
			$this->set_settings();
			$this->current_user_id = get_current_user_id();

			$args = NULL;
		}

		function set_settings() {
			$module = $this->module_name;

			if ( $this->register === false ) {
				if ( ! isset( $this->core->$module ) )
					$this->$module = $this->default_prefs;
				else
					$this->$module = $this->core->$module;

				if ( ! empty( $defaults ) )
					$this->$module = bwlmscredit_apply_defaults( $this->default_prefs, $this->$module );
			}

			else {
				if ( ! empty( $this->option_id ) ) {
					if ( is_array( $this->option_id ) ) {
						$pattern = 'bwlmscredit_pref_core';
							$this->$module = $this->core;

						foreach ( $this->option_id as $option_id => $option_name ) {
							$settings = bwlmscredit_get_option( $option_name, false );

							if ( $settings === false && array_key_exists( $option_id, $defaults ) )
								$this->$module[ $option_name ] = $this->default_prefs[ $option_id ];
							else
								$this->$module[ $option_name ] = $settings;

							if ( array_key_exists( $option_id, $defaults ) )
								$this->$module[ $option_name ] = bwlmscredit_apply_defaults( $this->default_prefs[ $option_id ], $this->$module[ $option_name ] );
						}
					}

					else {
						if ( str_replace( 'bwlmscredit_pref_core', '', $this->option_id ) == '' )
							$this->$module = $this->core;

						else {
							$this->$module = bwlmscredit_get_option( $this->option_id, false );

							if ( $this->$module === false && ! empty( $this->default_prefs ) )
								$this->$module = $this->default_prefs;

							if ( ! empty( $this->default_prefs ) )
								$this->$module = bwlmscredit_apply_defaults( $this->default_prefs, $this->$module );
						}
					}

					if ( is_array( $this->$module ) ) {
						foreach ( $this->$module as $key => $value ) {
							$this->$key = $value;
						}
					}
				}
			}
		}

		function load() {
			if ( ! empty( $this->screen_id ) && ! empty( $this->labels['menu'] ) ) {
				add_action( 'bwlmscredit_add_menu',         array( $this, 'add_menu' ), $this->menu_pos );
				add_action( 'admin_init',              array( $this, 'set_entries_per_page' ) );
			}

			if ( $this->register === true && ! empty( $this->option_id ) )
				add_action( 'bwlmscredit_admin_init',       array( $this, 'register_settings' ) );

			if ( $this->add_to_core === true ) {
				add_action( 'bwlmscredit_after_core_prefs', array( $this, 'after_general_settings' ) );
				add_filter( 'bwlmscredit_save_core_prefs',  array( $this, 'sanitize_extra_settings' ), 90, 3 );
			}

			add_action( 'bwlmscredit_pre_init',             array( $this, 'module_pre_init' ) );
			add_action( 'bwlmscredit_init',                 array( $this, 'module_init' ) );
			add_action( 'bwlmscredit_admin_init',           array( $this, 'module_admin_init' ) );
			add_action( 'bwlmscredit_widgets_init',         array( $this, 'module_widgets_init' ) );
		}

		function module_ready() { }

		function module_pre_init() { }

		function module_init() { }

		function module_admin_init() { }

		function module_widgets_init() { }

		function get() { }

		function call( $call, $callback, $return = NULL ) {
			if ( is_array( $callback ) && class_exists( $callback[0] ) ) {
				$class = $callback[0];
				$methods = get_class_methods( $class );
				if ( in_array( $call, $methods ) ) {
					$new = new $class( $this );
					return $new->$call( $return );
				}
			}

			elseif ( ! is_array( $callback ) ) {
				if ( function_exists( $callback ) ) {
					if ( $return !== NULL )
						return call_user_func( $callback, $return, $this );
					else
						return call_user_func( $callback, $this );
				}
			}
			
			return array();
		}

		function is_installed() {
			$module_name = $this->module_name;
			if ( $this->$module_name === false ) return false;
			return true;
		}

		function is_active( $key = '' ) {
			$module = $this->module_name;
			if ( ! isset( $this->active ) && ! empty( $key ) ) {
				if ( isset( $this->$module['active'] ) )
					$active = $this->$module['active'];
				else
					return false;

				if ( in_array( $key, $active ) ) return true;
			}
			elseif ( isset( $this->active ) && ! empty( $key ) ) {
				if ( in_array( $key, $this->active ) ) return true;
			}

			return false;
		}


		function add_menu() {
			if ( bwlmscredit_override_settings() && $GLOBALS['blog_id'] > 1 && substr( $this->screen_id, 0, 6 ) == 'bwlmsCREDIT' && strlen( $this->screen_id ) > 6 ) return;

			if ( ! empty( $this->labels ) && ! empty( $this->screen_id ) ) {
				$menu_slug = 'bwlmsCREDIT';
				if ( ! $this->is_main_type )
					$menu_slug = 'bwlmsCREDIT_' . $this->bwlmscredit_type;

				if ( ! isset( $this->labels['page_title'] ) && ! isset( $this->labels['menu'] ) )
					$label_menu = __( 'Surprise', 'wptobemem' );
				elseif ( isset( $this->labels['menu'] ) )
					$label_menu = $this->labels['menu'];
				else
					$label_menu = $this->labels['page_title'];

				if ( ! isset( $this->labels['page_title'] ) && ! isset( $this->labels['menu'] ) )
					$label_title = __( 'Surprise', 'wptobemem' );
				elseif ( isset( $this->labels['page_title'] ) )
					$label_title = $this->labels['page_title'];
				else
					$label_title = $this->labels['menu'];

				if ( $this->cap != 'plugin' )
					$cap = $this->core->edit_credits_cap();
				else
					$cap = $this->core->edit_plugin_cap();

				$page = add_submenu_page(
					$menu_slug,
					$label_menu,
					$label_title,
					$cap,
					$this->screen_id,
					array( $this, 'admin_page' )
				);
				add_action( 'admin_print_styles-' . $page, array( $this, 'settings_page_enqueue' ) );
				add_action( 'load-' . $page,               array( $this, 'screen_options' ) );
			}
		}

		function set_entries_per_page() {
			if ( ! isset( $_REQUEST['wp_screen_options']['option'] ) || ! isset( $_REQUEST['wp_screen_options']['value'] ) ) return;
			
			$getpage_name = sanitize_text_field($_GET['page']);
			$settings_key = 'bwlmscredit_epp_' . $getpage_name;

			if ( ! $this->is_main_type )
				$settings_key .= '_' . $this->bwlmscredit_type;

			if ( $_REQUEST['wp_screen_options']['option'] == $settings_key ) {
				$value = absint( $_REQUEST['wp_screen_options']['value'] );
				bwlmscredit_update_user_meta( get_current_user_id(), $settings_key, '', $value );
			}
		}

		function register_settings() {
			if ( empty( $this->option_id ) || $this->register === false ) return;

			register_setting( $this->settings_name, $this->option_id, array( $this, 'sanitize_settings' ) );
		}

		function screen_options() {
			$this->set_entries_per_page();
		}

		function settings_page_enqueue() {
			wp_dequeue_script( 'bpge_admin_js_acc' );

			if ( $this->accordion ) {
				wp_enqueue_script( 'bwlmscredit-admin' );
				wp_enqueue_style( 'bwlmscredit-admin' );
				
				$open = '-1';		
				if ( isset( $_GET['open-tab'] ) && is_numeric( $_GET['open-tab'] ) )
					$open = absint( $_GET['open-tab'] );

				wp_localize_script( 'bwlmscredit-admin', 'bwlmsCREDIT', apply_filters( 'bwlmscredit_localize_admin', array( 'active' => $open ) ) ); 
			}
			
			$this->settings_header();
		}


		function settings_header() {}

		function admin_page() {}

		function update_notice( $get = 'settings-updated', $class = 'updated', $message = '' ) {
			
			if ( empty( $message ) )
				$message = __( 'Settings Updated', 'wptobemem' );
			
			if ( isset( $_GET[ $get ] ) )
				echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
		}

		function sanitize_settings( $post ) {
			return $post;
		}

		function after_general_settings( $bwlmscredit ) { }

		function sanitize_extra_settings( $new_data, $data, $core ) {
			return $new_data;
		}

		function field_name( $name = '' ) {
			if ( is_array( $name ) ) {
				$array = array();
				foreach ( $name as $parent => $child ) {
					if ( ! is_numeric( $parent ) )
						$array[] = $parent;

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = $child;
				}
				$name = '[' . implode( '][', $array ) . ']';
			}
			else {
				$name = '[' . $name . ']';
			}

			if ( $this->add_to_core === true )
				$name = '[' . $this->module_name . ']' . $name;

			if ( $this->option_id != '' )
				return $this->option_id . $name;
			else
				return 'bwlmscredit_pref_core' . $name;
		}

		function field_id( $id = '' ) {
			if ( is_array( $id ) ) {
				$array = array();
				foreach ( $id as $parent => $child ) {
					if ( ! is_numeric( $parent ) )
						$array[] = str_replace( '_', '-', $parent );

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = str_replace( '_', '-', $child );
				}
				$id = implode( '-', $array );
			}
			else {
				$id = str_replace( '_', '-', $id );
			}

			if ( $this->add_to_core === true )
				$id = $this->module_name . '-' . $id;

			return str_replace( '_', '-', $this->module_id ) . '-' . $id;
		}

//		function available_template_tags( $available = array() ) {
//			return $this->core->available_template_tags( $available );
//		}

		function get_settings_url( $module = '' ) {
			$variables = array( 'page' => 'bwlmsCREDIT_page_settings' );
			if ( ! empty( $module ) )
				$variables['open-tab'] = $module;
			
			return add_query_arg( $variables, admin_url( 'admin.php' ) );
		}

		public function request_to_entry( $request ) {

			$entry = new stdClass();

			$entry->id      = -1;
			$entry->ref     = $request['ref'];
			$entry->ref_id  = $request['ref_id'];
			$entry->user_id = $request['user_id'];
			$entry->time    = current_time( 'timestamp' );
			$entry->entry   = $request['entry'];
			$entry->data    = $request['data'];
			$entry->ctype   = $request['type'];

			return $entry;
		}
	}
}