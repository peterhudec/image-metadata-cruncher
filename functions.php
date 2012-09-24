<?php
require 'mappings.php';

// globals
$metadata = NULL;

function parse_meta($file)
{
	// extract metadata from file
	$size = getimagesize($file, $metadata);
	
	// parse iptc
	$iptc = iptcparse($metadata['APP13']);
	foreach ($iptc as &$i) {
		$i = $i[0];
	}
	
	// parse exif
	$exif = exif_read_data($file);
	
	global $metadata;
	$metadata = array('size' => $size, 'IPTC' => $iptc, 'EXIF' => $exif);
	return $metadata;
}


function get_meta_by_key($tag, $delimiter=NULL){
    global $metadata, $IPTC_MAPPING, $EXIF_MAPPING;
    $delimiter = $delimiter ? $delimiter : ', ';
        
	$value = $key = NULL;
	
	$pieces = explode(':', $tag);
	$category = $pieces[0];
	if(count($pieces) > 1) $path = explode('.', $pieces[1]);
		
	if ($category == 'IPTC') {
		// construct IPTC key in form '0#000'
		
		// if keyword used e.g. "IPTC:FileFormat"
		$key = array_search($pieces[1], $IPTC_MAPPING);
		
		// else parse path
		if(!$key){
			if (count($path) == 1) {
				// if only one number specified, fall back to "[n]#000"
				$key = sprintf("%d#000", $path[0]);
			} else {
				// else build key
				$key = sprintf("%d#%03d", $path[0], $path[1]);
			}
			
		}
		
		// get value from IPTC
		$value = isset($metadata['IPTC'][$key]) ? $metadata['IPTC'][$key] : NULL;
			
	} elseif ($category == 'EXIF') {
		// try to get value directly
		$key = $path[0];
		$value = isset($metadata['EXIF'][$key]) ? $metadata['EXIF'][$key] : NULL;
		
		if(!$value){
			// if no value returned try looking up for "UndefinedTag:0x####"
			$key = strtoupper(dechex(intval($key, 16)));
			$key = "UndefinedTag:0x$key";
			$value = isset($metadata['EXIF'][$key]) ? $metadata['EXIF'][$key] : NULL;
		}
		
		if(!$value){
			// if still no success try again but lookup for the hex ID in the $EXIF_MAPPING
			$key = $path[0];
			$key = intval(array_search($key, $EXIF_MAPPING), 16);
			$key = strtoupper(dechex($key));
			$key = "UndefinedTag:0x$key";
			$value = isset($metadata['EXIF'][$key]) ? $metadata['EXIF'][$key] : NULL;
		}
		
	}
	
	if (is_array($value)) {
		if(isset($path[1])){
			// if array index specified
			$value = isset($value[$path[1]]) ? $value[$path[1]] : NULL;
		}else{
			$value = implode($delimiter, $value);
		}
		
	}
	
	return $value;
}


