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
// to add more templates edit pagetemplater, around line 72
include("includes/pagetemplater/pagetemplater.php");

// LOAD PLUGIN TEXT DOMAIN
// FOR STRING TRANSLATIONS
add_action( 'plugins_loaded', 'sgs_ressources_load_textdomain' );
function sgs_ressources_load_textdomain() {
	load_plugin_textdomain( 'sgs-ressources', false, plugin_basename( dirname( __FILE__ ) ) . '/lang/' ); 
}

/**
 * New user registrations should have display_name set 
 * to 'firstname lastname'. This is best used on the
 * 'user_register' action.
 *
 * @param int $user_id The user ID
 */
add_action( 'user_register', 'sgs_ressourcesset_default_user_config' );
function sgs_ressourcesset_default_user_config( $user_id ) {
	$user = get_userdata( $user_id );
	$name = sprintf( '%s %s', $user->first_name, $user->last_name );
	$args = array(
		'ID' => $user_id,
		'display_name' => $name,
		'nickname' => $name
	);
	wp_update_user( $args );
}

// POPULATE DROPDOWN FIELD DYNAMICALLY
// with users with subscriber role
add_filter( 'gform_pre_render_2', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_validation_2', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_submission_filter_2', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_admin_pre_render_2', 'sgs_ressources_gform_populate_users_subscribers' );
function sgs_ressources_gform_populate_users_subscribers( $form ) {
 
	if ( is_single() ) {
	global $post;
	foreach ( $form['fields'] as &$field ) {
 
		if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-users' ) === false ) {
		    continue;
		}
 		
		$args = array(
			'blog_id' => '2',
			'role' => 'subscriber',
			'number' => -1
		);
		$users = get_users($args);
		$choices = array();
 
		foreach ( $users as $u ) {
			$choices[] = array( 'text' => $u->user_email, 'value' => $u->ID );
		}
 
		$field->placeholder = __('Select your email address','sgs-ressources');
		$field->choices = $choices;
	}
	}
	return $form;

}

// POPULATE DROPDOWN FIELD DYNAMICALLY
// with subscribers to a workshop
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
 
		$field->placeholder = __('Select your email address','sgs-ressources');
		$field->choices = $choices;
	}
	}
	return $form;

}

// ADD EXTRA CONTENT TO ATELIER SINGLE
// + confirmation form
// + workshop metadata
// + list of registered and present people
// + subscription form
// https://docs.gravityforms.com/embedding-a-form/
add_filter('the_content','sgs_ressources_atelier_add_extra_data',5);
add_filter('the_content','sgs_ressources_atelier_subscription_form',7);
add_filter('the_content','sgs_ressources_atelier_add_form',10);
function sgs_ressources_atelier_add_form($content) {
	global $post;
	global $workshop_pt;
	if ( get_post_type($post) != $workshop_pt || !is_single() ) return $content;

	$content .= gravity_form( '1', true, true, false, null, false, '', false );

	return $content;
}

function sgs_ressources_atelier_add_extra_data($content) {
	global $post;
	global $workshop_pt;
	if ( get_post_type($post) != $workshop_pt || !is_single() ) return $content;

	$a_perma = get_permalink($post->ID);
	$a_date = get_post_meta($post->ID,'_atelier_date',true);
	$a_time = get_post_meta($post->ID,'_atelier_heure',true);
	$a_time_end = get_post_meta($post->ID,'_atelier_heure_fin',true);
	$a_registered = get_post_meta($post->ID,'_atelier_inscrits',false);
	$ar_count = ( $a_registered[0] === FALSE ) ? 0 : count($a_registered);
	$a_present = get_post_meta($post->ID,'_atelier_inscrits_presents',false);
	$ap_count = ( $a_present[0] === FALSE ) ? 0 : count($a_present);
	$ar_items = '';
	foreach ( $a_registered as $ar ) {
		$ar_items .= ( array_search($ar['ID'],array_column($a_present,'ID')) === FALSE ) ? '<li class="inscrit inscrit-non-confirmed">'.$ar['display_name'].' ('.$ar['user_email'].')</li>' : '<li class="inscrit-confirmed"><em>'.$ar['display_name'].' ('.$ar['user_email'].') '.__('Confirmed','sgs-ressources').'</em></li>';
	}
	$ar_list = ( $ar_items != '' ) ? '<ol>'.$ar_items.'</ol>' : '';

	$a_meta = '
	<dl class="workshop workshop-meta">
		<dt>'.__("Date","sgs-ressources").'</dt><dd>'.$a_date.'</dd>
		<dt>'.__("Time","sgs-ressources").'</dt><dd>'.$a_time.' &dash; '.$a_time_end.'</dd>
	</dl>
	';
	$ar_out = '
	<h2>'.__("Registered people","sgs-ressources").'</h2>'.$ar_list;
	$content = $a_meta.$content.$ar_out;

	return $content;
}

function sgs_ressources_atelier_subscription_form($content) {
	global $post;
	global $workshop_pt;
	if ( get_post_type($post) != $workshop_pt || !is_single() ) return $content;

	$content .= gravity_form( '2', true, true, false, null, false, '', false );

	return $content;

}

