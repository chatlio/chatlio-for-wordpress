<?php
	
/**
 *	Plugin Name: Chatlio for WordPress
 *	Plugin URI: http://chatlio.pragmatic-web.co.uk/
 *	Description: Chatlio plugin for WordPress
 *	Version: 0.0.1
 *	Author: James Morrison / Pragmatic Web
 *	Author URI: https://www.pragmatic-web.co.uk/
 **/

// Security check...
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access to this file is forbidden.' );
	exit;
}


/* REFERENCE SCRIPT
<script type="text/javascript">
  var _chatlio=_chatlio||[];
 !function(){
    var t=document.getElementById("chatlio-widget-embed");if(t&&window.React&&_chatlio.init)return void _chatlio.init(t,React);
    for(var e=function(t){return function(){_chatlio.push([t].concat(arguments))}},i=["identify","track","show","hide","isShown","isOnline"],a=0;a<i.length;a++)_chatlio[i[a]]||(_chatlio[i[a]]=e(i[a]));
    var n=document.createElement("script"),c=document.getElementsByTagName("script")[0];
    n.id="chatlio-widget-embed",n.async=!0,n.setAttribute("data-widget-id","47594043-c2f9-43a6-698d-a080a019870e"),n.setAttribute("data-embed-version","1.2"),n.src="https://w.chatlio.com/w.chatlio-widget.js",c.parentNode.insertBefore(n,c)
  }();
</script>
*/


/**
 * Github Updater
 *
 * @since 1.0
 */

if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin
	
	include_once( 'github-updater.php' );
	
	$config = array(
		'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
		'proper_folder_name' => 'chatlio', // this is the name of the folder your plugin lives in
		'api_url' => 'https://api.github.com/repos/jamesmorrison/chatlio-for-wordpress', // the github API url of your github repo
		'raw_url' => 'https://raw.github.com/amesmorrison/chatlio-for-wordpress/master', // the github raw url of your github repo
		'github_url' => 'https://github.com/amesmorrison/chatlio-for-wordpress', // the github url of your github repo
		'zip_url' => 'https://github.com/amesmorrison/chatlio-for-wordpress/zipball/master', // the zip url of the github repo
		'sslverify' => true, // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
		'requires' => '3.7', // which version of WordPress does your plugin require?
		'tested' => '3.7.1', // which version of WordPress is your plugin tested up to?
		'readme' => 'README.md', // which file to use as the readme for the version number
		'access_token' => '', // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
	);
	
	new WP_GitHub_Updater( $config );

}



/**
 * Chatlio class
 *
 * @since 1.0
 */

class Chatlio {
	
	/*
	 * Starter defines and vars for use later
	 *
	 * @since 1.0
	 */

	// Holds option data.
    var $option_name = 'pwl_chatlio_options';
    var $options = array();
    var $option_defaults;
    
    // DB version, for schema upgrades.
    var $db_version = 1;

	// Instance
	static $instance;

    /**
     * Constuct
     * Fires when class is constructed, adds init hook
     *
     * @since 1.0
     */
    function __construct() {
	    
	    //allow this instance to be called from outside the class
        self::$instance = $this;

		//add frontend init hook
		add_action( 'init', array( &$this, 'init' ) );

		//add frontend wp_head hook
		add_action( 'wp_head', array( &$this, 'wp_head' ) );

		//add admin init hook
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		//add admin panel
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

    	// Setting plugin defaults here:
		$this->option_defaults = array(
			'widget_id' => '',
	        'db_version' => $this->db_version,
	    );

	}

	
	/**
	 * Frontend init Callback
	 *
	 * @since 1.0
	 */
    function init() {
	    
	}

