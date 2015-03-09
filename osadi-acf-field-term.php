<?php
/*
Plugin Name: Advanced Custom Fields: Term
Plugin URI: https://github.com/osadi/osadi-acf-field-term
Description: Adds the ability to chose from all terms in all taxonomies on the edit screen.
Version: 1.0.0
Author: Oskar Adin
Author URI: https://github.com/osadi
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

load_plugin_textdomain( 'osadi-acf-term', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 

function include_field_types_term( $version ) {
	include_once( 'class-osadi-acf-field-term.php' );
}
add_action( 'acf/include_field_types', 'include_field_types_term' );