function render_template($template){
	$pattern = '/
    {\s*(
    (?: \s* [\w:.]+ \s* \|? )+
    (?: \?\s*["\'][^"\']*["\']\s* )?
    (?: :\s*["\'][^"\']*["\']\s* )?
    )\s*}
    /x';
    
    $pattern = '/{([^{}]*)}/';
    
	// replace tags with data
	$res = preg_replace_callback($pattern, function($match){
	    return parse_tag($match[1]);
	}, $template);
	
    $res = $res ? $res : $template;    
        
	// handle escaped curly brackets
	return str_replace(array('\{', '\}'), array('{', '}'), $res);
}

function parse_tag($tag)
{
	$pattern = '/
    (?:
        (?:
            ^ | \|                  # leading pipe (|) or beginning of string = KEYWORD
        )
        \s*                         # allow whitespace
        (?P<keywords> [\w:.]+ )     # capture KEYWORD
        \s*                         # allow whitespace
    )
    (?:
        \?                          # leading question mark (?) = DEFAULT
        \s*                         # allow whitespace
        ["\']                       # require double or single quote
        (?P<default> [^"\']+ )      # capture DEFAULT
        ["\']                       # require double or single quote
        \s*                         # allow whitespace
    )?
    (?:
        :                           # leading colon (:) = DELIMITER
        \s*                         # allow whitespace
        ["\']                       # require double or single quote
        (?P<delimiter> [^"\']+ )    # capture DELIMITER
        ["\']                       # require double or single quote
        \s*                         # allow whitespace
    )?
    /x';
    
    $m = preg_match_all($pattern, $tag, $match);
    
    $keywords = array_filter($match['keywords']);
    $default = array_pop(array_filter($match['default']));
    $delimiter = array_pop(array_filter($match['delimiter']));
    
    //print "def = $default\ndel = $delimiter\n------------------\n";
    
    // loop through keywords and return after success
    $meta;
    $result = $default;
    foreach ($keywords as $keyword) {
        $meta = get_meta_by_key($keyword, $delimiter);
        
        if($meta){
            $result = $meta;
            break;
        }
    }
        
    return $result;
    // print "Meta: $result\n";
    
}

function validate_tag($tag)
{
	$pattern = '/
    {\s*(
    (?: \s* [\w:.]+ \s* \|? )+
    (?: \?\s*["\'][^"\']*["\']\s* )?
    (?: :\s*["\'][^"\']*["\']\s* )?
    )\s*}
    /x';
    
    preg_match($pattern, $tag, $m);
    
    $match = isset($m[0]) ? $m[0] : "NO MATCH!";
    
    return "Tag: $tag\nMatch: $match\n\n";
}

function test()
{
	
	
	$meta = parse_meta('pokus.jpg');
	
	print render_template('Pokus { Kokotina | IPTC:HeadlineX | EXIF:LensInfo ? "neni meta" : "; " },  M = {IPTC:Headline}');
    
   /*
    print validate_tag('Bla { EXIF:LensInfo.2 | IPTC:2.105 | EXIF:0xa432.0 ? "default: text" : "delimiter" } bla.');
    print validate_tag('Bla {EXIF:LensInfo.2|IPTC:2.105|EXIF:0xa432.0?"default: text":"delimiter"} bla.');
    print validate_tag('Bla { EXIF:LensInfo.2 | IPTC:2.105 | EXIF:0xa432.0 ? "default text" } bla.');
    print validate_tag('Bla { EXIF:LensInfo.2 | IPTC:2.105 | EXIF:0xa432.0 : "only delimiter" } bla.');
    */
    
        
	//print "\n------------------\n";
	//print render_template('Prazdny = {},  Neznamy = {keket}, \{ kaar }');
	
	//print render_template($meta, 'LensInfo.2 = {EXIF:LensInfo.2}');
	//print render_template($meta, 'IPTC:2.105 = {IPTC:2.105}');
	//print render_template($meta, 'EXIF:0xa432.0 = {EXIF:0xa432.0}');
	
	/*
	get_meta_by_tag($meta, 'IPTC:Headline');
	get_meta_by_tag($meta, 'IPTC:2.105');
	get_meta_by_tag($meta, 'IPTC:1');
	get_meta_by_tag($meta, 'IPTC:2.103');
	get_meta_by_tag($meta, 'IPTC:1.1234');
	get_meta_by_tag($meta, 'EXIF:FileDateTime');
	get_meta_by_tag($meta, 'EXIF:Model');
	get_meta_by_tag($meta, 'EXIF:FNumber');
	get_meta_by_tag($meta, 'EXIF:SerialNumber');
	get_meta_by_tag($meta, 'EXIF:LensInfo');
	get_meta_by_tag($meta, 'EXIF:LensInfo.3');
	get_meta_by_tag($meta, 'EXIF:LensInfo.2');
	get_meta_by_tag($meta, 'EXIF:LensInfo.1');
	get_meta_by_tag($meta, 'EXIF:LensInfo.0');
	get_meta_by_tag($meta, 'EXIF:0xa432.0');
	*/
}
//test();

?>