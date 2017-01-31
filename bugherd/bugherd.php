<?php
/***
Plugin Name: BugHerd
Plugin URI: http://simpleux.co.uk/plugins/wordpress/bugherd
Description: Bug tracking plugin. Allow you to integrate BugHerd sidebar into WordPress based website within one minute.
Author: Junaid Ahmed
Author URI: http://www.simpleux.co.uk
Version: 1.0.0.0
License: GPLv3 or later
***/


if(!defined("DS"))
	define("DS", DIRECTORY_SEPARATOR);

if(!defined('BUGHERD_ABSPATH'))
	define( 'BUGHERD_ABSPATH', plugin_dir_path( __FILE__ ) );

/**
 * Libraries
 */
if(!class_exists('Bugherd_Client'))
{
	require_once(BUGHERD_ABSPATH.'wp-bugherd-client.php');
}

if(!class_exists('Bugherd_View'))
{
	require_once(BUGHERD_ABSPATH.'wp-bugherd-view.php');
}

?>