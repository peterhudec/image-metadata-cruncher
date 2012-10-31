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


/**
 * Main plugin class
 */
class Image_Metadata_Cruncher_Plugin {
	
	// stores metadata between wp_handle_upload_prefilter and add_attachment hooks
	private $metadata;
	private $keyword;
	private $keywords;
	private $pattern;
	
	private $IPTC_MAPPING = array(
		'1#000' => 'EnvelopeRecordVersion',
		'1#005' => 'Destination',
		'1#020' => 'FileFormat',
		'1#022' => 'FileVersion',
		'1#030' => 'ServiceIdentifier',
		'1#040' => 'EnvelopeNumber',
		'1#050' => 'ProductID',
		'1#060' => 'EnvelopePriority',
		'1#070' => 'DateSent',
		'1#080' => 'TimeSent',
		'1#090' => 'CodedCharacterSet',
		'1#100' => 'UniqueObjectName',
		'1#120' => 'ARMIdentifier',
		'1#122' => 'ARMVersion',
		'2#000' => 'ApplicationRecordVersion',
		'2#003' => 'ObjectTypeReference',
		'2#004' => 'ObjectAttributeReference',
		'2#005' => 'ObjectName',
		'2#007' => 'EditStatus',
		'2#008' => 'EditorialUpdate',
		'2#010' => 'Urgency',
		'2#012' => 'SubjectReference',
		'2#015' => 'Category',
		'2#020' => 'SupplementalCategories',
		'2#022' => 'FixtureIdentifier',
		'2#025' => 'Keywords',
		'2#026' => 'ContentLocationCode',
		'2#027' => 'ContentLocationName',
		'2#030' => 'ReleaseDate',
		'2#035' => 'ReleaseTime',
		'2#037' => 'ExpirationDate',
		'2#038' => 'ExpirationTime',
		'2#040' => 'SpecialInstructions',
		'2#042' => 'ActionAdvised',
		'2#045' => 'ReferenceService',
		'2#047' => 'ReferenceDate',
		'2#050' => 'ReferenceNumber',
		'2#055' => 'DateCreated',
		'2#060' => 'TimeCreated',
		'2#062' => 'DigitalCreationDate',
		'2#063' => 'DigitalCreationTime',
		'2#065' => 'OriginatingProgram',
		'2#070' => 'ProgramVersion',
		'2#075' => 'ObjectCycle',
		'2#080' => 'By-line',
		'2#085' => 'By-lineTitle',
		'2#090' => 'City',
		'2#092' => 'Sub-location',
		'2#095' => 'Province-State',
		'2#100' => 'Country-PrimaryLocationCode',
		'2#103' => 'Country-PrimaryLocationName',
		'2#103' => 'OriginalTransmissionReference',
		'2#105' => 'Headline',
		'2#110' => 'Credit',
		'2#115' => 'Source',
		'2#116' => 'CopyrightNotice',
		'2#118' => 'Contact',
		'2#120' => 'Caption-Abstract',
		'2#121' => 'LocalCaption',
		'2#122' => 'Writer-Editor',
		'2#125' => 'RasterizedCaption',
		'2#130' => 'ImageType',
		'2#131' => 'ImageOrientation',
		'2#135' => 'LanguageIdentifier',
		'2#150' => 'AudioType',
		'2#151' => 'AudioSamplingRate',
		'2#152' => 'AudioSamplingResolution',
		'2#153' => 'AudioDuration',
		'2#154' => 'AudioOutcue',
		'2#184' => 'JobID',
		'2#185' => 'MasterDocumentID',
		'2#186' => 'ShortDocumentID',
		'2#187' => 'UniqueDocumentID',
	);
	
	private $EXIF_MAPPING = array(
		0x0001 => 'InteropIndex',
		0x0002 => 'InteropVersion',
		0xa432 => 'LensInfo',
		0xa431 => 'SerialNumber',
		0x8830 => 'SensitivityType'
	);
	
