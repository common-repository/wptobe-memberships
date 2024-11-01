<?php

if ( ! class_exists( 'bwlmsCREDIT_Protect' ) && ! defined( 'BWLMSCREDIT_DISABLE_PROTECTION' ) ) :
	class bwlmsCREDIT_Protect {

		public $skey;

		public function __construct( $custom_key = NULL ) {
			if ( $custom_key !== NULL )
				$this->skey = $custom_key;
			else {
				$skey = bwlmscredit_get_option( 'bwlmscredit_key', false );
				if ( $skey === false )
					$skey = $this->reset_key();

				$this->skey = $skey;
			}
		}

		public function reset_key() {
			$skey = wp_generate_password( 16, true, true );
			bwlmscredit_update_option( 'bwlmscredit_key', $skey );
			$this->skey = $skey;
		}

		public function do_encode( $value = NULL ) {
			if ( $value === NULL || empty( $value ) ) return false;

			if ( function_exists( 'mcrypt_encrypt' ) ) {
				$text = $value;
				$iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
				$iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
				$crypttext = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->skey, $text, MCRYPT_MODE_ECB, $iv );
				return trim( $this->do_safe_b64encode( $crypttext ) );
			}

			return $value;
		}

		public function do_decode( $value ) {
			if ( $value === NULL || empty( $value ) ) return false;

			if ( function_exists( 'mcrypt_decrypt' ) ) {
				$crypttext = $this->do_safe_b64decode( $value ); 
				$iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
				$iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
				$decrypttext = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->skey, $crypttext, MCRYPT_MODE_ECB, $iv );
				return trim( $decrypttext );
			}

			return $value;
		}

		protected function do_retrieve( $value ) {
			if ( $value === NULL || empty( $value ) ) return false;

			if ( function_exists( 'mcrypt_decrypt' ) ) {
				$crypttext = $this->do_safe_b64decode( $value ); 
				$iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
				$iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
				$decrypttext = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->skey, $crypttext, MCRYPT_MODE_ECB, $iv );
				$string = trim( $decrypttext );
				parse_str( $string, $output );
				return $output;
			}

			return $value;
		}

		protected function do_safe_b64encode( $string ) {
			$data = base64_encode( $string );
			$data = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $data );
			return $data;
		}

		protected function do_safe_b64decode( $string ) {
			$data = str_replace( array( '-', '_' ), array( '+', '/' ), $string );
			$mod4 = strlen( $data ) % 4;
			if ( $mod4 ) {
				$data .= substr( '====', $mod4 );
			}
			return base64_decode( $data );
		}
	}
endif;

if ( ! function_exists( 'bwlmscredit_protect' ) ) :
	function bwlmscredit_protect()
	{
		if ( ! class_exists( 'bwlmsCREDIT_Protect' ) || defined( 'BWLMSCREDIT_DISABLE_PROTECTION' ) ) return false;

		global $bwlmscredit_protect;

		if ( ! isset( $bwlmscredit_protect ) || ! is_object( $bwlmscredit_protect ) )
			$bwlmscredit_protect = new bwlmsCREDIT_Protect();

		return $bwlmscredit_protect;
	}
endif;
?>