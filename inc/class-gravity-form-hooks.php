<?php

if( !class_exists( 'Idea_gravity_hooks' ) ) :
	
	class Idea_gravity_hooks extends Idea_rental {
		
		protected static $_instance = null;
		
		// used to insert in to gravity forms entry
		var $quote_id;		
		var $lead_id;

		function __construct(){					
			//add_filter('gform_validation', array( $this, 'custom_validation' ) );
			add_action("gform_pre_submission_filter", array( $this, 'check_form_quick_quote' ) );

			// Fired 1 - Create a temporary quote post type to get the ID of the quote post to be inserted later in to the gravity forms entry data			
			//add_filter( 'gform_entry_meta', array( $this, 'add_quote_id' ), 10, 2);

			// Fired 2 - control sending of email
			add_filter( "gform_pre_send_email", array( $this, "before_email" ) );

			// Fired 3 - After confirmation has been sent, complete the quote and empty the cart
			add_filter( "gform_confirmation", array( $this, "checkout_success_redirect" ), 10, 4);

			// get the quote id from GF form
			add_action( "gform_entry_detail", array( $this, "add_cart_to_entry" ), 10, 2);
			
			// Added class wraps to hidden fields since GF has no support to add classes to hidden fields
			add_action("gform_field_css_class", array( $this, "custom_class" ), 10, 3);


		}	

		public static function instance() {			
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		function check_form_quick_quote( $form ){
			
			foreach( $_POST as $key => $value ) :
				if( $value == 'quick_quote' ) :
					$form["is_quick_quote"] = true;
				endif;
			endforeach;

			return $form;
		}
		
		public function custom_class($classes, $field, $form){
			
			if($field["type"] == "hidden") :
				$class = $this->permalize( $field['inputName'] );				
				$classes .= " " . $class;
			endif;
			
		    return $classes;

		}

		

		function add_quote_id( $entry_meta, $form_id ){			

			//data will be stored with the meta key named score
			//label - entry list will use Score as the column header
			//is_numeric - used when sorting the entry list, indicates whether the data should be treated as numeric when sorting
			//is_default_column - when set to true automatically adds the column to the entry list, without having to edit and add the column for display
			//update_entry_meta_callback - indicates what function to call to update the entry meta upon form submission or editing an entry
			
			if( !is_admin() ) :
					
				if( $form_id == $this->get_page_rental_form_id() ) :				

					$quote_id = idea_checkout()->create_quote( false );			
					
					$this->quote_id = $quote_id;

				    $entry_meta['quote_id'] = array(
				        'label' => 'Quote ID',
				        'is_numeric' => true,		        
				        'update_entry_meta_callback' => array($this, 'update_entry_meta' ), 
				        'is_default_column' => true
				    );

				    return $entry_meta;

			    endif;
			    

			else:
				 return $entry_meta;
			endif;
		}

		function update_entry_meta($key, $lead, $form){

			$lead_id = $lead['id'];
			update_post_meta( $this->quote_id, '_gf_lead_id', $lead_id );

		    $value = $this->quote_id;
		    return $value;
		}

		public function before_email( $email ){			

			$message = $email["message"];
			unset( $email['message'] );

			$quote_id = $this->quote_id;

			$email["message"] .= "<h2>Requested Rental Items</h2>";
			$email["message"] .= idea_cart()->get_cart_table( $quote_id, false, true );
			$email["message"] .= $message;

			$email["abort_email"] = true;

		    return $email;  
		}

		/* Finalize the quote and create an entry */
		public function checkout_success_redirect($confirmation, $form, $lead, $ajax){
		    
		    if( $form["id"] == $this->get_page_rental_form_id() ) :		    		    			    	
		    
		    	$first_name = $lead[$this->get_page_rental_form_field_first_name_id()];
				$last_name = $lead[$this->get_page_rental_form_field_last_name_id()];
				$name = trim( $first_name . " " . $last_name );
				
				$lead_id = $lead['id'];

				if( $form['is_quick_quote'] ) :					
					$quote_id = idea_checkout()->create_quote( true );												
				else:
					$quote_id = idea_checkout()->create_quote( false );
				endif;

				update_post_meta( $quote_id, '_gf_lead_id', $lead_id );
			
				if( get_post( $quote_id ) ) :
					$post_array = array(
						'ID'	=>	$quote_id,
						'post_name'			=> 'quote_' . $quote_id . '_' . $name,
						'post_title'		=> "#" . $quote_id . " by " . ( $name ? $name : '' ),				
						'post_type'			=> 'quote',	
					);
					
					wp_update_post( $post_array );

				endif;

		    	// after processing order empty the cart
		    	if( $form['is_quick_quote'] ) :
		    		idea_cart()->empty_cart( true, false, null, true );
		    	else:
		    		idea_cart()->empty_cart( true, false );
		    	endif;
		    	
		    	if( $this->get_page_thank_you() ) :
			        $confirmation = array( 
			        	"redirect" => add_query_arg( array('success' => '1'), get_permalink( $this->get_page_thank_you() ) ) 
			        );
			   	endif;

		    endif;

		    return $confirmation;
		}
		
		public function add_cart_to_entry($form, $lead){
						
			if ($form["id"] == $this->get_page_rental_form_id() ){		
				$quote_id = $lead['quote_id'];
				idea_cart()->get_cart_table( $quote_id, true );
			}

		}		

		
	}

endif;

function idea_gravity_hooks(){
	return Idea_gravity_hooks::instance();	
}

$GLOBALS['idea_gravity_hooks'] = idea_gravity_hooks();