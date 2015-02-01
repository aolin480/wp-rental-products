<?php 

if( !class_exists( 'Idea_messages' ) ) :
	
	class Idea_messages extends Idea_rental{

		protected static $_instance = null;

		public static function instance() {			
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		function __construct(){
			add_action( 'get_cart_messages', array( $this, 'message_listeners' ) );
		}

		public function user_message_id(){
			return 'idea_cart_messages_' . $this->get_user_hash();
		}
		
		private function empty_messages(){
			delete_option( $this->user_message_id() );
		}

		public function set_cart_message( $message, $error = false ) {
			
			$current_messages = get_option( $this->user_message_id() );

			if( is_array( $current_messages ) ) :

				$message_merge = array(
					array( 
						'message' => $message,
						'error' => $error				
					)
				);	

				$message_data = array_merge( $current_messages, $message_merge );

			else:

				$message_data = array(
					array( 
						'message' => $message,
						'error' => $error				
					)
				);	

			endif;					



			update_option( $this->user_message_id(), $message_data );

		}

		public function get_cart_messages(){
			return get_option( $this->user_message_id() );
		}

		public function message_listeners(){
			
			$db_messages = $this->get_cart_messages();					

			if( is_array( $db_messages ) ) :

				foreach( $db_messages as $message ) :
					$error = ( trim( $message['error'] ) ) ? 'error' : '';
					$messages .= "<div class='idea-rental-notice $error'>";
						$messages .= "<p>" . $message['message'] . "</p>";
					$messages .= "</div>";
				endforeach;

				echo $messages;

				$this->empty_messages();

			endif;
		}
		

	}

endif; // eof: class_exists(Idea_messages)

function idea_messages(){
	return Idea_messages::instance();	
}

$GLOBALS['idea_messages'] = idea_messages();

function set_cart_message($message, $error = false){
	idea_messages()->set_cart_message( $message, $error );
}

function get_cart_messages(){
	do_action( 'get_cart_messages' );
}
/*
<?php do_action( 'get_cart_messages' ); ?>
*/