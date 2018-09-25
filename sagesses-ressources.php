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


// PAGE TEMPLATES CREATOR
include("includes/pagetemplater/pagetemplater.php");

// LOAD PLUGIN TEXT DOMAIN
// FOR STRING TRANSLATIONS
add_action( 'plugins_loaded', 'sgs_ressources_load_textdomain' );
function sgs_ressources_load_textdomain() {
	load_plugin_textdomain( 'sgs-ressources', false, plugin_basename( dirname( __FILE__ ) ) . '/lang/' ); 
}

// POPULATE DROPDOWN FIELD DYNAMICALLY
// https://docs.gravityforms.com/dynamically-populating-drop-down-fields/
add_filter( 'gform_pre_render_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_validation_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_submission_filter_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_admin_pre_render_1', 'sgs_ressources_gform_populate_inscrits' );
function sgs_ressources_gform_populate_inscrits( $form ) {
 
	if ( is_single() ) {
	global $post;
	foreach ( $form['fields'] as &$field ) {
 
		if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-inscrits' ) === false ) {
		    continue;
		}
 
		$inscrits = get_post_meta($post->ID,'_atelier_inscrits',false);
		$inscrits_presents = get_post_meta($post->ID,'_atelier_inscrits_presents',false);
		$choices = array();
 
		foreach ( $inscrits as $i ) {
			if ( array_search($i['ID'],array_column($inscrits_presents,'ID')) === FALSE ) $choices[] = array( 'text' => $i['user_email'], 'value' => $i['ID'] );
		}
 
		$field->placeholder = __('Select your email address','sgs_ressources');
		$field->choices = $choices;
	}
	}
	return $form;

}

// ADD FORM TO ATELIER SINGLE
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

// UPDATE ATELIER INSCRITS META FIELD
// https://docs.gravityforms.com/gform_after_submission/#1-update-post
add_action("gform_after_submission_1", "sgs_ressources_atelier_inscrits_presents_update", 10, 2);
function sgs_ressources_atelier_inscrits_presents_update($entry, $form) {

	$p = get_post( $entry['post_id'] );
	$cf = get_post_meta($p->ID,'_atelier_inscrits_presents',false);
	if ( is_array($cf) ) $nf = array_column($cf,'ID');
	$nf[] = $entry['1'];
	update_post_meta($p->ID, '_atelier_inscrits_presents', $nf );

}
?>
