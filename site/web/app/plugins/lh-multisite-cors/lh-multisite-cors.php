<?php
/**
* Plugin Name: LH Multisite CORS
* Plugin URI: https://lhero.org/portfolio/lh-multisite-cors/
* Version: 1.02
* Author: Peter Shaw
* Author URI: https://shawfactor.com
* Description: Sets the CORS allow headers for requests between sites within your multisite network
* License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* LH Multisite CORS Class
*/

if (!class_exists('LH_multisite_cors_plugin')) {

class LH_multisite_cors_plugin {
    
    private static $instance;
    
    static function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(plugin_basename( __FILE__ ).' - '.print_r($log, true));
            } else {
                error_log(plugin_basename( __FILE__ ).' - '.$log);
            }
        }
    }
    
    static function isValidURL($url){ 
        
        if (empty($url)){
            
            return false;
            
        } else {
    
            return (bool)parse_url($url);
    
        }
    }
    

static function check_blog($blog){
    



$args = array(
     'domain' => $blog,
    );
    
$sites = get_sites( $args );

//self::write_log('the sites are');
//self::write_log($the_sites);


if (!empty($sites[0]->domain)){

return true;

} else {

return false;


}

}

static function check_mapped_domain($mapped){

global $wpdb;

if (empty($wpdb->dmtable)){

// Define the custom table
$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';

}

// Check if the custom table exists
if ( $wpdb->dmtable != $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->dmtable}'" ) ){
    return false;
    
}

$sql = "SELECT a.domain FROM ".$wpdb->dmtable." a, ".$wpdb->blogs." b where a.blog_id = b.blog_id and a.domain = '".$mapped."'";

//self::write_log($sql);

$domain = $wpdb->get_results($sql);

if (isset($domain[0]->domain)){

return true;

} else {

return false;


}

}

public function add_header() {

  if (isset($_SERVER['HTTP_REFERER'])){

	$referrer = $_SERVER['HTTP_REFERER'];
	

$bits = parse_url($referrer);

	if (isset($bits['host'])){ $referrer_domain = $bits['host']; }
	if (isset($bits['scheme'])){ $referrer_scheme = $bits['scheme']; }
 
  }


  if (isset($referrer_domain) and isset($referrer_scheme)){

if (isset($_SERVER['HTTP_ORIGIN'])){
  
$origin = $_SERVER['HTTP_ORIGIN'];

}

$pieces = parse_url(site_url());

if (isset($pieces['host'])){ $site_domain = $pieces['host']; } 
if (isset($pieces['scheme'])){ $site_scheme = $pieces['scheme']; }

if (empty($referrer_scheme) or empty($site_scheme) or ($referrer_scheme != $site_scheme)){

//The protocols don't match
return;

} elseif (empty($site_domain) or empty($referrer_domain) or ($site_domain == $referrer_domain)){

//the referrer is part of the same domain
return;


} elseif (self::check_blog($referrer_domain) or self::check_mapped_domain($referrer_domain)){

//It is is part of the multisite
header("Access-Control-Allow-Origin:".$referrer_scheme."://".$referrer_domain); 
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS,PUT,DELETE');
header('Access-Control-Allow-Headers: *');

if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])){

 header('Access-Control-Allow-Headers: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);

 
 }

		if ( 'OPTIONS' == $_SERVER['REQUEST_METHOD'] ) {
			status_header(200);
			exit();
		}




} else {

//No match
return;


}
  }

}

public function maybe_remove_sandbox($html, $url, $attr) {
    
    libxml_use_internal_errors(true);  
    
    $dom = new DOMDocument;

    $dom->loadHTML( mb_convert_encoding('<html><body>'.$html.'</body></html>', 'HTML-ENTITIES', 'UTF-8'),  LIBXML_HTML_NODEFDTD );

    $iframes = $dom->getElementsByTagName('iframe');
 
        foreach ($iframes as $iframe ){
     
     
            if ($iframe->getAttribute('src') && self::isValidURL($iframe->getAttribute('src'))){
                
                
                $bits = parse_url($iframe->getAttribute('src'));
                
                if (self::check_blog($bits['host']) or self::check_mapped_domain($bits['host'])){
                    
                    if ($iframe->getAttribute('sandbox')){
    
                        $iframe->removeAttribute('sandbox');
                
                    }
                    
                     if ($iframe->getAttribute('security')){
    
                        $iframe->removeAttribute('security');
                
                    }
                
                }

         
            }
     
     }
 
$content = str_replace( array( '<html><body>', '</body></html>' ), '', $dom->saveHTML());
 
 libxml_clear_errors(); 
 
 if (!empty($content)){
     
     return $content;
     
     
 } else {


      return $html;
      
 }
 
} 

public function plugin_init(){
    
//maybe add the cors headers is required    
add_action( 'wp_loaded', array($this,'add_header'));

//maybe remove the unneccessary html attributes to embedded iframes if they on the same multisite
add_filter( 'embed_oembed_html', array($this,'maybe_remove_sandbox'), 10, 3);
    
    
}


   /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        if (null === self::$instance) {
            self::$instance = new self();
        }
 
        return self::$instance;
    }


public function __construct() {

        //run our hooks on plugins loaded to as we may need checks       
    add_action( 'plugins_loaded', array($this,'plugin_init'));

}

}

$lh_multisite_cors_instance = LH_multisite_cors_plugin::get_instance();

}


?>