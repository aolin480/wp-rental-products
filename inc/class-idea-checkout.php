<?php

if( !class_exists( 'Idea_checkout' ) ) :
	
	class Idea_checkout extends Idea_rental{

		protected static $_instance = null;

		function __construct(){
		}	
		
		public static function instance() {			
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		
		//public function create_quote( $first_name = null, $last_name = null, $is_quick_quote = false ){		
		public function create_quote( $is_quick_quote = false ){		

			// if $is_quick_quote is true, delete all instances of the regular cart, 
			// and convert the quick cart option to a regular cart option to finalize the cart checkout						
			$products = idea_cart()->get_cart_contents(null, $is_quick_quote);									

			// Set initial properties for product insertion
			
			$time = date('Y-m-d H:i:s');
			$gmtime = gmdate('Y-m-d H:i:s', date('Y-m-d H:i:s') );
			
			/*
			if( $first_name && $last_name ) :
				$name = $first_name . " " . $last_name;
			endif;
			*/

			$quote = array(
				'post_content' 		=> '',
				'post_name'			=> 'temp_quote_' . $time,
				'post_title'		=> "Temporary quote",
				'post_status'		=> 'unread',
				'post_type'			=> 'quote_temp',
				'ping_status'		=> 'closed',
				'post_date'			=> $time,
				'post_date_gmt'		=> $gmtime,
				'comment_status'	=> 'closed'
			);

			$quote_id = wp_insert_post( $quote );

			update_post_meta( $quote_id, '_quote_products', $products );

			$new_quote = array(				
				'ID'				=> $quote_id,
				'post_name'			=> 'quote_' . $quote_id,
				//'post_title'		=> "#" . $quote_id . " by " . ( $name ? $name : '' ),				
				'post_type'			=> 'quote',								
			);
			
			wp_update_post( $new_quote );

			return $quote_id;
			

		}

	}
endif; // eof: Idea_checkout


function Idea_checkout(){
	return idea_checkout::instance();	
}

$GLOBALS['idea_checkout'] = idea_checkout();
