<?php

if ( ! class_exists( 'bwlmsCREDIT_Network_Module' ) ) {
	class bwlmsCREDIT_Network_Module {

		public $core;
		public $plug;

		function __construct() {
			global $bwlmscredit_network;
			$this->core = bwlmscredit();
		}

		public function load() {
			add_action( 'admin_init',         array( $this, 'module_admin_init' ) );
			add_action( 'admin_head',         array( $this, 'admin_menu_styling' ) );
			add_action( 'network_admin_menu', array( $this, 'add_menu' ) );
		}

		public function module_admin_init() {
			register_setting( 'bwlmscredit_network', 'bwlmscredit_network', array( $this, 'save_network_prefs' ) );
		}

		public function add_menu() {
			$pages[] = add_menu_page(
				__( 'bwlmsCREDIT', 'wptobemem' ),
				__( 'bwlmsCREDIT', 'wptobemem' ),
				'manage_network_options',
				'bwlmsCREDIT_Network',
				'',
				'dashicons-star-filled'
			);
			$pages[] = add_submenu_page(
				'bwlmsCREDIT_Network',
				__( 'Network Settings', 'wptobemem' ),
				__( 'Network Settings', 'wptobemem' ),
				'manage_network_options',
				'bwlmsCREDIT_Network',
				array( $this, 'admin_page_settings' )
			);

			foreach ( $pages as $page )
				add_action( 'admin_print_styles-' . $page, array( $this, 'admin_menu_styling' ) );
		}

		public function admin_menu_styling() {
			global $wp_version;

			wp_enqueue_style( 'bwlmscredit-admin' );

			?>
				<style type="text/css">
				h4:before { float:right; padding-right: 12px; font-size: 14px; font-weight: normal; color: silver; }
				h4.ui-accordion-header.ui-state-active:before { content: "<?php _e( 'click to close', 'wptobemem' ); ?>"; }
				h4.ui-accordion-header:before { content: "<?php _e( 'click to open', 'wptobemem' ); ?>"; }
				</style>
			<?php
		}

		public function admin_print_styles() {
			if ( ! wp_style_is( 'bwlmscredit-admin', 'registered' ) ) {

				wp_register_style(
					'bwlmscredit-admin',
					BWLMSMEM_URL . '/css/bwlms-credit-admin.css',
					false
				);
			}
			wp_enqueue_style( 'bwlmscredit-admin' );

			if ( ! wp_script_is( 'bwlmscredit-admin', 'registered' ) ) {
				wp_localize_script( 'bwlmscredit-admin', 'bwlmsCREDIT', apply_filters( 'bwlmscredit_localize_admin', array( 'active' => '-1' ) ) );
			}
			wp_enqueue_script( 'bwlmscredit-admin' );
		}

		public function admin_page_settings() {
			if ( ! current_user_can( 'manage_network_options' ) ) wp_die( __( 'Access Denied', 'wptobemem' ) );

			global $bwlmscredit_network;

			$prefs = bwlmscredit_get_settings_network();
			$name = bwlmscredit_label(); ?>

	<div class="wrap" id="bwlmsCREDIT-wrap">
		<div id="icon-bwlmsCREDIT" class="icon32"><br /></div>
		<h2> <?php echo sprintf( __( '%s Network', 'wptobemem' ), $name ); ?></h2>
		<?php
			
			// Inform user that bwlmsCREDIT has not yet been setup
			$setup = get_blog_option( 1, 'bwlmscredit_setup_completed', false );
			if ( $setup === false )
				echo '<div class="error"><p>' . sprintf( __( 'Note! %s has not yet been setup.', 'wptobemem' ), $name ) . '</p></div>';

			// Settings Updated
			if ( isset( $_GET['settings-updated'] ) )
				echo '<div class="updated"><p>' . __( 'Network Settings Updated', 'wptobemem' ) . '</p></div>'; ?>

<p><?php echo sprintf( __( 'Configure network settings for %s.', 'wptobemem' ), $name ); ?></p>
<form method="post" action="<?php echo admin_url( 'options.php' ); ?>" class="">
	<?php settings_fields( 'bwlmscredit_network' ); ?>

	<div class="list-items expandable-li" id="accordion">
		<h4><div class="icon icon-inactive core"></div><?php _e( 'Settings', 'wptobemem' ); ?></h4>
		<div class="body" style="display:block;">
			<label class="subheader"><?php _e( 'Master Template', 'wptobemem' ); ?></label>
			<ol id="bwlmsCREDIT-network-settings-enabling">
				<li>
					<input type="radio" name="bwlmscredit_network[master]" id="bwlmsCREDIT-network-overwrite-enabled" <?php checked( $prefs['master'], 1 ); ?> value="1" /> 
					<label for="bwlmsCREDIT-network-"><?php _e( 'Yes', 'wptobemem' ); ?></label>
				</li>
				<li>
					<input type="radio" name="bwlmscredit_network[master]" id="bwlmsCREDIT-network-overwrite-disabled" <?php checked( $prefs['master'], 0 ); ?> value="0" /> 
					<label for="bwlmsCREDIT-network-"><?php _e( 'No', 'wptobemem' ); ?></label>
				</li>
				<li>
					<p class="description"><?php echo sprintf( __( "If enabled, %s will use your main site's settings for all other sites in your network.", 'wptobemem' ), $name ); ?></p>
				</li>
			</ol>
			<label class="subheader"><?php _e( 'Central Logging', 'wptobemem' ); ?></label>
			<ol id="bwlmsCREDIT-network-log-enabling">
				<li>
					<input type="radio" name="bwlmscredit_network[central]" id="bwlmsCREDIT-network-overwrite-log-enabled" <?php checked( $prefs['central'], 1 ); ?> value="1" /> 
					<label for="bwlmsCREDIT-network-"><?php _e( 'Yes', 'wptobemem' ); ?></label>
				</li>
				<li>
					<input type="radio" name="bwlmscredit_network[central]" id="bwlmsCREDIT-network-overwrite-log-disabled" <?php checked( $prefs['central'], 0 ); ?> value="0" /> 
					<label for="bwlmsCREDIT-network-"><?php _e( 'No', 'wptobemem' ); ?></label>
				</li>
				<li>
					<p class="description"><?php echo sprintf( __( "If enabled, %s will log all site actions in your main site's log.", 'wptobemem' ), $name ); ?></p>
				</li>
			</ol>
			<label class="subheader"><?php _e( 'Site Block', 'wptobemem' ); ?></label>
			<ol id="bwlmsCREDIT-network-site-blocks">
				<li>
					<div class="h2"><input type="text" name="bwlmscredit_network[block]" id="bwlmsCREDIT-network-block" value="<?php echo $prefs['block']; ?>" class="long" /></div>
					<span class="description"><?php echo sprintf( __( 'Comma separated list of blog ids where %s is to be disabled.', 'wptobemem' ), $name ); ?></span>
				</li>
			</ol>
			<?php do_action( 'bwlmscredit_network_prefs', $this ); ?>

		</div>
		<?php do_action( 'bwlmscredit_after_network_prefs', $this ); ?>

	</div>
	<p><?php submit_button( __( 'Save Network Settings', 'wptobemem' ), 'primary large', 'submit', false ); ?></p>
</form>	
<?php do_action( 'bwlmscredit_bottom_network_page', $this ); ?>

</div>
<?php
		}

		public function save_network_prefs( $settings ) {

			$new_settings = array();
			$new_settings['master'] = ( isset( $settings['master'] ) ) ? $settings['master'] : 0;
			$new_settings['central'] = ( isset( $settings['central'] ) ) ? $settings['central'] : 0;
			$new_settings['block'] = sanitize_text_field( $settings['block'] );

			$new_settings = apply_filters( 'bwlmscredit_save_network_prefs', $new_settings, $settings, $this->core );

			return $new_settings;
		}
	}
}
?>