	/**
	 * Constructor
	 */
	function __construct() {
		
		$this->patterns();
		
		// hooks
		
		// plugin settings hooks
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta',  array( $this, 'plugin_row_meta' ), 10, 2 );
		register_activation_hook( __FILE__, array( $this, 'defaults' ) );
		add_action('admin_init', array( $this, 'init' ) );
		add_action('admin_menu', array( $this, 'options' ) );
		
		// plugin functionality hooks
		add_action( 'wp_handle_upload_prefilter', array( $this, 'upload' ) );
		add_action( 'add_attachment', array( $this, 'add_attachment' ) );
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////////
	// Functionality
	/////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * The wp_handle_upload_prefilter hook gets triggered before
	 * wordpress erases all the image metadata
	 */
	public function upload( $file ){
		// get meta
		$this->metadata = $this->parse_meta($file['tmp_name']);
		
		// return untouched file
		return $file;
	}
	
	/**
	 * The add_attachment hook gets triggered when the attachment post is created
	 * in Wordpress media uploads are handled as post
	 */
	public function add_attachment( $post_ID ){
		$options = get_option( $this->prefix );
		
		$post = get_post($post_ID);
		
		// title
		$post->post_title = $this->render_template($options['title']);
		// caption
		$post->post_excerpt = $this->render_template($options['caption']);
		// description
		$post->post_content = $this->render_template($options['description']);
		// alt is meta attribute
		update_post_meta($post_ID, '_wp_attachment_image_alt', $this->render_template($options['alt']));
		
		// add custom metadata
		foreach ($options['custom_meta'] as $key => $value) {
		    // update or create
		    $value = $this->render_template($value);
			add_post_meta($post_ID, $key, $value, true) or update_post_meta($post_ID, $key, $value);
		}
		
		wp_update_post( $post );
	}
	
	/**
	 * returns a structured array with all available metadata of supplied image
	 */
	private function parse_meta( $file ) {
		// extract metadata from file
		$size = getimagesize( $file, $meta );
		
		// parse iptc
		$iptc = iptcparse( $meta[ 'APP13' ] );
		foreach ( $iptc as &$i ) {
			// symplify array structure
			$i = $i[0];
		}
		
		// parse exif
		$exif = exif_read_data( $file );
		
		// construct the metadata array
		$this->metadata = array(
			'size' => $size,
			'IPTC' => $iptc,
			'EXIF' => $exif
		);
		
		// no need to return but good for testing
		return $this->metadata;
	}
	
	/**
	 * Replaces tags in template string with actual metadata if found
	 */
	private function render_template( $template ){
		
		// restore escaped characters
		$template = str_replace( 
			array(
				'&gt;',
				'&#039;',
				'&quot;'
			),
			array(
				'>',
				"'",
				'"'
			),
			$template
		);
		
		// replace each found tag with result of the replace_parse_tag method
		$res = preg_replace_callback($this->pattern, array($this, 'parse_tag'), $template);
		
	    $res = $res ? $res : $template;    
	        
		// handle escaped curly brackets
		$res = str_replace(array('\{', '\}'), array('{', '}'), $res);
		
		return $res;
	}
	
	/**
	 * Returns recursive copy of an array with 
	 */
	private function array_keys_to_lower_recursive( $array ) {
		$array = array_change_key_case( $array, CASE_LOWER );
		foreach ($array as $key => $value) {
			if ( is_array( $value ) ) {
				$array[$key] = $this->array_keys_to_lower_recursive( $value );
			}
		}
		return $array;
	}
	
	
	private function get_metadata( $metadata, $category, $key ) {
		$category = strtolower( $category );
		$key = strtolower( $key );
		if ( isset( $metadata[$category][$key] ) ) {
			return $metadata[$category][$key];
		}
	}
	
	/**
	 * Searches the $this->metadata property for a metadata field key
	 * e.g.: IPTC:Caption, IPTC:2.15, EXIF:SerialNumber, EXIF:LensInfo.1
	 */
	private function get_meta_by_key( $key, $delimiter = NULL ){
		// convert metadata keys to lowercase to allow for case insensitive keys
		$metadata = $this->array_keys_to_lower_recursive( $this->metadata );
		
		if ( ! $delimiter ) {
	    	// default delimiter for array values to be joined with
	    	$delimiter = ', ';
	    }
				
		// parse key
		$pieces = explode(':', $key);
		
		// get case insensitive metadata category: "ALL", "EXIF" or "IPTC"
		$category = strtolower( $pieces[0] );
		
		if ( count( $pieces ) > 1 ) {
			// parse path pieces separated by dot
			$path = explode( '>', $pieces[1] );
		} else {
			// tag is not valid without anything after colon e.g. "EXIF:"
			return; // exit and return nothing			
		}
		
		// start search
		$value = $key = NULL;
		
		if ( $category == 'all' ) {
				
			$value = $this->explore_path( $this->metadata, $path, $delimiter );
			
			// returns all metadata structured according to key
			switch ( strtolower( $path[0] )  ) {
				case 'php':
					//return print_r( $this->metadata, TRUE );
					return print_r( $value, TRUE );
					break;
					
				case 'json':
					return json_encode( $value );
					break;
					
				case 'xml':
					// not implemented yet
					break;
				
				default:
					break;
			}
		} elseif ( $category == 'iptc' ) {
			// if key starts with "IPTC"
			
			// search for named keyword in the IPTC mapping e.g. "IPTC:FileFormat"
			$key = array_search( strtolower( $pieces[1] ), array_map( strtolower, $this->IPTC_MAPPING ));
			
			// if nothing found search for IPTC by code e.g. "IPTC:2#025"...
			if( !$key ) {
				
				if ( count( $path ) == 1 ) {
						
					// if IPTC part is in n#nnn form this will get its value if set
					$value = $this->get_metadata( $metadata, $category, $path[0] );
					
					// if nothing found check the n>nnn form
					if( ! $value ) {
						// if only one number specified, fallback to "[n]#000" e.g. "IPTC:1" becomes "IPTC:1#000"
						$key = sprintf("%d#000", $path[0]);
						$value = $this->get_metadata( $metadata, $category, $key );
					}
					
				} else {
					
					// else pad leading zeros if missing e.g. "IPTC:2.5" becomes "IPTC:2#005"
					$key = sprintf("%d#%03d", $path[0], $path[1]);
					
				}
			}
			
			$value = $this->get_metadata( $metadata, $category, $key );
				
		} elseif ( $category == 'exif' ) {
			// if key starts with "EXIF" e.g. {EXIF:whatever.whateverelse}
			
			// key is the first part of the path
			$key = $path[0];
			
			// try to fin value directly in the keys returned by exif_read_data() function
			// e.g. {EXIF:Model}
			$value = $this->get_metadata( $metadata, $category, $key );
			
			if( ! $value ){
				// some EXIF tags are returned by the exif_read_data() functions like "UndefinedTag:0x####"
				// so if nothing found try looking up for "UndefinedTag:0x####"
				
				// since we need an uppercase hex number e.g. 0xA432 but with lowercase 0x part
				//  we convert the key to base 16 integer and then back to uppercase string
				$key = strtoupper( dechex( intval( $key, 16 ) ) );
				
				// construct the "UndefinedTag:0x####" key and search for it in the extracted metadata
				$key = "UndefinedTag:0x$key";
				$value = $this->get_metadata( $metadata, $category, $key );
			}
			
			if( ! $value ){
				// if still no success try again but lookup for the hex ID in the $EXIF_MAPPING
				
				// reset key to the first part of the path
				$key = $path[0];
				
				// find the appropriate EXIF hex code in the mapping...
				$key = array_search( strtolower( $key ), array_map( strtolower, $this->EXIF_MAPPING ));
				// ...and convert to base 16 integer
				$key = intval( $key , 16 );
				
				// convert to uppercase string
				$key = strtoupper( dechex( $key ) );
				
				// construct key
				$key = "UndefinedTag:0x$key";
				
				// and search for it in the extracted metadata
				$value = $this->get_metadata( $metadata, $category, $key );
			}
			$value = $this->explore_path( $value, $path, $delimiter );
		} else {
			// try to find anything that is provided
			$value = $this->get_metadata( $metadata, $category, $pieces[1] );
			$value = $this->explore_path( $value, $path, $delimiter );
		}
		
		if ( is_array( $value ) ) {
			$value = implode( $delimiter, $value );
		}
		
		return $value;
	}
	
	private function explore_path( $value, $path, $delimiter, $index = 0 ) {
		// if value is array
		if ( is_array( $value ) ) {
			$index++;
			if ( isset( $path[ $index ] ) ) {
				// if index set in the path, get its value
				
				// temporarily convert value and path to lowercase to allow for key insensitive lookup 
				$value_lower = $this->array_keys_to_lower_recursive( $value );
				$path_lower = strtolower( $path[ $index ] );
				$value = $value_lower[ $path_lower ];
				
				// before returning check if there is not another part of the path
				return $this->explore_path( $value, $path, $delimiter, $index );
			} else {
				return $value;
			}
		} else {
			// if value is not an aray return it
			return $value;
		}
	}
	
	private function validate_tag ( $tag, $pattern ) {
		preg_match( $pattern, $tag, $match );
		if ( isset( $match[0] ) ) {
			return $match[0];
		}
	}
	
	private function u($value='')
	{
		
	}
	
	/**
	 * Gets the tag contents without curly braces e.g. 'IPTC:Caption | EXIF:Model ? "default" : "delimiter"'
	 * parses the tag and returns the value of the first succesful key or the specified default string
	 * if the found value is an array it returns its values joined with the specified delimiter
	 */
	private function parse_tag( $match ) {
		
		$keywords = isset( $match[ 'keywords' ] ) ? explode('|', $match[ 'keywords' ] ) : FALSE;
		$success = isset( $match[ 'success' ] ) ? $match[ 'success' ] : FALSE;
		$default = isset( $match[ 'default' ] ) ? $match[ 'default' ] : FALSE;
		$delimiter = isset( $match[ 'delimiter' ] ) ? $match[ 'delimiter' ] : FALSE;
			
	    // loop through keywords...
	    foreach ( $keywords as $keyword ) {
	    	
	    	// search for key in metadata extracted from the image
	        $meta = $this->get_meta_by_key( trim( $keyword ), $delimiter );
	        
	        if( $meta ) {
	        	// return first found meta
	        	if ( $success ) {
	        		return str_replace(
	        			array(
	        				'\$', // replace escaped dolar sign with some unusual character like: ⌨
	        				'$', // replace dolar signs for meta value
	        				'\"', // replace escaped doublequote for doublequote
	        				'⌨' // replace ⌨ to dolar sign
	        			),
	        			array(
	        				'⌨',
	        				$meta,
	        				'"',
	        				'$'
						),
	        			$success
					);
	        		return $res;
	        	} else {
	        		return $meta;
	        	}
	        }
	    }
		
		// if flow gets here nothing was found so return default
		if ( $default ){
			return $default;
		}else{
			return '';
		}
		
	}
	
	
	private function patterns() {
		
		// matches key in form abc:def(>ijk)*
		$this->keyword = '
			[\w]+ # caterory prefix
			: # colon
			[\w.#]+ # keyword first part
			(?: # zero or more keyword parts
				> # part delimiter
				[\w.#]+ # part
			)*
		';
		
		// matches keys in form key( | key)*
		$this->keywords = '
			'.$this->keyword.' # at least one key
			(?: # zero or more additional keys
				\s* # space
				\| # colon delimiter
				\s* # space
				'.$this->keyword.' # key
			)*
		';
		
		// matches tag in form { keys @ "success" % "default" # "identifier" }
		$this->pattern = '/
			{
			\s*
			(?P<keywords>'.$this->keywords.')
			\s*
			(?: # success
				@ # identifier
				\s* # space
				" # opening quote
				(?P<success> # capture value
					(?: # must contain
						\\\\" # either escaped doublequote \"
						| # or
						[^"] # any non doublequote character
					)* # zero or more times
				)
				" # closing quote
			)?
			\s*
			(?: # default
				% # identifier
				\s* # space
				" # opening quote
				(?P<default> # capture value
					(?: # must contain
						\\\\" # either escaped doublequote \"
						| # or
						[^"] # any non doublequote character
					)* # zero or more times
				)
				" # closing quote
			)?
			\s*
			(?: # delimiter
				\# # identifier
				\s* # space
				" # opening quote
				(?P<delimiter> # capture value
					(?: # must contain
						\\\\" # either escaped doublequote \"
						| # or
						[^"] # any non doublequote character
					)* # zero or more times
				)
				" # closing quote
			)?
			\s*
			}
		/x';
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////////
	// Settings
	/////////////////////////////////////////////////////////////////////////////////////
	
	private $settings_slug = 'mc-options';
	private $donate_url = 'http://www.paypal.com';
	private $option_name = 'mc';
	public $prefix = 'image_metadata_cruncher';
	
	/**
	 * Adds action links to the plugin
	 */
	public function plugin_action_links( $links, $file ) {
		
	    static $this_plugin;
	    if ( ! $this_plugin ) {
	        $this_plugin = plugin_basename( __FILE__ );
	    }
		
	    if ( $file == $this_plugin ) {
	    	$url = get_bloginfo( 'wpurl' ) . "/wp-admin/admin.php?page=$this->settings_slug";
	        $settings_link = "<a href=\"$url\">Settings</a>";
	        array_unshift( $links, $settings_link );
	    }
		
	    return $links;
	}
	
	/**
	 * Adds action links to the plugin row
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$url = get_bloginfo( 'wpurl' ) . "/wp-admin/admin.php?page=$this->settings_slug";
	        $links[] = "<a href=\"$url\">Settings</a>";
			$links[] = "<a href=\"$this->donate_url\">Donate</a>";
		}
		return $links;
	}
	
	/**
	 * js and css
	 */
	function js_rangy_core() { wp_enqueue_script( "{$this->prefix}_rangy_core" ); }
	function js_rangy_selectionsaverestore() { wp_enqueue_script( "{$this->prefix}_rangy_selectionsaverestore" ); }
	function js() { wp_enqueue_script( "{$this->prefix}_script" ); }
	function js_highlighting() { wp_enqueue_script( "{$this->prefix}_highlighting" ); }
	function css() { wp_enqueue_style( "{$this->prefix}_style" ); }
	
	/**
	 * Default plugin options
	 */
	public function defaults(){
		add_option( $this->option_name, array(
			'title' => '',
			'alt' => '',
			'caption' => '',
			'description' => '',
			'file_url' => '',
			'image_size' => '',
			'custom_meta' => array()
		) );
	}
	
	/**
	 * Adds section to plugin admin page
	 */
	private function section( $id, $title ) {
		add_settings_section(
			"{$this->prefix}_section_{$id}", // section id
			$title, // title
			array( $this, "section_{$id}" ), // callback
			"{$this->prefix}-section-{$id}"); // page
	}
	
	/**
	 * Plugin initialization
	 */
	public function init() {
	    
	    // register stylesheet and script for admin
	    //js_rangy
	    wp_register_script( "{$this->prefix}_rangy_core", plugins_url( 'rangy-core.js', __FILE__ ) );
	    wp_register_script( "{$this->prefix}_rangy_selectionsaverestore", plugins_url( 'rangy-selectionsaverestore.js', __FILE__ ) );
	    wp_register_script( "{$this->prefix}_script", plugins_url( 'script.js', __FILE__ ) );
	    wp_register_script( "{$this->prefix}_highlighting", plugins_url( 'highlighting.js', __FILE__ ) );
	    wp_register_style( "{$this->prefix}_style", plugins_url( 'style.css', __FILE__ ) );
	    
	    ///////////////////////////////////
	    // Sections
	    ///////////////////////////////////
	    $this->section( 1, 'Media form fields:' );
	    $this->section( 2, 'Custom image meta tags:' );
	    $this->section( 3, 'Available metadata keywords:' );
	    $this->section( 4, 'Usage:' );
	    $this->section( 5, 'About Image Metadata Cruncher:' );
	    
	    ///////////////////////////////////
	    // Options
	    ///////////////////////////////////
	    
	    // Title
	    // register a new setting...
	    register_setting(
	        "{$this->prefix}_title",         		// option group
	        $this->prefix,               		// option name
	        array( $this, 'sanitizator' )	// sanitizator
	    );              
	    // ...and add it to a section
	    add_settings_field(
	        "{$this->prefix}_title",         		// field id
	        'Title:',           		// title
	        array( $this, 'title_cb' ), // callback
	        "{$this->prefix}-section-1",     		// section page
	        "{$this->prefix}_section_1");    		// section id
	    
	    // Alternate text
	    register_setting(
	        "{$this->prefix}_alt",           		// option group
	        $this->prefix,               		// option name
	        array( $this, 'sanitizator' ) // sanitizator
	    );
	    add_settings_field(
	        "{$this->prefix}_alt",           		// field id
	        'Alternate text:',          // title
	        array( $this, 'alt_cb' ),   // callback
	        "{$this->prefix}-section-1",     		// section page
	        "{$this->prefix}_section_1");    		// section id
	    
	    // Caption
	    register_setting(
	        "{$this->prefix}_caption",       		// option group
	        $this->prefix,               		// option name
	        array( $this, 'sanitizator' ) // sanitizator
	    );
	    add_settings_field(
	        "{$this->prefix}_caption",       		// field id
	        'Caption:', 				// title
	        array( $this, 'caption_cb' ),	// callback
	        "{$this->prefix}-section-1",     			// section page
	        "{$this->prefix}_section_1");    			// section id
	    
	    // Description
	    register_setting(
	        "{$this->prefix}_description",   			// option group
	        $this->prefix,               			// option name
	        array( $this, 'sanitizator' )     // sanitizator
	    );
	    add_settings_field(
	        "{$this->prefix}_description",   			// field id
	        'Description:',     			// title
	        array( $this, 'description_cb' ),	// callback
	        "{$this->prefix}-section-1",     				// section page
	        "{$this->prefix}_section_1");    				// section id
	}
	
	/**
	 * Plugin options callback
	 */
	public function options() {
		$page = add_plugins_page(
			'Metadata Cruncher',
			'Metadata Cruncher',
			'administrator',
			"{$this->prefix}-options",
			array( $this, 'options_cb' )
		);
		add_action( 'admin_print_scripts-' . $page, array( $this, 'js_rangy_core' ) );
		add_action( 'admin_print_scripts-' . $page, array( $this, 'js_rangy_selectionsaverestore' ) );
		add_action( 'admin_print_scripts-' . $page, array( $this, 'js_highlighting' ) );
	    add_action( 'admin_print_scripts-' . $page, array( $this, 'js' ) );
	    add_action( 'admin_print_styles-' . $page, array( $this, 'css' ) );
	}
	
	/**
	 * Options page callback
	 */
	public function options_cb() { ?>
		<div id="metadata-cruncher" class="wrap metadata-cruncher">
			<h2>Image Metadata Cruncher Options</h2>
			<?php settings_errors(); ?>
			<h2 class="nav-tab-wrapper">
				<?php
					if ( isset( $_GET[ 'tab' ] ) ) {
						$active_tab = $_GET[ 'tab' ];
					} else {
						$active_tab = 'settings';
					}
					
					function active_tab( $value, $at  ) {
						if ( $at == $value ) {
							echo 'nav-tab-active';
						}
					}
				?>
				<a href="?page=image_metadata_cruncher-options&tab=settings" class="nav-tab <?php active_tab( 'settings', $active_tab ); ?>">Settings</a>
				<a href="?page=image_metadata_cruncher-options&tab=metadata" class="nav-tab <?php active_tab( 'metadata', $active_tab ); ?>">Available Metadata</a>
				<a href="?page=image_metadata_cruncher-options&tab=usage" class="nav-tab <?php active_tab( 'usage', $active_tab ); ?>">Usage</a>
				<a href="?page=image_metadata_cruncher-options&tab=about" class="nav-tab <?php active_tab( 'about', $active_tab ); ?>">About</a>
			</h2>
			<form action="options.php" method="post">
				<?php
					settings_fields( "{$this->prefix}_title" ); // renders hidden input fields
					settings_fields( "{$this->prefix}_alt" ); // renders hidden input fields
					if ( $active_tab == 'settings' ) {
						do_settings_sections( "{$this->prefix}-section-1" );
						do_settings_sections( "{$this->prefix}-section-2" );
						submit_button();
					} elseif ( $active_tab == 'metadata' ) {
						do_settings_sections( "{$this->prefix}-section-3" );
					} elseif ( $active_tab == 'usage' ) {
						do_settings_sections( "{$this->prefix}-section-4" );
					} elseif ( $active_tab == 'about' ) {
						do_settings_sections( "{$this->prefix}-section-5" );
					}
				?>
			</form>
		</div>
	<?php }
	
	///////////////////////////////////
    // Section callbacks
    ///////////////////////////////////
    
    // media form fields
    public function section_1() { ?>
		<p>
		    Specify texts with which should the media upload form be prepopulated with.
		    You can use metadata tags inside curly brackets<code>{}</code>.
		    <br />
		    <strong>Example:</strong> <code>Image was taken with {EXIF:Model} camera.</code>
		</p>
	<?php }
	
	// custom post metadata
	public function section_2() { ?>
	    <?php $options = get_option( $this->prefix ); ?>
		<i>You can also specify your own meta fields that will be saved to the database with the picture.</i>
		<table id="custom-meta-list" class="widefat">
			<colgroup>
				<col class="col-name" />
				<col class="col-template" />
				<col class="col-delete" />
			</colgroup>
			<thead>
				<th>Name</th>
				<th>Template</th>
				<th>Delete</th>
			</thead>
			<?php foreach ($options['custom_meta'] as $key => $value): ?>
				<tr>
	                <td><input type="text" class="name" value="<?php echo $key ?>" /></td>
	                <td>
	                	<div class="ce" contenteditable="true"><?php echo $value ?></div>
	                	<?php // used textarea because hidden input caused bugs when whitespace got converted to &nbsp; ?>
	                	<textarea class="hidden-input template" name="<?php echo $this->prefix; ?>[custom_meta][<?php echo $key ?>]" ><?php echo $value ?></textarea>
	                </td>
	                <td><button class="button">Remove</button></td>
				</tr>
			<?php endforeach ?>
		</table>
		<div>
			<button id="add-custom-meta" class="button">Add New Field</button>
		</div>	
	<?php }
	
	
	// list of available metadata tags
	public function section_3() { ?>
		
		<h2>EXIF</h2>
		<div class="tag-list exif">
			<?php foreach ($this->EXIF_MAPPING as $key => $value): ?>
				<span class="tag">
					<span class="first">
						<span class="prefix">EXIF</span><span class="colon">:</span><span class="part"><?php echo $value; ?></span>						
					</span>
					or
					<span class="second">
						<span class="prefix">EXIF</span><span class="colon">:</span><span class="part"><?php echo sprintf("0x%04x", $key); ?></span>
					</span>
				</span>
			<?php endforeach ?>
		</div>
		
		<h2>IPTC</h2>
		<div class="tag-list iptc">
			<?php foreach ($this->IPTC_MAPPING as $key => $value): ?>
				<?php 
					$parts = explode('#', $key);
					$part1 = $parts[0];
					$part2 = intval($parts[1]);
				?>
				<span class="tag">
					<span class="first">
						<span class="prefix">IPTC</span><span class="colon">:</span><span class="part"><?php echo $value; ?></span>						
					</span>
					or
					<span class="second">
						<span class="prefix">IPTC</span><span class="colon">:</span><span class="part"><?php echo $key; ?></span>
					</span>
					or
					<span class="third">
						<span class="prefix">IPTC</span><?php
						?><span class="colon">:</span><?php
						?><span class="part"><?php echo $part1; ?></span><?php
						if ( $part2 ):
						?><span class="gt">&gt;</span><?php
						?><span class="part"><?php echo $part2; ?><?php
						endif ?></span>
					</span>
				</span>
			<?php endforeach ?>
		</div>
		
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
	<?php }
	
	// usage
	public function section_4()	{ ?>
		<p>Usage</p>
	<?php }
	
	// about
	public function section_5()	{ ?>
		<p>Created by <strong>Peter Hudec</strong>, <a href="http://peterhudec.com" target="_blank">peterhudec.com</a>.</p>
	<?php }
		
	///////////////////////////////////
    // Options callbacks
    ///////////////////////////////////
    
    /**
	 * General callback for media form fields
	 */
	private function cb( $key ) { ?>
		<?php $options = get_option( $this->prefix ); ?>
		<div class="ce" contenteditable="true"><?php echo $options[$key]; ?></div>
		<?php // used textarea because hidden input caused bugs when whitespace got converted to &nbsp; ?>
		<textarea class="hidden-input" id="<?php echo $this->prefix; ?>[<?php echo $key; ?>]" name="<?php echo $this->prefix; ?>[<?php echo $key; ?>]"><?php echo $options[$key]; ?></textarea>
	<?php }
	
	public function title_cb() { $this->cb( 'title' ); }
	
	public function alt_cb() { $this->cb( 'alt' ); }
	
	public function caption_cb() { $this->cb( 'caption' ); }
	
	public function description_cb() { $this->cb( 'description' ); }
	
	public function sanitizator( $input ) {
				
		$output = array();
		
		// iterate through options array
		foreach ( $input as $key => $value ) {
			
			if ( is_array( $value ) ) {
				// if is array iterate over it...
				
				$output[$key] = array();
				foreach ( $value as $k => $v ) {
					// ...and sanitize both key and value
					$output[$key][esc_attr( $k )] = esc_attr( $v );
				}
				
			} else {
				// sanitize value
				$output[$key] = esc_attr( $value );
			}
		}
		return $output;
	}
}

// instantiate the plugin
$image_metadata_cruncher_plugin = new Image_Metadata_Cruncher_Plugin();

?>