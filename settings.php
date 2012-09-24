<?php

// add links to plugins list
add_filter('plugin_action_links', 'mc_action_links', 10, 2);
function mc_action_links($links, $file) {
    static $this_plugin;
    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }
    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=mc-options">Settings</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}

/*******************************************************
 * Plugin settings and options page
 *******************************************************/

// default options
register_activation_hook( __FILE__, 'mc_defaults');
function mc_defaults(){
	add_option('mc', array(
		'title' => '',
		'alt' => '',
		'caption' => '',
		'description' => '',
		'file_url' => '',
		'image_size' => '',
		'custom_meta' => array()
	));
}

// add settings
add_action('admin_init', 'mc_init');
function mc_init(){
    
    // register stylesheet and script for admin
    wp_register_script( 'mc_script', plugins_url('script.js', __FILE__));
    wp_register_style( 'mc_style', plugins_url('style.css', __FILE__));
    
    ///////////////////////////////////
    // Sections
    ///////////////////////////////////
    mc_section(1, 'Media form fields:');
    mc_section(2, 'Custom image meta tags:');
    mc_section(3, 'Available metadata variables:');
    
    ///////////////////////////////////
    // Options
    ///////////////////////////////////
    
    // Title
    // register a new setting...
    register_setting(
        'mc_title',         // option group
        'mc',               // option name
        'mc_validator'      // validator
    );              
    // ...and add it to a section
    add_settings_field(
        'mc_title',         // field id
        'Title:',           // title
        'mc_title_cb',      // callback
        'mc-section-1',     // section page
        'mc_section_1');    // section id
    
    // Alternate text
    register_setting(
        'mc_alt',           // option group
        'mc',               // option name
        'mc_validator'      // validator
    );
    add_settings_field(
        'mc_alt',           // field id
        'Alternate text:',              // title
        'mc_alt_cb',        // callback
        'mc-section-1',     // section page
        'mc_section_1');    // section id
    
    // Caption
    register_setting(
        'mc_caption',       // option group
        'mc',               // option name
        'mc_validator'      // validator
    );
    add_settings_field(
        'mc_caption',       // field id
        'Caption:', // title
        'mc_caption_cb',    // callback
        'mc-section-1',     // section page
        'mc_section_1');    // section id
    
    // Description
    register_setting(
        'mc_description',   // option group
        'mc',               // option name
        'mc_validator'      // validator
    );
    add_settings_field(
        'mc_description',   // field id
        'Description:',     // title
        'mc_description_cb',// callback
        'mc-section-1',     // section page
        'mc_section_1');    // section id
}

// add plugin settings page
add_action('admin_menu', 'mc_options');
function mc_options(){
	$page = add_plugins_page(
		'Metadata Cruncher',
		'Metadata Cruncher',
		'administrator',
		'mc-options',
		'mc_options_cb');
    
    add_action('admin_print_scripts-' . $page, 'mc_script');
    add_action('admin_print_styles-' . $page, 'mc_style');
}

function mc_script()
{
    wp_enqueue_script('mc_script');
}

function mc_style()
{
    wp_enqueue_style('mc_style');
}

/**
 * Adds section to plugin admin page
 */
function mc_section($id, $title)
{
	add_settings_section(
		"mc_section_{$id}",		// section id
		$title,					// title
		"mc_section_{$id}_cb",	// callback
		"mc-section-{$id}");	// page
}

// options page form
function mc_options_cb(){
	?>
	<div class="wrap metadata-cruncher">
		<h2>Metadata Cruncher options</h2>
		<?php settings_errors(); ?>
		<form action="options.php" method="post">
			<?php settings_fields('mc_title'); // renders hidden input fields ?>
			<?php settings_fields('mc_alt'); // renders hidden input fields ?>
			<?php do_settings_sections('mc-section-1'); ?>
			<?php do_settings_sections('mc-section-2'); ?>	
			<?php do_settings_sections('mc-section-3'); ?>		
			<?php submit_button(); ?>
		</form>
		<p>Created by <strong>Peter Hudec</strong>, <a href="http://peterhudec.com" target="_blank">peterhudec.com</a>.</p>
	</div>
	<?php
}


///////////////////////////////////
// Section callbacks
///////////////////////////////////

function mc_section_1_cb(){
	?>
	<p>
	    Specify texts with which should the media upload form be prepopulated.
	    You can use metadata tags inside curly brackets<code>{}</code>.
	    <br />
	    <strong>Example:</strong> <code>Image was taken with {EXIF:Model} camera.</code>
	</p>
	<?php
}

function mc_section_2_cb(){
    $options = get_option('mc');
	?>
	<i>You can also specify your own meta fields that will be saved to the database with the picture.</i>
	<table id="custom-meta-list" class="widefat">
		<thead>
			<th>Name</th>
			<th>Template</th>
			<th>Delete</th>
		</thead>
		<?php foreach ($options['custom_meta'] as $key => $value): ?>
			<tr>
                <td><input type="text" class="name" value="<?php echo $key ?>" /></td>
                <td><input type="text" class="template" name="mc[custom_meta][<?php echo $key ?>]" value="<?php echo $value ?>" /></td>
                <td><button class="button">Remove</button></td>
			</tr>
		<?php endforeach ?>
	</table>
	<div>
		<button id="add-custom-meta" class="button">Add New Field</button>
	</div>	
	<?php
}

function mc_section_3_cb(){
	?>
	<table>
		<thead>
			<th>
				IPTC
			</th>
			<th>
				EXIF
			</th>
		</thead>
		<tbody>
			<tr>
				<td>
					<table class="widefat">
						<thead>
							<th>
								Tag
							</th>
							<th>
								Alternative
							</th>
						</thead>
						<tbody>
							<tr>
								<td>
									IPTC:ObjectName
								</td>
								<td>
									IPTC:2.005
								</td>
							</tr>
						</tbody>
					</table>
				</td>
				<td>
					<table class="widefat">
						<thead>
							<th>
								Tag
							</th>
							<th>
								Alternative
							</th>
						</thead>
						<tbody>
							<tr>
								<td>
									EXIF:InteropIndex
								</td>
								<td>
									EXIF:0x0001
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	
	
	<?php
}


///////////////////////////////////
// Options callbacks
///////////////////////////////////

// copy or move setting content
function mc_title_cb(){cb('title');}

// copy or move setting content
function mc_alt_cb(){cb('alt');}

// copy or move setting content
function mc_caption_cb(){cb('caption');}

// copy or move setting content
function mc_description_cb(){cb('description');}

function cb($key)
{
	$options = get_option('mc');
	?>
	<input type="text" id="mc[<?php echo $key; ?>]" name="mc[<?php echo $key; ?>]" value="<?php echo $options[$key]; ?>" />
	<?php
}

function mc_validator($input)
{
	// validate
	return $input;
}











?>