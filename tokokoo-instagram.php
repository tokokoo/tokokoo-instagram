<?php
/*
Plugin Name: Tokokoo Instagram
Plugin URI: http://wordpress.org/plugins/tokokoo-instagram/
Description: Show your recently uploaded, liked, or popular Instagram photos in your sidebar easily.
Version: 0.1
Author: Rizqyhi
Author URI: http://tokokoo.com
Author Email: rizqyhi@tokokoo.com
License: GPLv2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Tokokoo_Instagram' ) ) :

class Tokokoo_Instagram {

	/**
	 * PHP5 constructor method.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( &$this, 'constants' ), 1 );
		add_action( 'plugins_loaded', array( &$this, 'i18n' ), 2 );
		add_action( 'plugins_loaded', array( &$this, 'includes' ), 3 );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'widgets_init', array( &$this, 'register_widgets' ) );
		add_action( 'admin_menu', array( &$this, 'add_plugin_page' ) );
	}

	/**
	 * Defines constants used by the plugin.
	 *
	 * @since 0.1
	 */
	public function constants() {
		define( 'TOKOKOO_INSTAGRAM_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'TOKOKOO_INSTAGRAM_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		define( 'TOKOKOO_INSTAGRAM_INCLUDES', TOKOKOO_INSTAGRAM_DIR . trailingslashit( 'includes' ) );
	}

	/**
	 * Loads the translation files.
	 *
	 * @since 0.1
	 */
	public function i18n() {
		load_plugin_textdomain( 'koo-instagram', false, TOKOKOO_INSTAGRAM_DIR . 'languages/' );
	}

	/**
	 * Loads the initial files needed by the plugin.
	 *
	 * @since 0.1
	 */
	public function includes() {
		require_once( TOKOKOO_INSTAGRAM_INCLUDES . 'setting-page.php' );
		require_once( TOKOKOO_INSTAGRAM_INCLUDES . 'widget-tokokoo-instagram.php' );
	}

	/**
	 * Init function for the plugin.
	 *
	 * @since 0.1
	 */
	public function init() {
		/* Load the style only if there is any active widget */
		if( is_active_widget( '', '', 'tokokoo_instagram_widget' ) ) {
			wp_enqueue_style( 'tokokoo-instagram', TOKOKOO_INSTAGRAM_URI . 'assets/tokokoo-instagram.css');
		}
	}
	
	/**
	 * Add plugin setting page.
	 *
	 * @since 0.1
	 */
	public function add_plugin_page() {
		add_options_page( 'Tokokoo  Instagram', __( 'Tokokoo Instagram', 'koo-instagram' ), 'manage_options', 'tokokoo-instagram', 'tokokoo_instagram_setting_page' );
	}
	
	/**
	 * Register the included widget.
	 *
	 * @since 0.1
	 */
	public function register_widgets() {
		register_widget( 'Tokokoo_Instagram_Widget' );
	}	
	
	/**
	 * Get access token after user authorize the application, and save the access token to the settings.
	 *
	 * @param string $code - the code received from Instagram
	 * @since 0.1
	 */
	 public static function get_access_token( $code ) {
	 	$settings = get_option( 'tokokoo-instagram' );
	 	
	 	$args = array(
			'method'	=> 'POST',
			'timeout'	=> 45,
			'body' 		=> array(
				'client_id'	=> $settings['client_id'],
				'client_secret'	=> $settings['client_secret'],
				'grant_type'	=> 'authorization_code',
				'redirect_uri'	=> admin_url( 'options-general.php?page=tokokoo-instagram' ),
				'code'			=> $code
			)
		);
		
		$response = wp_remote_post( 'https://api.instagram.com/oauth/access_token', $args );
		
		if ( is_wp_error( $response ) || 200 != $response['response']['code'] )
			return json_decode( $response['body'], true );
		
		$result = json_decode( $response['body'], true );
		
		/* Update the plugin settings with the new access token received */
		$settings['access_token'] = $result['access_token'];
		$settings['user_id'] = $result['user']['id'];
		update_option( 'tokokoo-instagram', $settings );		
	 }
	 
	/**
	 * Function to get the user profile.
	 * Also can be used to check whether the access token is valid or not. Invalid access token could happen
	 * if the user revoke the autorization of the application.
	 *
	 * @since 0.1
	 */
	 public static function get_user_profile() {
	 	$settings = get_option( 'tokokoo-instagram' );
		$url = add_query_arg( 'access_token', $settings['access_token'], 'https://api.instagram.com/v1/users/' . $settings['user_id'] );
		
		$response = wp_remote_get( $url );
		
		if ( is_wp_error( $response ) || 200 != $response['response']['code'] )
			return $response['response'];
		
		$result = json_decode( $response['body'], true );
		return $result;
	 }
}

new Tokokoo_Instagram;

endif;
?>