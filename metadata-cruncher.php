<?php
/*
Plugin Name: Metadata Cruncher
Description: This plugin copies the value of description to caption in the media editor, after the media attachment has been uploaded. I created this plugin to fix the annoying feature of Wordpress, when by uploading images it copies the IPTC caption to description and leaves the caption field empty.
Version: 1.0
Author: Peter Hudec
Author URI: http://peterhudec.com
Plugin URI: http://peterhudec.com/programming/metada
License: GPL2
*/

// includes
include 'functions.php';
include 'settings.php';

// globals
$mc_metadata = '';

/**
 * The wp_handle_upload_prefilter hook gets triggered before wordpress erases all the image metadata
 */
add_action('wp_handle_upload_prefilter', 'mc_upload');
function mc_upload($file){
	global $mc_metadata;
	
	// get meta
	$mc_metadata = parse_meta($file['tmp_name']);
	
	// return untouched file
	return $file;
}

/**
 * The "Caption" input field in the media item form is filled with
 * post excerpt in the get_attachment_fields_to_edit() function
 * 
 * This action copies the attachment's post_content to its post_excerpt
 * after the attachment post is inserted to the DB after file upload
 */
add_action('add_attachment', 'mc');
function mc($post_ID){
	global $mc_metadata;
	$options = get_option('mc');
	
	$post = get_post($post_ID);
	
	// title
	$post->post_title = render_template($options['title']);
	// caption
	$post->post_excerpt = render_template($options['caption']);
	// description
	$post->post_content = render_template($options['description']);
	// alt is meta attribute
	update_post_meta($post_ID, '_wp_attachment_image_alt', render_template($options['alt']));
	
	// add custom metadata
	foreach ($options['custom_meta'] as $key => $value) {
	    // update or create
	    $value = render_template($value);
		add_post_meta($post_ID, $key, $value, true) or update_post_meta($post_ID, $key, $value);
	}
	
	wp_update_post( $post );
}

?>