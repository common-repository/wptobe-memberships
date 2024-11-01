<?php
if ( ! class_exists( 'bwlmsCREDIT_Hook' ) ) {
	abstract class bwlmsCREDIT_Hook {

		public $id;

		public $core;

		public $point_types;

		public $is_main_type = true;
		public $bwlmscredit_type = 'bwlmscredit_default';

		public $prefs = false;

		function __construct( $args = array(), $hook_prefs = NULL, $type = 'bwlmscredit_default' ) {
			if ( ! empty( $args ) ) {
				foreach ( $args as $key => $value ) {
					$this->$key = $value;
				}
			}

			$this->core = bwlmscredit( $type );
			$this->point_types = bwlmscredit_get_types();

			if ( $type != '' ) {
				$this->core->credit_id = sanitize_text_field( $type );
				$this->bwlmscredit_type = $this->core->credit_id;
			}

			if ( $this->bwlmscredit_type != 'bwlmscredit_default' )
				$this->is_main_type = false;

			if ( $hook_prefs !== NULL ) {
				if ( isset( $hook_prefs[ $this->id ] ) )
					$this->prefs = $hook_prefs[ $this->id ];

				if ( ! isset( $this->defaults ) )
					$this->defaults = array();
			}

			if ( ! empty( $this->defaults ) )
				$this->prefs = bwlmscredit_apply_defaults( $this->defaults, $this->prefs );
		}

		function run() {
			wp_die( 'function bwlmsCREDIT_Hook::run() must be over-ridden in a sub-class.' );
		}

	
		function preferences() {
			echo '<p>' . __( 'This Hook has no settings', 'wptobemem' ) . '</p>';
		}


		function sanitise_preferences( $data ) {
			return $data;
		}

		function field_name( $field = '' ) {
			if ( is_array( $field ) ) {
				$array = array();
				foreach ( $field as $parent => $child ) {
					if ( ! is_numeric( $parent ) )
						$array[] = $parent;

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = $child;
				}
				$field = '[' . implode( '][', $array ) . ']';
			}
			else {
				$field = '[' . $field . ']';
			}
			
			$option_id = 'bwlmscredit_pref_hooks';
			if ( ! $this->is_main_type )
				$option_id = $option_id . '_' . $this->bwlmscredit_type;

			return $option_id . '[hook_prefs][' . $this->id . ']' . $field;
		}

		function field_id( $field = '' ) {
			if ( is_array( $field ) ) {
				$array = array();
				foreach ( $field as $parent => $child ) {
					if ( ! is_numeric( $parent ) )
						$array[] = str_replace( '_', '-', $parent );

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = str_replace( '_', '-', $child );
				}
				$field = implode( '-', $array );
			}
			else {
				$field = str_replace( '_', '-', $field );
			}

			$option_id = 'bwlmscredit_pref_hooks';
			if ( ! $this->is_main_type )
				$option_id = $option_id . '_' . $this->bwlmscredit_type;

			$option_id = str_replace( '_', '-', $option_id );
			return $option_id . '-' . str_replace( '_', '-', $this->id ) . '-' . $field;
		}


		function over_hook_limit( $instance = '', $reference = '', $user_id = NULL ) {

			if ( ! isset( $this->prefs[ $instance ] ) && $instance != '' )
				return true;

			if ( isset( $this->prefs[ $instance ]['limit'] ) ) {
				if ( $this->prefs[ $instance ]['limit'] == '0/x' ) return false;
				$prefs = $this->prefs[ $instance ]['limit'];
			}

			elseif ( isset( $this->prefs['limit'] ) ) {
				if ( $this->prefs['limit'] == '0/x' ) return false;
				$prefs = $this->prefs['limit'];
			}

			else {
				return false;
			}

			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			list ( $amount, $period ) = explode( '/', $prefs );
			$amount = (int) $amount;

			global $wpdb;

			$from = '';
			$until = current_time( 'timestamp' );
			if ( $period == 'd' )
				$from = $wpdb->prepare( "AND time BETWEEN %d AND %d", mktime( 0, 0, 0, date( 'n', $until ), date( 'j', $until ), date( 'Y', $until ) ), $until );

			elseif ( $period == 'w' )
				$from = $wpdb->prepare( "AND time BETWEEN %d AND %d", mktime( 0, 0, 0, date( "n", $until ), date( "j", $until ) - date( "N", $until ) + 1 ), $until );

			elseif ( $period == 'm' )
				$from = $wpdb->prepare( "AND time BETWEEN %d AND %d", mktime( 0, 0, 0, date( "n", $until ), 1, date( 'Y', $until ) ), $until );

			$count = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(*) 
				FROM {$this->core->log_table} 
				WHERE user_id = %d 
				AND   ref = %s 
				AND   ctype = %s {$from};", $user_id, $reference, $this->bwlmscredit_type ) );

			if ( $count === NULL ) $count = 0;

			$result = false;
			if ( $period != 'x' && $count >= $amount )
				$result = true;

			return $result;

		}

		function get_limit_types() {

			return apply_filters( 'bwlmscredit_hook_limits', array(
				'x' => __( 'No limit', 'wptobemem' ),
				'd' => __( '/ Day', 'wptobemem' ),
				'w' => __( '/ Week', 'wptobemem' ),
				'm' => __( '/ Month', 'wptobemem' ),
				't' => __( 'in Total', 'wptobemem' )
			), $this );

		}


		function hook_limit_setting( $name = '', $id = '', $selected = '' ) {

			$check = explode( '/', $selected );
			$count = count( $check );

			if ( $count == 0 || ( $count == 1 && $check[0] == 0 ) )
				$selected = array( 0, 'x' );
			elseif ( $count == 1 && $check[0] != '' && is_numeric( $check[0] ) )
				$selected = array( (int) $check[0], 'd' );
			else
				$selected = $check;

			$hide = 'text';
			if ( $selected[1] == 'x' )
				$hide = 'hidden';

			$output = '<div class="row"><div class="small-4 medium-4 large-4 columns">';
			$output .= '<input class="bwlmspointtxtbox" type="' . $hide . '" size="8" class="mini" name="' . $name . '" id="' . $id . '" value="' . $selected[0] . '" />';
			$output .= '</div><div class="small-8  medium-8 large-8  columns">';
			$options = $this->get_limit_types();

			$name = str_replace( '[limit]', '[limit_by]', $name );
			$name = str_replace( '[alimit]', '[alimit_by]', $name );
			$name = apply_filters( 'bwlmscredit_hook_limit_name_by', $name, $this );

			$id = str_replace( 'limit', 'limit-by', $id );
			$id = str_replace( 'alimit', 'alimit-by', $id );
			$id = apply_filters( 'bwlmscredit_hook_limit_id_by', $id, $this );

			$output .= '<select class="bwlmspointtxtbox" name="' . $name . '" id="' . $id . '" class="limit-toggle">';
			foreach ( $options as $value => $label ) {
				$output .= '<option value="' . $value . '"';
				if ( $selected[1] == $value ) $output .= ' selected="selected"';
				$output .= '>' . $label . '</option>';
			}
			$output .= '</select>';
			$output .= '</div></div>';

			return $output;
		}

		function impose_limits_dropdown( $pref_id = '', $use_select = true ) {
			$limits = array(
				''           => __( 'No limit', 'wptobemem' ),
				'twentyfour' => __( 'Once every 24 hours', 'wptobemem' ),
				'sevendays'  => __( 'Once every 7 days', 'wptobemem' ),
				'daily'      => __( 'Once per day (reset at midnight)', 'wptobemem' )
			);
			$limits = apply_filters( 'bwlmscredit_hook_impose_limits', $limits, $this );

			echo '<select name="' . $this->field_name( $pref_id ) . '" id="' . $this->field_id( $pref_id ) . '">';

			if ( $use_select )
				echo '<option value="">' . __( 'Select', 'wptobemem' ) . '</option>';

			$settings = '';
			if ( is_array( $pref_id ) ) {
				reset( $pref_id );
				$key = key( $pref_id );
				$settings = $this->prefs[ $key ][ $pref_id[ $key ] ];
			}
			elseif ( isset( $this->prefs[ $pref_id ] ) ) {
				$settings = $this->prefs[ $pref_id ];
			}

			foreach ( $limits as $value => $description ) {
				echo '<option value="' . $value . '"';
				if ( $settings == $value ) echo ' selected="selected"';
				echo '>' . $description . '</option>';
			}
			echo '</select>';
		}

		function has_entry( $action = '', $ref_id = '', $user_id = '', $data = '', $type = '' ) {
			if ( $type == '' )
				$type = $this->bwlmscredit_type;

			return $this->core->has_entry( $action, $ref_id, $user_id, $data, $type );
		}

//		function available_template_tags( $available = array(), $custom = '' ) {
//			return $this->core->available_template_tags( $available, $custom );
//		}


		public function is_over_daily_limit( $ref = '', $user_id = 0, $max = 0, $ref_id = NULL ) {
			global $wpdb;

			$reply = true;

			$start = date_i18n( 'U', strtotime( 'today midnight' ) );
			$end = date_i18n( 'U' );

			$total = $this->limit_query( $ref, $user_id, $start, $end, $ref_id );

			if ( $total !== NULL && $total < $max )
				$reply = false;

			return apply_filters( 'bwlmscredit_hook_over_daily_limit', $reply, $ref, $user_id, $max );
		}


		public function limit_query( $ref = '', $user_id = 0, $start = 0, $end = 0, $ref_id = NULL ) {
			global $wpdb;

			$reply = true;

			if ( empty( $ref ) || $user_id == 0 || $start == 0 || $end == 0 )
				return NULL;

			$ref = '';
			if ( $ref_id !== NULL )
				$ref = $wpdb->prepare( 'AND ref_id = %d ', $ref_id );

			$total = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT( * ) 
				FROM {$this->core->log_table} 
				WHERE ref = %s {$ref}
					AND user_id = %d 
					AND time BETWEEN %d AND %d;", $ref, $user_id, $start, $end ) );

			return apply_filters( 'bwlmscredit_hook_limit_query', $total, $ref, $user_id, $ref_id, $start, $end );
		}
	}
}
?>