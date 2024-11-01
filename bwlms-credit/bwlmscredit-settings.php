<?php

if ( ! class_exists( 'bwlmsCREDIT_Hooks_Module' ) ) :
	class bwlmsCREDIT_Hooks_Module extends bwlmsCREDIT_Module {

		function __construct( $type = 'bwlmscredit_default' ) {

			parent::__construct( 'bwlmsCREDIT_Hooks_Module', array(
				'module_name' => 'hooks',
				'option_id'   => 'bwlmscredit_pref_hooks',
				'defaults'    => array(
					'installed'   => array(),
					'active'      => array(),
					'hook_prefs'  => array()
				),
				'labels'      => array(
					'menu'        => __( 'Points', 'wptobemem' ),
					'page_title'  => __( 'Points', 'wptobemem' ),
					'page_header' => __( 'Points', 'wptobemem' )
				),
				'screen_id'   => 'bwlmsCREDIT'
			), $type );

		}

		public function module_init() {

			if ( ! empty( $this->installed ) ) {

				foreach ( $this->installed as $key => $gdata ) {

					if ( $this->is_active( $key ) && isset( $gdata['callback'] ) ) {
						$this->call( 'run', $gdata['callback'] );
					}

				}

			}

		}

		public function call( $call, $callback, $return = NULL ) {

			if ( is_array( $callback ) && class_exists( $callback[0] ) ) {

				$class = $callback[0];
				
				$methods = get_class_methods( $class );
				if ( in_array( $call, $methods ) ) {

					$new = new $class( ( isset( $this->hook_prefs ) ) ? $this->hook_prefs : array(), $this->bwlmscredit_type );
					return $new->$call( $return );
				}

			}

			elseif ( ! is_array( $callback ) ) {

				if ( function_exists( $callback ) ) {

					if ( $return !== NULL ) {
						return call_user_func( $callback, $return, $this );
					}
					else {
						return call_user_func( $callback, $this );
					}

				}

			}

		}

		public function get( $save = false ) {

			$installed = array();

			$installed['registration'] = array(
				'title'        => __( 'Registrations', 'wptobemem' ),
				'description'  => __( 'Points for users joining website.', 'wptobemem' ),
				'callback'     => array( 'bwlmsCREDIT_Hook_Registration' )
			);

			// Site Visits
			$installed['site_visit'] = array(
				'title'        => __( 'Daily visits', 'wptobemem' ),
				'description'  => __( 'Points for users visiting website on a daily basis.', 'wptobemem' ),
				'callback'     => array( 'bwlmsCREDIT_Hook_Site_Visits' )
			);

			// Logins
			$installed['logging_in'] = array(
				'title'       => __( 'Logins', 'wptobemem' ),
				'description' => __( 'Points for logging in to website.', 'wptobemem' ),
				'callback'    => array( 'bwlmsCREDIT_Hook_Logging_In' )
			);

			$installed = apply_filters( 'bwlmscredit_setup_hooks', $installed, $this->bwlmscredit_type );

			if ( $save === true && $this->core->can_edit_plugin() ) {
				$new_data = array(
					'active'     => $this->active,
					'installed'  => $installed,
					'hook_prefs' => $this->hook_prefs
				);
				bwlmscredit_update_option( $this->option_id, $new_data );
			}

			$this->installed = $installed;
			return $installed;

		}


		public function admin_page() {

			// Security
			if ( ! $this->core->can_edit_credits() )
				wp_die( __( 'Access Denied', 'wptobemem' ) );

			$installed = $this->get();
?>

	<form method="post" action="options.php" name="bwlmscredit-hooks-setup-form" novalidate>

		<?php submit_button( __( 'Save Changes', 'wptobemem' ), 'primary', 'submit', false ); ?>

		<div class="bwlmscredit_admin_setting_wrapper">
			<div class="row fullwidthrow_credit_admin bwlmscredit-settingtitle-row">
				<div class="small-3 large-3 columns">&nbsp; </div>
				<div class="small-1 large-1 columns">&nbsp; </div>
				<div class="small-8 large-8 columns"> 
					<div class="row">
						<div class="small-2 large-2 columns bwlmslcredit-field-title"> <?php _e('POINTS','wptobemem');?></div>
						<div class="small-2 large-2 columns "> &nbsp; </div>
						<div class="small-4 large-4 columns bwlmslcredit-field-title"> <?php _e('LIMITS','wptobemem');?> </div>
						<div class="small-4 large-4 columns ">  </div>
					</div>
				</div>
			</div>

			
				<?php settings_fields( $this->settings_name ); ?> 
				<?php
					// If we have hooks
					if ( ! empty( $installed ) ) {

						// Loop though them
						foreach ( $installed as $key => $data ) { ?>
						<div class="row fullwidthrow_credit_admin bwlmscredit_admin_content_row">
							<div class="small-3 large-3 columns"> 
								<label><?php echo $this->core->template_tags_general( $data['title'] ); ?></label>
							</div>

							<div class="small-1 large-1 columns">
								<input type="checkbox" name="<?php echo $this->option_id; ?>[active][]" id="bwlmscredit-hook-<?php echo $key; ?>" value="<?php echo $key; ?>"<?php if ( $this->is_active( $key ) ) echo ' checked="checked"'; ?> />
							</div>

							<div class="small-8 large-8 columns">
								<?php echo $this->call( 'preferences', $data['callback'] ); ?>
							</div>
						</div><!--fullwidthrow_credit_admin-->
							<?php
						}
					} ?>
					
			
		</div><!--wrapper-->

	</form>


			<?php

		}

		public function sanitize_settings( $post ) {

			// Loop though all installed hooks
			$installed = $this->get();

			// Construct new settings
			$new_post['installed'] = $installed;
			if ( empty( $post['active'] ) || ! isset( $post['active'] ) )
				$post['active'] = array();

			$new_post['active'] = $post['active'];

			if ( ! empty( $installed ) ) {

				foreach ( $installed as $key => $data ) {

					if ( isset( $data['callback'] ) && isset( $post['hook_prefs'][ $key ] ) ) {

						// Old settings
						$old_settings = $post['hook_prefs'][ $key ];

						// New settings
						$new_settings = $this->call( 'sanitise_preferences', $data['callback'], $old_settings );

						// If something went wrong use the old settings
						if ( empty( $new_settings ) || $new_settings === NULL || ! is_array( $new_settings ) )
							$new_post['hook_prefs'][ $key ] = $old_settings;

						// Else we got ourselves new settings
						else
							$new_post['hook_prefs'][ $key ] = $new_settings;

						// Handle de-activation
						if ( ! isset( $this->active ) ) continue;

						if ( in_array( $key, (array) $this->active ) && ! in_array( $key, $new_post['active'] ) )
							$this->call( 'deactivate', $data['callback'], $new_post['hook_prefs'][ $key ] );

						// Next item

					}

				}

			}

			$installed = NULL;
			return $new_post;

		}

	}
