<?php
/*
Plugin Name: Idea Product Rental
Version: 1.0
Description: Adds Product Rental functionality using wordpress
Author: Idea Marketing ( aaron@ideamktg.com )
Author URI: http://www.ideamktg.com
Plugin URI: http://www.ideamktg.com
Text Domain: idea-rental
Domain Path: /languages
*/



if (!defined('ABSPATH'))
    exit; // Exit if accessed directly


if( !class_exists( 'Idea_rental' ) ) :
	
	class Idea_rental {						
		
		protected static $_instance = null;

		public $current_id;
		public $taxonomy_product_cat;
		
		function __construct(){			
			
			// set the product taxonomy
			$this->taxonomy_product_cat = 'product-cat';

			// create image sizes
			add_action( 'init', array( $this, 'add_image_sizes' ) );
			
			// Enqueue the frontend scripts and styles 
			add_action( 'wp_enqueue_scripts', array( $this, 'idea_rental_enqueue' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_idea_rental_enqueue' ) );			

			// Sets the current ID so we can use it in the plugin
			add_action( 'template_redirect', array( $this, 'set_current_id' ) );

			add_action( 'pre_get_posts', array( $this, 'idea_rental_conditional_pages' ) );

			// ajax handling
			/*
			add_action( "wp_ajax_idea_rental", array( $this, 'idea_rental_ajax' ) );
			add_action( "wp_ajax_nopriv_idea_rental", array( $this, 'idea_rental_ajax' ) );		
			*/

			// Include required files
			$this->includes();			
		}

		
		// returns the wordpress include directory to use some of wordpress's functions
		public function wp_include_dir(){	 	   
	 	   $wp_include_dir = preg_replace('/wp-content$/', 'wp-includes', WP_CONTENT_DIR);
	       return $wp_include_dir;
		}

		// use this to create a variable like string to be used in various functions
		public function permalize( $string ) {				
			$include_dir = $this->wp_include_dir();				
			include_once( $include_dir . '/formatting.php');
			return sanitize_title_with_dashes( $string );
		}


		public function add_image_sizes(){
			if ( function_exists( 'add_image_size' ) ) {				
				add_image_size( 'idea_cart_small', 75, 75, true );
			}
		}

		public function idea_rental_conditional_pages( $query ){
			
			if( !is_admin() && $query->is_main_query() ) :

				if( $this->get_page_rental_form() && !idea_cart()->get_cart_contents() && $query->queried_object_id == $this->get_page_rental_form() ) :
					wp_redirect( get_permalink( $this->get_page_categories_listings() ) );
					exit();
					return $query;
	        	endif;

	        	if( $this->get_page_thank_you() && $query->queried_object_id == $this->get_page_thank_you() && ( idea_cart()->get_cart_contents() || !$_GET['success'] ) ) :
					wp_redirect( get_permalink( $this->get_page_rental_form() ) );
					exit();
					return $query;
	        	endif;


        	endif;

        	return $query;

		}

		public function set_current_id(){
			global $post;			
			$this->current_id = $post->ID;
		}

		/**
		 * Get the plugin url.
		 *
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		// checks if current user is administrator. If they are then let the plugin do its thang!
		public function check_admin_role(){
			
			global $current_user;
			$user_roles = $current_user->roles;			
			$user_role = array_shift($user_roles);
			
			if( $user_role == 'administrator' ) :
				$is_admin = true;
			endif;

			if( $is_admin ) :				
				$this->is_gnarly_admin = true;
				return true;
			endif;

		}

		public function get_users_ip(){
			if (isset($_SERVER)) {

		        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
		            return $_SERVER["HTTP_X_FORWARDED_FOR"];

		        if (isset($_SERVER["HTTP_CLIENT_IP"]))
		            return $_SERVER["HTTP_CLIENT_IP"];

		        return $_SERVER["REMOTE_ADDR"];
		    }

		    if (getenv('HTTP_X_FORWARDED_FOR'))
		        return getenv('HTTP_X_FORWARDED_FOR');

		    if (getenv('HTTP_CLIENT_IP'))
		        return getenv('HTTP_CLIENT_IP');

		    return getenv('REMOTE_ADDR');
		}

		public function get_user_hash(){
			return base64_encode( $this->get_users_ip() );
		}

		public function users_cart(){
			return 'user_rental_' . $this->get_user_hash();			
		}

		public function users_quick_cart(){
			return 'qq_user_rental_' . $this->get_user_hash();			
		}
				
		public function idea_rental_ajax(){			
			/*
			echo json_encode( $response );
			exit();
			*/
		}

		// load the scripts only if the Admin is gnarly enough
		public function idea_rental_enqueue() { 

			// fontawesome.io
			
			// local
			//wp_enqueue_style( 'fontawesome', $this->plugin_url() . '/assets/css/fontawesome/css/font-awesome.min.css', array(), '' );

			// CDN
			wp_enqueue_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', array(), '' );

			// idea-rental stylesheet
			wp_enqueue_style( 'idea-rental', $this->plugin_url() . '/assets/css/idea-rental.css', array(), '' );

		    wp_enqueue_script('jquery');  		    
			wp_register_script( 'idea-rental-global', $this->plugin_url() . '/assets/js/idea-rental.js', array( 'jquery' ), '', true ); 
			wp_enqueue_script( 'idea-rental-global' );  				
			
			/*
			wp_localize_script( 'idea-rental-global', 'idea_site_options', 
				array( 
					'home'	=>	get_bloginfo( 'url' )
				) 
			);
			*/

			/*
			wp_register_script( 'idea-rental-global', $this->plugin_url() . 'assets/js/gnarly.frontend.sort.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), '', true ); 
			wp_enqueue_script( 'idea-rental-global' );  							
			*/
		}

		public function logz( $var ){
			error_log( print_r( $var, 1 ), 3, 'abcom_errors.log' );
		}

		public function _l( $var ){
			error_log( print_r( $var, 1 ), 1, 'aaron@ideamktg.com' );
		}

		public function admin_idea_rental_enqueue(){
			// admin styles
			wp_enqueue_style( 'admin-idea-rental', $this->plugin_url() . '/assets/css/idea_admin.css', array(), '' );			
		}

		/* Get Idea Rental Cart Options */
		public function load_idea_settings_options(){
            return (object)get_option( 'idea_rental_options' );
        }
        
        public function get_page_rental_form_id(){            
            $options = $this->load_idea_settings_options();            
            return $options->idea_rental_gravity_form;
        }

        public function get_page_rental_form(){            
            $options = $this->load_idea_settings_options();            
            return $options->idea_rental_form_page;
        }

        public function get_page_rental_quick_quote_form(){            
            $options = $this->load_idea_settings_options();            
            return $options->idea_rental_quick_quote_form_page;            				 
        }

        public function get_page_thank_you(){            
            $options = $this->load_idea_settings_options();            
            return $options->idea_rental_thank_you_page;
        }


        /* Return the First Name Field ID from Gravity Forms to be used in storing the data later */
        public function get_page_rental_form_field_first_name_id(){            
            $options = $this->load_idea_settings_options(); 
            
            $first_name_id = $options->idea_rental_gravity_field_first_name;
            if( $first_name_id )
            	return $first_name_id;

        }
		
		/* Return the Last Name Field ID from Gravity Forms to be used in storing the data later */
        public function get_page_rental_form_field_last_name_id(){            
            $options = $this->load_idea_settings_options(); 
            $last_name_id = $options->idea_rental_gravity_field_last_name;
            if( $last_name_id )
            	return $last_name_id;
        }

        public function get_page_categories_listings(){            
            $options = $this->load_idea_settings_options();            
            return $options->idea_rental_categories_listings_page;
        }

		private function includes(){
			
			include_once( 'inc/class-idea-messages.php' );	// Handles the Message output functionality	
			include_once( 'inc/class-custom-post-types.php' );	// Handles the Cart functionality
			include_once( 'inc/class-idea-admin-options.php' );	// Handles the Cart functionality
			include_once( 'inc/class-idea-cart.php' );	// Handles the Cart functionality			
			include_once( 'inc/class-idea-quick-quote.php' );	// Handles the Quick Quote functionality			
			include_once( 'inc/class-gravity-form-hooks.php' );	// Handles the Cart functionality			
			include_once( 'inc/class-idea-products.php' );	// Handles the Product Functionality			
			include_once( 'inc/class-idea-checkout.php' );	// Handles the Checkout functionality	
			include_once( 'inc/class-custom-post-status.php' );	// Custom Backend Post Statuses
		}

	}

endif; // eof: class exists

function idea_rental() {
	return Idea_rental::instance();
}

$GLOBALS['idea_rental'] = idea_rental();

function get_page_rental_quick_quote_form_url(){            
	$options = idea_rental()->load_idea_settings_options();            
	return get_permalink( $options->idea_rental_quick_quote_form_page );
}


?>