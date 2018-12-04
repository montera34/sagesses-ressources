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
$seance_pt = 'seance';
$type_tx = 'type';


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
	$name = ( $user->firs_name == '' && $user->last_name == '' ) ? $user->username : sprintf( '%s %s', $user->first_name, $user->last_name );
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
add_filter( 'gform_pre_render_4', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_render_5', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_validation_2', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_validation_4', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_validation_5', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_submission_filter_2', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_submission_filter_4', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_pre_submission_filter_5', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_admin_pre_render_2', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_admin_pre_render_4', 'sgs_ressources_gform_populate_users_subscribers' );
add_filter( 'gform_admin_pre_render_5', 'sgs_ressources_gform_populate_users_subscribers' );
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
			$text = ( $u->display_name != '' ) ? $u->display_name : $u->username;
			$choices[] = array( 'text' => $text.' ('.$u->user_email.')', 'value' => $u->ID );
		}
 
		$field->placeholder = __('Select your email address','sgs-ressources');
		$field->choices = $choices;
	}
	}
	return $form;

}

// POPULATE DROPDOWN FIELD DYNAMICALLY
// with subscribers to a workshop or session
// https://docs.gravityforms.com/dynamically-populating-drop-down-fields/
add_filter( 'gform_pre_render_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_render_3', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_validation_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_validation_3', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_submission_filter_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_pre_submission_filter_3', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_admin_pre_render_1', 'sgs_ressources_gform_populate_inscrits' );
add_filter( 'gform_admin_pre_render_3', 'sgs_ressources_gform_populate_inscrits' );
function sgs_ressources_gform_populate_inscrits( $form ) {
 
	if ( is_single() ) {
		global $post;
		global $workshop_pt;
		global $seance_pt;
		if ( get_post_type($post) == $workshop_pt ) $prefix = "_atelier";
		else $prefix = "_seance";
		
		foreach ( $form['fields'] as &$field ) {
 
			if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-inscrits' ) === false )
				continue;
 
			$inscrits = get_post_meta($post->ID,$prefix.'_inscrits',false);
			$inscrits_presents = get_post_meta($post->ID,$prefix.'_inscrits_presents',false);
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
	
	if ( get_post_type($post) != $workshop_pt || !is_single() || get_post_meta($post->ID,'_atelier_simpleform',true) == 1 ) return $content;

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

	if ( get_post_meta($post->ID,'_atelier_simpleform',true) == 1 )
		$form_id = '5';
	else 
		$form_id = '2';
	$content .= gravity_form( $form_id, true, true, false, null, false, '', false );

	return $content;

}

// UPDATE ATELIER INSCRITS META FIELD
// https://docs.gravityforms.com/gform_after_submission/#1-update-post
add_action("gform_after_submission_1", "sgs_ressources_atelier_inscrits_presents_update", 10, 2);
add_action("gform_after_submission_3", "sgs_ressources_atelier_inscrits_presents_update", 10, 2);
function sgs_ressources_atelier_inscrits_presents_update($entry, $form) {

	global $workshop_pt;
	global $seance_pt;
	// $entry['1'] user id
	// $entry['2'] post id, atelier or seance
	
	if ( get_post_type($post_id) == $workshop_pt ) {
		$prefix = "_atelier";
		$pt = $workshop_pt;
	}
	else {
		$prefix= '_seance';
		$pt = $seance_pt;
	}

	$i_id = $entry['1'];
	$i = get_user_by('id',$i_id);
	$p = get_post( $entry['2'] );
	$cf = get_post_meta($p->ID,$prefix.'_inscrits_presents',false);
	if ( is_array($cf) ) $nf = array_column($cf,'ID');
	$nf[] = $i_id;

	// subscription to suspension
	$suspension_control = sgs_ressources_suspension($i,$p);
	if ( $suspension_control != 1 ) {
		echo __('Error: suscription to suspension failed. Try again.','sgs-ressources'); return;
	}

	// send mail
	if ( get_post_type($entry['2']) == $workshop_pt || get_post_type($entry['2']) == $seance_pt && $entry['3'] == '1' )
		$send_control = sgs_ressources_send_mail($i,$p,$pt,$prefix.'_documents');
	else $send_control = 1;
	if ( $send_control != 1 ) {
		echo __('Error: no mail sent. Try again.','sgs-ressources'); return;
	}

	update_post_meta($p->ID, $prefix.'_inscrits_presents', $nf );
	return;
}

add_action("gform_after_submission_5", "sgs_ressources_atelier_inscrits_presents_update_simpleform", 10, 2);
function sgs_ressources_atelier_inscrits_presents_update_simpleform($entry, $form) {

	global $workshop_pt;
	global $seance_pt;
	// $entry['9'] post id, atelier or seance
	// $entry['7'] user id
	// $entry['4'] user id
	// $entry['8'] premier atelier. Oui/Non
	if ( get_post_type($post_id) == $workshop_pt ) {
		$prefix = "_atelier";
		$pt = $workshop_pt;
	}
	else {
		$prefix= '_seance';
		$pt = $seance_pt;
	}

	if ( $entry['8'] == 'Oui' ) {
		$i_email = $entry['4'];
		$i = get_user_by('email',$i_email);
		$i_id = $i->ID;
	}
	else {
		$i_id = $entry['7'];
		$i = get_user_by('id',$i_id);
	}


	$p = get_post( $entry['9'] );
	$inscrits = get_post_meta($p->ID,$prefix.'_inscrits',false);
	$presents = get_post_meta($p->ID,$prefix.'_inscrits_presents',false);
	if ( is_array($inscrits) ) $inscrits_new = array_column($inscrits,'ID');
	if ( is_array($presents) ) $presents_new = array_column($presents,'ID');
	$inscrits_new[] = $i_id;
	$presents_new[] = $i_id;

	// subscription to suspension
	$suspension_control = sgs_ressources_suspension($i,$p);
	if ( $suspension_control != 1 ) {
		echo __('Error: suscription to suspension failed. Try again.','sgs-ressources'); return;
	}
	
	// send mail
	$send_control = sgs_ressources_send_mail($i,$p,$pt,$prefix.'_documents');
	$send_control = 1;
	if ( $send_control != 1 ) {
		echo __('Error: no mail sent. Try again.','sgs-ressources'); return;
	}

	update_post_meta($p->ID, $prefix.'_inscrits', $inscrits_new );
	update_post_meta($p->ID, $prefix.'_inscrits_presents', $presents_new );
	return;
}


// DO NEW SUBSCIPTION
// to a workshop
// or seance
// https://docs.gravityforms.com/gform_after_submission/#1-update-post
add_action("gform_after_submission_2", "sgs_ressources_atelier_subscription", 99, 2);
add_action("gform_after_submission_4", "sgs_ressources_atelier_subscription", 99, 2);
function sgs_ressources_atelier_subscription($entry, $form) {

	global $workshop_pt;
	global $seance_pt;
	// $entry['9'] post id, atelier or seance
	// $entry['7'] user id
	// $entry['4'] user id
	if ( get_post_type($entry['9']) == $workshop_pt ) $prefix = "_atelier";
	else $prefix= '_seance';
	if ( $entry['7'] != '' ) {
		$i_id =  $entry['7'];

	} else {
		$user = get_user_by( 'email', $entry['4'] );
		$i_id =  $user->ID;

	}
	//$p = get_post( $entry['post_id'] );
	$cf = get_post_meta($entry['9'],$prefix.'_inscrits',false);
	if ( is_array($cf) ) $nf = array_column($cf,'ID');
	$nf[] = $i_id;
	$nf_control = update_post_meta($entry['9'], $prefix.'_inscrits', $nf );

}

// PRODUCE ATELIERS AND SESSIONS LIST
// to include it in any page
add_filter('the_content','sgs_ressources_lists');
function sgs_ressources_lists($content) {

	global $workshop_pt;
	global $seance_pt;
	if ( is_page_template('sgs-workshops.php') ) {
		$pt = $workshop_pt;
		$prefix = "_atelier";
		$class = "workshop";
		$label = __("Workshop","sgs-ressources");
		$label_past = __('Past workshops','sgs-ressources');
	}
	elseif ( is_page_template('sgs-seances.php') ) {
		$pt = $seance_pt;
		$prefix = "_seance";
		$class = "workshop";
		$label = __("Session","sgs-ressources");
		$label_past = __('Past sessions','sgs-ressources');
	} else {
		return $content;
	}

	$args = array(
		'post_type' => $pt,
		'posts_per_page' => -1,
		//'orderby' => 'meta_value',
		//'meta_key' => '_atelier_date'
		'meta_query' => array(
			'relation' => 'AND',
			'date_clause' => array(
				'key' => '_atelier_date',
				'compare' => 'EXISTS',
			),
			'hour_clause' => array(
				'key' => '_atelier_heure',
				'compare' => 'EXISTS',
			),
		),
		'orderby' => array(
			'date_clause' => 'ASC',
			'hour_clause' => 'ASC',
		),
	);
	$ateliers = get_posts($args);
	$today = date('Y-m-d');
	$a_rows = '';
	$ap_rows = '';
	foreach ( $ateliers as $a ) {
		$title = $a->post_title;
		$a_name = ( $pt == $workshop_pt ) ? $title : sgs_ressources_session_title($title,$a);
		$a_perma = get_permalink($a->ID);
		$a_date = get_post_meta($a->ID,'_atelier_date',true);
		$a_time = get_post_meta($a->ID,'_atelier_heure',true);
		$a_registered = get_post_meta($a->ID,$prefix.'_inscrits',false);
		$ar_count = ( $a_registered[0] === FALSE ) ? 0 : count($a_registered);
		$a_present = get_post_meta($a->ID,$prefix.'_inscrits_presents',false);
		$ap_count = ( $a_present[0] === FALSE ) ? 0 : count($a_present);
		if ( $today <= $a_date ) {
			$a_rows .= '
			<tr>
				<td><a href="'.$a_perma.'">'.$a_name.'</a></td>
				<td>'.$a_date.'</td>
				<td>'.$a_time.'</td>
				<td>'.$ar_count.'</td>
				<td>'.$ap_count.'</td>
			</tr>
			';
		} else {
			$ap_rows .= '
			<tr>
				<td><a href="'.$a_perma.'">'.$a_name.'</a></td>
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
			<th>'.$label.'</th>
			<th>'.__("Date","sgs-ressources").'</th>
			<th>'.__("Time","sgs-ressources").'</th>
			<th>'.__("Registered people","sgs-ressources").'</th>
			<th>'.__("Present people","sgs-ressources").'</th>
		</tr></thead>
	';
	$a_table = ( $a_rows != "" ) ? '
	<table class="'.$class.'-list '.$class.'-list-current">
		'.$a_head.'
		<tbody>'.$a_rows.'</tbody></table>
	' : '';
	$ap_table = ( $ap_rows != "" ) ? '
	<h2>'.$label_past.'</h2>
	<table class="'.$class.'-list '.$class.'-list-past">
		'.$a_head.'
		<tbody>'.$ap_rows.'</tbody></table>
	' : '';

	$content .= $a_table.$ap_table;
	return $content;
}

// SEND WORKSHOP INFO BY MAIL
// to people registered to workshop
function sgs_ressources_send_mail($user,$post,$pt,$cf) {
	$nl = "\r\n\r\n";

	// headers
	$from_name = 'Activez vos ressources (Sagesses)';
	$headers[] = "From: ".$from_name." <ressources@activezvosressources.tools>".$nl;
	$replyto_name = 'Sagesses';
	$headers[] = "Reply-To: ".$replyto_name." <info@activezvosressources.tools>".$nl;
	
	// to
	$to = $user->user_email;
	
	// subject
	$subject = sprintf(__('Documents of the workshop %s','sgs-ressources'),$post->post_title);
	
	// body
	$a_docs = get_post_meta($post->ID,$cf,false);
	$a_docs_out = "";
	$count = 0;
	foreach ( $a_docs as $d ) {
		$count++;
		$d_title = $d['post_title'];
		$d_archive = get_post_meta($d['ID'],'_doc_archive',true);
		$a_docs_out .= $count."/ ".$d_title.": ".$d_archive['guid'].$nl;
	}
	$body = sprintf(__("Hi %s,","sgs-ressources"),$user->display_name).$nl;
	$body .= sprintf(__("here you have the documents related to the workshop '%s' you have completed recently.","sgs-ressources"),$post->post_title).$nl;
	$body .= sprintf(__("Click in the links to download the documents:","sgs-ressources"),$post->post_title).$nl;
	$body .= $a_docs_out.$nl;
	$body .= __("Copy the link and paste it in your browser if you have any problem reaching the document.","sgs-ressources").$nl;
	$body .= __("Thanks for participate in the workshop.","sgs-ressources").$nl;
	$body .= "Cilica Chlimper";

	$sent = wp_mail( $to, $subject, $body, $headers );
	return $sent;
}

// SUBSCRIPTION TO SUSPENSION
// to people registered to workshop
function sgs_ressources_suspension($user,$post) {
	if ( $suspension = get_post_meta( $post->ID,'_atelier_suspension',true) != 1 )
		return 1;
	 
	$settings = (array) get_option('sgs_emails_settings');
	$addresses = array_values(array_filter($settings['sgs_emails_settings_addresses']));
	if ( array_search($user->user_email,$addresses) === FALSE )
		$addresses[] = $user->user_email;
	$settings['sgs_emails_settings_addresses'] = $addresses;
	return update_option('sgs_emails_settings',$settings);
}

// ADD EXTRA CONTENT TO SEANCE SINGLE
// + confirmation form
// + seance metadata
// + list of registered and present people
// + subscription form
// https://docs.gravityforms.com/embedding-a-form/
add_filter('the_content','sgs_ressources_seance_add_extra_data',5);
add_filter('the_content','sgs_ressources_seance_subscription_form',7);
add_filter('the_content','sgs_ressources_seance_add_form',10);
function sgs_ressources_seance_add_form($content) {
	global $post;
	global $seance_pt;
	if ( get_post_type($post) != $seance_pt || !is_single() ) return $content;

	$content .= gravity_form( '3', true, true, false, null, false, '', false );

	return $content;
}

function sgs_ressources_seance_add_extra_data($content) {
	global $post;
	global $seance_pt;
	if ( get_post_type($post) != $seance_pt || !is_single() ) return $content;

	$a_perma = get_permalink($post->ID);
	$a_signup_form_perma = "#gform_wrapper_4";
	$a_date = get_post_meta($post->ID,'_atelier_date',true);
	$a_time = get_post_meta($post->ID,'_atelier_heure',true);
	$a_time_end = get_post_meta($post->ID,'_atelier_heure_fin',true);
	// Registered users
	$a_registered = get_post_meta($post->ID,'_seance_inscrits',false);
	$ar_count = ( $a_registered[0] === FALSE ) ? 0 : count($a_registered);
	$a_present = get_post_meta($post->ID,'_seance_inscrits_presents',false);
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

function sgs_ressources_seance_subscription_form($content) {
	global $post;
	global $seance_pt;
	if ( get_post_type($post) != $seance_pt || !is_single() ) return $content;

	$content .= gravity_form( '4', true, true, false, null, false, '', false );

	return $content;

}

// FILTER SESSION TITLE
add_filter('the_title','sgs_ressources_session_title',10,2);
function sgs_ressources_session_title($title,$post) {
	global $seance_pt;
	if ( $seance_pt == get_post_type($post) && !is_admin() ) {
		global $type_tx;
		$a_type = get_the_terms($post->ID,$type_tx);
		$title = $a_type[0]->name;
	}
	
	return $title;
}
?>