endif;


require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-hook-class.php");
if ( ! class_exists( 'bwlmsCREDIT_Hook_Registration' ) ) :
	class bwlmsCREDIT_Hook_Registration extends bwlmsCREDIT_Hook {

		function __construct( $hook_prefs, $type = 'bwlmscredit_default' ) {

			parent::__construct( array(
				'id'       => 'registration',
				'defaults' => array(
					'credits'   => 10,
					'log'     => '%plural% for becoming a member'
				)
			), $hook_prefs, $type );

		}

		public function run() {
			add_action( 'user_register', array( $this, 'registration' ) );
		}

		public function verified_signup( $user_id ) {

			$this->registration( $user_id );

		}

		public function registration( $user_id ) {

			if ( $this->core->exclude_user( $user_id ) === true ) return;

			$data = array( 'ref_type' => 'user' );

			if ( $this->core->has_entry( 'registration', $user_id, $user_id, $data, $this->bwlmscredit_type ) ) return;

			$this->core->add_credits(
				'registration',
				$user_id,
				$this->prefs['credits'],
				$this->prefs['log'],
				$user_id,
				$data,
				$this->bwlmscredit_type
			);

		}

		public function preferences() {

			$prefs = $this->prefs;

?>

	<div class="row">
		<div class="small-2 medium-2 large-2 columns">
			<input 
				type="text" 
				name="<?php echo $this->field_name( 'credits' ); ?>" 
				id="<?php echo $this->field_id( 'credits' ); ?>" 
				value="<?php echo $this->core->number( $prefs['credits'] ); ?>" 
				size="8" 
				class="bwlmspointtxtbox"
			/>
			<input 
				type="hidden" 
				name="<?php echo $this->field_name( 'log' ); ?>" 
				id="<?php echo $this->field_id( 'log' ); ?>" 
				value="<?php echo esc_attr( $prefs['log'] ); ?>" 
				class="long" 
			/>
		</div>
		<div class="small-2 medium-2 large-2 columns"></div>
		<div class="small-4 medium-4 large-4 columns"></div>
		<div class="small-4 medium-4 large-4 columns"></div>
	</div>

<?php

		}

	}
endif;

