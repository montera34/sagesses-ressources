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

// REGISTER AND LOAD STYLES
add_action( 'wp_enqueue_scripts', 'sgs_ressources_register_load_styles' );
function sgs_ressources_register_load_styles() {
	wp_enqueue_style( 'sgs-ressources-css',plugins_url( 'style/style.css' , __FILE__) );

} // end register load map styles

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
			'blog_id' => get_current_blog_id(),
			'role' => 'subscriber',
			'number' => -1
		);
		$users = get_users($args);
		$choices = array();
 
		foreach ( $users as $u ) {
			$choices[] = array( 'text' => $u->display_name.' ('.$u->user_email.')', 'value' => $u->ID );
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
add_filter('the_content','sgs_ressources_atelier_subscription_form',10);
add_filter('the_content','sgs_ressources_atelier_add_form',7);
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
	$a_signup_form_perma = "#gform_wrapper_2";
	$a_date = get_post_meta($post->ID,'_atelier_date',true);
	$a_time = get_post_meta($post->ID,'_atelier_heure',true);
	$a_time_end = get_post_meta($post->ID,'_atelier_heure_fin',true);
	// Docs
	$a_docs = get_post_meta($post->ID,'_atelier_documents',false);
	$ad_items = array();
	foreach ( $a_docs as $d ) {
		$d_title = $d['post_title'];
		$d_archive = get_post_meta($d['ID'],'_doc_archive',true);
		$ad_items[] = '<a href="'.$d_archive['guid'].'">'.$d_title.'</a>';
	}
	$a_docs_list = implode(', ',$ad_items);
	// Registered users
	$a_registered = get_post_meta($post->ID,'_atelier_inscrits',false);
	$ar_count = ( $a_registered[0] === FALSE ) ? 0 : count($a_registered);
	$a_present = get_post_meta($post->ID,'_atelier_inscrits_presents',false);
	$ap_count = ( $a_present[0] === FALSE ) ? 0 : count($a_present);
	$ar_items = '';
	if ( $ar_count != 0 ) {
		foreach ( $a_registered as $ar ) {
			$ar_items .= ( array_search($ar['ID'],array_column($a_present,'ID')) === FALSE ) ? '<li class="inscrit inscrit-non-confirmed">'.$ar['display_name'].' ('.$ar['user_email'].')</li>' : '<li class="inscrit-confirmed"><em>'.$ar['display_name'].' ('.$ar['user_email'].') '.__('Confirmed','sgs-ressources').'</em></li>';
		}
	}
	$ar_list = ( $ar_items != '' ) ? '<ol>'.$ar_items.'</ol>' : __('No registered people.','sgs-ressources');

	$a_meta = '
	<dl class="workshop workshop-meta">
		<dt>'.__("Date","sgs-ressources").'</dt><dd>'.$a_date.'</dd>
		<dt>'.__("Time","sgs-ressources").'</dt><dd>'.$a_time.' &dash; '.$a_time_end.'</dd>
		<dt>'.__("Documents","sgs-ressources").'</dt><dd>'.$a_docs_list.'</dd>
	</dl>
	';
	$ar_out = '
	<h2>'.__("Registered people","sgs-ressources").'</h2>'
	.$ar_list.
	'<p class="alert-warning">'.sprintf(__("If you are not in the list above, <a href='%s'>sign up for this workshop</a>.","sgs-ressources"),$a_signup_form_perma).'</p>
	';
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
	$i = get_user_by('ID',$i_id);
	$p = get_post( $entry['post_id'] );
	$cf = get_post_meta($p->ID,'_atelier_inscrits_presents',false);
	if ( is_array($cf) ) $nf = array_column($cf,'ID');
	$nf[] = $i_id;

	// subscription to suspension
	$suspenssion = get_post_meta( $p->ID,'_atelier_suspension',true);
	if ( $suspenssion == 1 ) {
		$suspension_control = sgs_ressources_suspension($i);
	} else {
		$suspension_control = 1;
	}
	// send mail
	$send_control = sgs_ressources_send_mail($i,$p);
	$send_control = 1;
	if ( $send_control != 1 || $suspension_control != 1 ) {
		echo "Error: no mail sent or suspension subscription failed. Try again."; return;
	} else {
		update_post_meta($p->ID, '_atelier_inscrits_presents', $nf );
		return;
	}
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
function sgs_ressources_send_mail($user,$workshop) {
	$nl = "\r\n\r\n";

	// headers
	$from_name = 'Activez vos ressources (Sagesses)';
	$headers[] = "From: ".$from_name." <ressources@activezvosressources.tools>".$nl;
	$replyto_name = 'Sagesses';
	$headers[] = "Reply-To: ".$replyto_name." <info@activezvosressources.tools>".$nl;

	// to
	$to = $user->user_email;
	// subject
	$subject = sprintf(__('Documents of the workshop %s','sgs-ressources'),$workshop->post_title);
	// body
	$a_docs = get_post_meta($workshop->ID,'_atelier_documents',false);
	$a_docs_out = "";
	$count = 0;
	foreach ( $a_docs as $d ) {
		$count++;
		$d_title = $d['post_title'];
		$d_archive = get_post_meta($d['ID'],'_doc_archive',true);
		$a_docs_out .= $count."/ ".$d_title.": ".$d_archive['guid'].$nl;
	}
	$body = sprintf(__("Hi %s,","sgs-ressources"),$user->display_name).$nl;
	$body .= sprintf(__("here you have the documents related to the workshop '%s' you have completed recently.","sgs-ressources"),$workshop->post_title).$nl;
	$body .= sprintf(__("Click in the links to download the documents:","sgs-ressources"),$workshop->post_title).$nl;
	$body .= $a_docs_out.$nl;
	$body .= __("Copy the link and paste it in your browser if you have any problem reaching the document.","sgs-ressources").$nl;
	$body .= __("Thanks for participate in the workshop.","sgs-ressources").$nl;
	$body .= "Cilica Chlimper";

	$sent = wp_mail( $to, $subject, $body, $headers );
	return $sent;
}

// SUBSCRIPTION TO SUSPENSION
// to people registered to workshop
function sgs_ressources_suspension($user) {
	$settings = (array) get_option('sgs_emails_settings');
	if ( array_search($user->user_email,$settings['sgs_emails_settings_addresses']) === FALSE ) {
		$a = 0;
		while ( $a < count($settings['sgs_emails_settings_addresses']) - 1 ) {
			if ( $settings['sgs_emails_settings_addresses'][$a] == '') {
				$settings['sgs_emails_settings_addresses'][$a] = $user->user_email;
				break;
			}
			$a++;
		}
		return update_option('sgs_emails_settings',$settings);
	} else {
		// the address is already subscribed to suspension
		return 1;
	}
}
?>