	/**
	 * Frontend wp_head Callback
	 *
	 * @since 1.0
	 */
    function wp_head() {
	    
	    // Get options
	    $this->options = wp_parse_args( get_option( 'chatlio_options' ), $this->option_defaults );
	    
	    if ( isset ( $this->options['widget_id'] ) ) {
		    
		    echo '<script type="text/javascript">
					var _chatlio=_chatlio||[];
					!function(){
						var t=document.getElementById("chatlio-widget-embed");if(t&&window.React&&_chatlio.init)return void _chatlio.init(t,React);
						for(var e=function(t){return function(){_chatlio.push([t].concat(arguments))}},i=["identify","track","show","hide","isShown","isOnline"],a=0;a<i.length;a++)_chatlio[i[a]]||(_chatlio[i[a]]=e(i[a]));
						var n=document.createElement("script"),c=document.getElementsByTagName("script")[0];
						n.id="chatlio-widget-embed",n.async=!0,n.setAttribute("data-widget-id","' . $this->options['widget_id'] . '"),n.setAttribute("data-embed-version","1.2"),n.src="https://w.chatlio.com/w.chatlio-widget.js",c.parentNode.insertBefore(n,c)
					}();
				</script>';
		    
	    }
	    
	    
	}
	
	/**
	 * Admin init Callback
	 *
	 * @since 2.0
	 */
    function admin_init() {

        //Fetch and set up options.
	    $this->options = wp_parse_args( get_option( 'chatlio_options' ), $this->option_defaults );
		
	    // Register Settings
		$this::register_settings();
	}

	/**
	 * Admin Menu Callback
	 *
	 * @since 2.0
	 */
    function admin_menu() {
		// Add settings page on Tools
		add_management_page( __('Chatlio'), __('Chatlio'), 'manage_options', 'chatlio-settings', array( &$this, 'chatlio_settings' ) );
	}

	/**
	 * Register Admin Settings
	 *
	 * @since 2.0
	 */
    function register_settings() {
	    register_setting( 'chatlio', 'chatlio_options', array( $this, 'chatlio_sanitize' ) );

		// The main section
		add_settings_section( 'chatlio_settings_section', 'Chatlio Settings', array( &$this, 'chatlio_settings_callback'), 'chatlio-settings' );

		// The Fields
		add_settings_field( 'widget_id', 'Widget ID', array( &$this, 'widget_id_callback'), 'chatlio-settings', 'chatlio_settings_section' );
	}

	/**
	 * UI Labs Experiments Callback
	 *
	 * @since 2.0
	 */
	function chatlio_settings_callback() {
/*
	    ?>
	    <p><?php _e('Please enter your Widget ID to enable Chatlio:', 'chatlio'); ?></p>
	    <?php
*/
	}

	/**
	 * Colour-Coded Post Statuses Callback
	 *
	 * @since 2.0
	 */
	function widget_id_callback() {
		?>
		<input type="input" id="chatlio_options[widget_id]" name="chatlio_options[widget_id]" value="<?php echo ( $this->options['widget_id'] ); ?>" >
		<label for="chatlio_options[widget_id]"><?php _e('Add your Widget ID to enable Chatlio', 'chatlio'); ?></label>
		<?php
	}

	/**
	 * Call settings page
	 *
	 * @since 1.0
	 */
	
	function chatlio_settings() { 
		?>

		<div class="wrap">
			
		<h2><?php _e( 'Chatlio', 'chatlio' ); ?></h2>
		
		<form action="options.php" method="POST" >
		    <?php 
			    settings_fields('chatlio');
			    do_settings_sections( 'chatlio-settings' );
			    submit_button();
		    ?>
		</form>
		</div>
		<?php
	}

	/**
	 * Options sanitization and validation
	 *
	 * @param $input the input to be sanitized
	 * @since 2.0
	 */
	function chatlio_sanitize( $input ) {
    	$options = $this->options;
    	
    	$input['db_version'] = $this->db_version;

    	foreach ($options as $key=>$value) {
            $output[$key] = sanitize_text_field($input[$key]);
        }

		return $output;
	}


	/**
	 * Add settings link on plugin
	 *
	 * @since 2.0
	 */
	function add_settings_link( $links, $file ) {
		if ( plugin_basename( __FILE__ ) == $file ) {
			$settings_link = '<a href="' . admin_url( 'tools.php?page=chatlio-settings' ) .'">' . __( 'Settings', 'chatlio' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

}

new Chatlio();