if ( ! class_exists( 'bwlmsCREDIT_Hook_Site_Visits' ) ) :
	class bwlmsCREDIT_Hook_Site_Visits extends bwlmsCREDIT_Hook {

		function __construct( $hook_prefs, $type = 'bwlmscredit_default' ) {

			parent::__construct( array(
				'id'       => 'site_visit',
				'defaults' => array(
					'credits'   => 1,
					'log'     => '%plural% for site visit'
				)
			), $hook_prefs, $type );

		}

		public function run() {

			if ( is_user_logged_in() && ! isset( $_COOKIE['bwlmscredit_site_visit'] ) )
				add_action( 'wp_head', array( $this, 'site_visit' ) );
		}

		public function site_visit() {

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;

			$user_id = get_current_user_id();

			$lifespan = (int) ( 24*3600 ) - ( date_i18n( 'H' ) * 3600 + date_i18n( 'i' ) * 60 + date_i18n( 's' ) );
			if ( ! headers_sent() ) setcookie( 'bwlmscredit_site_visit', 1, $lifespan, '/' );

			if ( $this->core->exclude_user( $user_id ) ) return;

			$today = (int) apply_filters( 'bwlmscredit_site_visit_id', date_i18n( 'Ynj' ) );
			$data = '';

			if ( $this->core->has_entry( 'site_visit', $today, $user_id, $data, $this->bwlmscredit_type ) ) return;

			$this->core->add_credits(
				'site_visit',
				$user_id,
				$this->prefs['credits'],
				$this->prefs['log'],
				$today,
				$data,
				$this->bwlmscredit_type
			);

		}

		public function preferences() {

			$prefs = $this->prefs;

?>

		<div class="row">
			<div class="small-2 medium-2 large-2 columns">
				<input
					type="text" 
					name="<?php echo $this->field_name( 'credits' ); ?>" 
					id="<?php echo $this->field_id( 'credits' ); ?>" 
					value="<?php echo $this->core->number( $prefs['credits'] ); ?>" 
					size="8" 
					class="bwlmspointtxtbox"
				/>
				<input 
					type="hidden" 
					name="<?php echo $this->field_name( 'log' ); ?>" 
					id="<?php echo $this->field_id( 'log' ); ?>" 
					value="<?php echo esc_attr( $prefs['log'] ); ?>" 
					class="long" 
				/>
			</div>
			<div class="small-2 medium-2 large-2 columns"></div>
			<div class="small-4 medium-4 large-4 columns"></div>
			<div class="small-4 medium-4 large-4 columns"></div>
		</div>

<?php

		}

	}
endif;



if ( ! class_exists( 'bwlmsCREDIT_Hook_Logging_In' ) ) :
	class bwlmsCREDIT_Hook_Logging_In extends bwlmsCREDIT_Hook {

		function __construct( $hook_prefs, $type = 'bwlmscredit_default' ) {

			parent::__construct( array(
				'id'       => 'logging_in',
				'defaults' => array(
					'credits'   => 1,
					'log'     => '%plural% for logging in',
					'limit'   => '1/d'
				)
			), $hook_prefs, $type );

		}

		public function run() {

			if ( function_exists( 'sc_social_connect_process_login' ) )
				add_action( 'social_connect_login', array( $this, 'social_login' ) );

			add_action( 'wp_login', array( $this, 'logging_in' ), 10, 2 );

		}

		public function social_login( $user_login = 0 ) {

			$user = get_user_by( 'login', $user_login );
			if ( ! isset( $user->ID ) ) {
				$user = get_user_by( 'email', $user_login );
				if ( ! is_object( $user ) ) return;
			}

			if ( $this->core->exclude_user( $user->ID ) === true ) return;

			if ( ! $this->over_hook_limit( '', 'logging_in', $user->ID ) )
				$this->core->add_credits(
					'logging_in',
					$user->ID,
					$this->prefs['credits'],
					$this->prefs['log'],
					0,
					'',
					$this->bwlmscredit_type
				);

		}

		public function logging_in( $user_login, $user = '' ) {

			if ( ! is_object( $user ) ) {

				$user = get_user_by( 'login', $user_login );
				if ( ! is_object( $user ) ) {

					$user = get_user_by( 'email', $user_login );
					if ( ! is_object( $user ) ) return;
				}
			}

			if ( $this->core->exclude_user( $user->ID ) ) return;

			if ( ! $this->over_hook_limit( '', 'logging_in', $user->ID ) )
				$this->core->add_credits(
					'logging_in',
					$user->ID,
					$this->prefs['credits'],
					$this->prefs['log'],
					0,
					'',
					$this->bwlmscredit_type
				);
		}

		public function preferences() {

			$prefs = $this->prefs;

?>

	<div class="row">
		<div class="small-2 medium-2 large-2 columns">
			<input 
				type="text" 
				name="<?php echo $this->field_name( 'credits' ); ?>" 
				id="<?php echo $this->field_id( 'credits' ); ?>" 
				value="<?php echo $this->core->number( $prefs['credits'] ); ?>" 
				size="8" 
				class="bwlmspointtxtbox"
			/>

			<input 
				type="hidden" 
				name="<?php echo $this->field_name( 'log' ); ?>" 
				id="<?php echo $this->field_id( 'log' ); ?>" 
				value="<?php echo esc_attr( $prefs['log'] ); ?>" 
				class="long" 
			/>
		</div>
		<div class="small-2 medium-2 large-2 columns"> &nbsp;	</div>

		<div class="small-4 medium-4 large-4 columns">
			<?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ); ?>
		</div>
		<div class="small-4 medium-4 large-4 columns"></div>
	</div>



<?php
		}


		function sanitise_preferences( $data ) {

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $data['limit_by'] );
			}

			return $data;

		}

	}
endif;