// UPDATE ATELIER INSCRITS META FIELD
// https://docs.gravityforms.com/gform_after_submission/#1-update-post
add_action("gform_after_submission_1", "sgs_ressources_atelier_inscrits_presents_update", 10, 2);
function sgs_ressources_atelier_inscrits_presents_update($entry, $form) {
	
	$i_id = $entry['1'];
	$p = get_post( $entry['post_id'] );
	$cf = get_post_meta($p->ID,'_atelier_inscrits_presents',false);
	if ( is_array($cf) ) $nf = array_column($cf,'ID');
	$nf[] = $i_id;
	$nf_control = update_post_meta($p->ID, '_atelier_inscrits_presents', $nf );

	// send mail
//	if ( $nf_control != 1 ) { echo "Error. Try again."; return; }
//	$i_mail = get_user($i_id);
//	$send_control = sgs_ressources_atelier_list($i_email,$a);
//	if ( $send_control != 1 ) { echo "Error. Try again."; return; }
}

// DO NEW SUBSCIPTION
// to a workshop
// https://docs.gravityforms.com/gform_after_submission/#1-update-post
add_action("gform_after_submission_2", "sgs_ressources_atelier_subscription", 99, 2);
function sgs_ressources_atelier_subscription($entry, $form) {
	
	if ( $entry['7'] != '' ) {
		$i_id =  $entry['7'];

	} else {
		$user = get_user_by( 'email', $entry['4'] );
		$i_id =  $user->ID;
;
	}
	$p = get_post( $entry['post_id'] );
	$cf = get_post_meta($p->ID,'_atelier_inscrits',false);
	if ( is_array($cf) ) $nf = array_column($cf,'ID');
	$nf[] = $i_id;
	$nf_control = update_post_meta($p->ID, '_atelier_inscrits', $nf );

}

// PRODUCE ATELIERS LIST
// to include it in home page
add_filter('the_content','sgs_ressources_atelier_list');
function sgs_ressources_atelier_list($content) {

	if ( !is_page_template('sgs-home.php') ) return $content;

	global $workshop_pt;
	$args = array(
		'post_type' => $workshop_pt,
		'posts_per_page' => -1,
		'orderby' => 'meta_value',
		'meta_key' => '_atelier_date'
	);
	$ateliers = get_posts($args);
	$today = date('Y-m-d');
	$a_rows = '';
	$ap_rows = '';
	foreach ( $ateliers as $a ) {
		$a_perma = get_permalink($a->ID);
		$a_date = get_post_meta($a->ID,'_atelier_date',true);
		$a_time = get_post_meta($a->ID,'_atelier_heure',true);
		$a_registered = get_post_meta($a->ID,'_atelier_inscrits',false);
		$ar_count = ( $a_registered[0] === FALSE ) ? 0 : count($a_registered);
		$a_present = get_post_meta($a->ID,'_atelier_inscrits_presents',false);
		$ap_count = ( $a_present[0] === FALSE ) ? 0 : count($a_present);
		if ( $today <= $a_date ) {
			$a_rows .= '
			<tr>
				<td><a href="'.$a_perma.'">'.$a->post_title.'</a></td>
				<td>'.$a_date.'</td>
				<td>'.$a_time.'</td>
				<td>'.$ar_count.'</td>
				<td>'.$ap_count.'</td>
			</tr>
			';
		} else {
			$ap_rows .= '
			<tr>
				<td><a href="'.$a_perma.'">'.$a->post_title.'</a></td>
				<td>'.$a_date.'</td>
				<td>'.$a_time.'</td>
				<td>'.$ar_count.'</td>
				<td>'.$ap_count.'</td>
			</tr>
			';
		}
	}
	$a_head = '
		<thead><tr>
			<th>'.__("Workshop","sgs-ressources").'</th>
			<th>'.__("Date","sgs-ressources").'</th>
			<th>'.__("Time","sgs-ressources").'</th>
			<th>'.__("Registered people","sgs-ressources").'</th>
			<th>'.__("Present people","sgs-ressources").'</th>
		</tr></thead>
	';
	$a_table = ( $a_rows != "" ) ? '
	<table class="workshop-list workshop-list-current">
		'.$a_head.'
		<tbody>'.$a_rows.'</tbody></table>
	' : '';
	$ap_table = ( $ap_rows != "" ) ? '
	<h2>'.__('Past workshops','sgs-ressources').'</h2>
	<table class="workshop-list workshop-list-past">
		'.$a_head.'
		<tbody>'.$ap_rows.'</tbody></table>
	' : '';

	$content .= $a_table.$ap_table;
	return $content;
}

// SEND WORKSHOP INFO BY MAIL
// to people registered to workshop
//function sgs_ressources_send_mail($email_address,$workshop) {
//	$from = 'ressources@activezvosressources.tools';
//	$from_name = 'Activez vos ressources';
//	$replyto = 'info@activezvosressources.tools';
//	$replyto_name = 'Sagesses';
//	$to = $email_address;
//	$subject = __('Workshop documents','sgs-ressources');
//
//	$docs = array();
//	$sent = wp_mail( $to, $subject, $body);
//	return $sent;
//}

?>
