<?php
/*
Plugin Name: Sagesses ressources
Description: This plugin allows you to add some tools to manage a workshop workflow.
Version: 0.1
Author: Montera34
Author URI: https://montera34.com
License: GPLv3
Text Domain: sgs-ressources
Domain Path: /lang/
*/

// VARIABLES
// TODO: to include in plugin config page in dashboard
$workshop_pt = 'atelier';


// LOAD PLUGIN TEXT DOMAIN
// FOR STRING TRANSLATIONS
add_action( 'plugins_loaded', 'sgs_ressources_load_textdomain' );
function sgs_ressources_load_textdomain() {
	load_plugin_textdomain( 'sgs-emails', false, plugin_basename( dirname( __FILE__ ) ) . '/lang/' ); 
}

// POPULATE DROPDOWN FIELD DYNAMICALLY
// https://docs.gravityforms.com/dynamically-populating-drop-down-fields/
add_filter( 'gform_pre_render_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_validation_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_submission_filter_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_admin_pre_render_1', 'sgs_ressources_gform_populate_inscrits' );
function sgs_ressources_gform_populate_inscrits( $form ) {
 
	global $post;
	foreach ( $form['fields'] as &$field ) {
 
		if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-inscrits' ) === false ) {
		    continue;
		}
 
		$inscrits = get_post_meta($post->ID,'_atelier_inscrits',false);
		$choices = array();
 
		foreach ( $inscrits as $i ) {
		    $choices[] = array( 'text' => $i['user_email'], 'value' => $i['ID'] );
		}
 
		$field->placeholder = __('Select your email address','sgs_ressources');
		$field->choices = $choices;
	}
	return $form;

}

// ADD FORM TO ATELIER SINGLE PAGE
// https://docs.gravityforms.com/embedding-a-form/
add_filter('the_content','sgs_ressources_atelier_add_form');
function sgs_ressources_atelier_add_form($content) {
	global $post;
	global $workshop_pt;
	if ( get_post_type($post) == $workshop_pt ) {
		$content .= gravity_form( '1', true, true, false, null, false, '', false );
	}
	return $content;
}
?>