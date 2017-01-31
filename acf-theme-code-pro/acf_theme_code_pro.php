<?php
/*
Plugin Name: Advanced Custom Fields: Theme Code Pro
Plugin URI: https://hookturn.io/downloads/acf-theme-code-pro/
Description: Generates theme code for ACF Pro field groups to speed up development.
Version: 1.2.0
Author: hookturn
Author URI: http://www.hookturn.io/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// define version
define( 'HOOKTURN_ITEM_VERSION', '1.2.0' );

// Check for dashboard or admin panel
if ( is_admin() ) {

	/**
	 * Classes
	 */
	include('core/core.php');
	include('core/group.php');
	include('core/field.php');

	/**
	 * TC Pro classes
	 */
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'pro' ) ) {
		include('pro/core/flexible-content-layout.php');
	}

	/**
	 * Single function for accessing plugin core instance
	 *
	 * @return ACFTCP_Core
	 */
	function acftcp()
	{
		static $instance;
		if ( !$instance )
			$instance = new ACFTCP_Core( plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ) );
		return $instance;
	}

	acftcp(); // kickoff

}

// update functionality
function hookturn_acftcp_plugin_updater() {

	if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
		// load our custom updater
		include( dirname( __FILE__ ) . '/pro/updates/EDD_SL_Plugin_Updater.php' );
	}

	// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
	define( 'HOOKTURN_STORE_URL', 'https://hookturn.io' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

	// the name of your product. This should match the download name in EDD exactly
	define( 'HOOKTURN_ITEM_NAME', 'ACF Theme Code Pro' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'hookturn_acftcp_license_key' ) );

	// setup the updater
	$edd_updater = new EDD_SL_Plugin_Updater( HOOKTURN_STORE_URL, __FILE__, array(
			'version' 	=> HOOKTURN_ITEM_VERSION, 			// current version number
			'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
			'item_name' => HOOKTURN_ITEM_NAME, 	// name of this plugin
			'author' 	=> 'hookturn',  		// author of this plugin
			'wp_override' => true
		)
	);

}
add_action( 'admin_init', 'hookturn_acftcp_plugin_updater', 0 );

// include the update functions
include('pro/updates/hookturn-updates.php');
