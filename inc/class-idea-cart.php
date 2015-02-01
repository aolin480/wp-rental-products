<?php

if( !class_exists( 'Idea_cart' ) ) :
	
	class Idea_cart extends Idea_rental{

		protected static $_instance = null;

		function __construct(){
			add_filter( 'rental_button_add_to_quote', array( $this, 'button_add_to_quote' ), 20, 4 );						
			add_filter( 'rental_button_add_to_quote_single', array( $this, 'button_add_to_quote_single' ), 20, 4 );
			add_action( 'init', array( $this, 'product_add_to_quote' ) );									
			
			// if $_GET['empty_cart'] has been set; empty the cart
			add_action( 'init', array( $this, 'cart_listeners' ) );									

			add_filter( 'the_content', array( $this, 'the_cart_table' ), 1, 99 );
		}	
		
		public static function instance() {			
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * button_add_to_quote
		 * @param string $current_page_id gets current page url
		 * @param int/string $post_id the post id of the product
		 * @param boolean $echo echo html output
		 * @return HTML button anchor
		 */
		

		public function button_add_to_quote( $current_page_id, $post_id, $echo = false, $button_text = 'Add to Quote' ){						
			
			// if current page is taxonomy, then override the $current_page_id with the taxonomy link since added product to quote would 
			// redirect to product page sine the $current_page_id and $post_id are the same
			if( is_tax( 'product-cat' ) ) :
				global $wp_query;
				$term =	$wp_query->queried_object;
				$taxonomy = idea_rental()->taxonomy_product_cat;								
				$current_page_id = get_term_link( $term->slug, $taxonomy );												
			endif;

			$quote_link = add_query_arg( array( 
				'product'	=> 	$post_id,
				'nonce'		=>	wp_create_nonce( 'add_to_quote' ),
				'page'		=>	$current_page_id				
			), $current_page_id );

			$button = "<a class='quote-btn btn btn-small outline-gray' href='{$quote_link}'>$button_text</a>"; 
			//echo get_page_id();

			if( $echo ) :
				echo $button;
			else:
				return $button;
			endif;

		}

		/**
		 * button_add_to_quote_single
		 * @param string $current_page_id gets current page url
		 * @param int/string $post_id the post id of the product
		 * @param boolean $echo echo html output
		 * @return HTML button anchor
		 */
		
		public function button_add_to_quote_single( $current_page_id, $post_id, $echo = false, $button_text = 'Get Quote' ){						
			
			$quote_link = add_query_arg( array( 
				'product'	=> 	$post_id,
				'nonce'		=>	wp_create_nonce( 'add_to_quote' ),
				'page'		=>	$current_page_id
			), get_permalink( $current_page_id ) );

			$button = "<a class='quote-btn btn btn-primary' href='{$quote_link}'>$button_text</a>"; 
			//echo get_page_id();
			if( $echo ) :
				echo $button;
			else:
				return $button;
			endif;

		}

		/**
		 * product_add_to_quote
		 * inserts product into cart
		 */
		public function product_add_to_quote(){
			
			global $post;

			if( $_REQUEST['page'] && $_REQUEST['nonce'] && $_REQUEST['product'] ) :
			
				$last_page = ( filter_var($_REQUEST['page'], FILTER_VALIDATE_URL) ) ? $_REQUEST['page'] : get_permalink( $_REQUEST['page'] );

				$nonce = wp_verify_nonce( $_REQUEST['nonce'], 'add_to_quote' );
				$product_id = absint( $_REQUEST['product'] );
				
				if( !$nonce && get_post_type( $product_id ) != 'product' ) :
					//wp_redirect( $last_page );										
					wp_redirect( $last_page );										
					exit();
				else:							
					$insert_product = $this->set_cart_contents( $product_id );
				
					if( $insert_product ) :
						$product_title = ( $product_id ) ? get_the_title( $product_id ) : 'Product';
						set_cart_message( $product_title . ' has been added to your ' . $this->get_cart_html_link( 'rental cart' ) );						
					endif;

					$url_submit_page = get_permalink( $this->get_page_rental_form() );							
					wp_redirect( add_query_arg( array( 'lp' => $last_page, 'inserted' => $insert_product ), $last_page ) );
					exit();
				endif;

			endif;

		}
		
		public function get_cart_url( $message = 'rental cart' ){
			return get_permalink( $this->get_page_rental_form() );
		}

		public function get_cart_html_link( $message = 'rental cart' ){
			return "<a href='" . $this->get_cart_url() . "'>" . $message . "</a>";
		}

		/**
		 * remove_product_url
		 * generates the remove product from cart URL
		 */
		private function remove_product_url( $product_id = null ){			
			if( !$product_id )
				return false;
			return add_query_arg( array( 'remove' => 'true', '_wpnonce' => wp_create_nonce( 'remove-product' ), 'pid' => $product_id ), get_permalink( $this->get_page_rental_form() ) );
		}

	
		/**
		 * empty_cart_url
		 * generates a URL to empty the cart
		 */
		private function empty_cart_url(){
			return add_query_arg( array( 'empty_cart' => 'true', '_wpnonce' => wp_create_nonce( 'empty-cart' ) ), '/' );
		}

		/**
		 * update_cart_url
		 * generates a URL to update the cart
		 */
		private function update_cart_url(){
			return add_query_arg( array( 'update_cart' => 'true', '_wpnonce' => wp_create_nonce( 'update-cart' ) ), '/' );
		}

		/**
		 * product_remove_from_cart
		 * removes a single product from the cart
		 */
		public function product_remove_from_cart( $product_id ){

			$product = get_option( $this->users_cart() );

			if( $product[$product_id] ) :
				unset($product[$product_id]);
			endif;

			return update_option( $this->users_cart(), $product );
		}

		/**
		 * is_cart_page
		 * checks if current page is the cart page
		 */
		public function is_cart_page( $current_page_id ){			
			if( $current_page_id == $this->get_page_rental_form() )
				return true;			
		}

		/**
		 * set_cart_contents
		 * adds and appends products to the cart. 
		 * This will automatically adjust quantities too
		 */
		public function set_cart_contents( $product_id, $update = false, $new_quantity = null ){
			
			$product = get_option( $this->users_cart() );	
			
			if( $update ){		
				if( $new_quantity ) :
					$product[$product_id]['quantity'] = $new_quantity;
				else:
					unset( $product[$product_id] );
				endif;
			}else if( $product[$product_id] ) :
				$product[$product_id]['quantity'] = $product[$product_id]['quantity'] + 1;
			else:
				$product[$product_id] = array(
					'title'	=>	get_the_title( $product_id ),
					'quantity'	=>	1				
				);

			endif;

			return update_option( $this->users_cart(), $product );
		}

		/**
		 * get_cart_contents
		 * grabs the cart contents using the native wordpress options
		 */
		public function get_cart_contents( $quote_id = null, $quick_quote = false ){					
			$products = ( $quote_id ) ? $this->get_quote_products( $quote_id ) : ( $quick_quote ? get_option( $this->users_quick_cart() ) : get_option( $this->users_cart() ) );									
			return $products;
		}

		public function get_quote_products( $quote_id = null ){		
			
			if( !$quote_id )
				return false;

			$products = get_post_meta( $quote_id, '_quote_products', false );

			if( $products[0] ) :
				return array_shift( $products );
			else:
				return $products;
			endif;

		}



		public function get_cart_values(){

			$products = get_option( $this->users_cart() );
			if( $products ) :
				$content .= wp_nonce_field('update-cart','_wpnonce');
				$content .= "<input type='hidden' name='update_cart' value='true' >";
				foreach( $products as $pid => $product ) :

					foreach( $product as $key => $p ) :
						$content .= "<input type='hidden' name='product[$pid][$key]' " . ( $key == 'quantity' ? "data-quantity='qty-$pid'" : '' ) . " value='$p' >";
					endforeach;

				endforeach;
				return $content;
			endif;

		}

		/**
		 * get_cart_table
		 * Generates the cart table to be later inserted before the content on the cart page
		 */
		public function get_cart_table( $quote_id = null, $echo = false, $email = false, $quick_quote = false ){
			
			$products = $this->get_cart_contents( $quote_id );

			$content .= "<div class='rental-cart'>";
				
				if( $products ) :

					$content .= '<table role="grid" ' . ( $email ? 'cellspacing="0" cellpadding="10" width="100%" style="max-width: 768px;border:1px solid #ddd;"' : '' ) . '>
						<thead>
							<tr role="row">
								<th class="product_image" ' . ( $email ? 'style="background:#666;color:#fff;text-align:left;"' : '' ) . '>&nbsp;</th>
						        <th class="product_title" ' . ( $email ? 'style="background:#666;color:#fff;text-align:left;"' : '' ) . '>Rental Item</th>';
						        
						        if( $quick_quote ) :
						       		$content .= '<th class="product_rental_term" ' . ( $email ? 'style="background:#666;color:#fff;text-align:left;"' : '' ) . '>Rental Term</th>';
						        endif;

						    $content .= '
						        <th class="product_quantity" ' . ( $email ? 'style="background:#666;color:#fff;text-align:left;"' : '' ) . '>Quantity</th>
						        <th class="product_delete" ' . ( $email ? 'style="background:#666;color:#fff;text-align:left;"' : '' ) . '>&nbsp;</th>
						    </tr>
						</thead>';
					$content .= '
						<tbody>';

					$thumbnail = ( !$email ? 'thumbnail' : 'idea_cart_small' );
					
					foreach( $products as $pid => $product ) :

					    $content .= '<tr role="row">';							
							$content .= "<td class='product_image' style='text-align:center;'>" . get_the_post_thumbnail( $pid, $thumbnail, $attr ) . "</td>";
					        $content .= "<td class='product_title'><a href='" . get_permalink( $pid ) . "'>{$product['title']}</a></td>";
					        
					        if( $quick_quote ) :
					        	$content .= "<td class='product_rental_term'>{$product['rental_term']}</td>";
					       	endif;

					        $content .= "<td class='product_quantity' " . ( $email ? 'style="text-align: center;"' : '' ) . ">";
					        $content .= ( !$quote_id ) ? "<input type='text' value='{$product['quantity']}' name='qty-$pid' onkeydown='return ( event.ctrlKey || event.altKey || (47<event.keyCode && event.keyCode<58 && event.shiftKey==false) || (95<event.keyCode && event.keyCode<106) || (event.keyCode==8) || (event.keyCode==9) || (event.keyCode>34 && event.keyCode<40) || (event.keyCode==46) )'></td>" : "<h3>{$product['quantity']}</h3>";
					        $content .= "<td class='product_delete'><a href='" . $this->remove_product_url( $pid ) . "'><i class='fa fa-remove'></i></a></td>";
					    $content .= '</tr>';
					    
					endforeach;
					$content .= '							
						</tbody>
					</table>';

					if( !$quote_id ) :
						
						$content .= "<div class='other-utility'>";
							$content .= "<ul>";
								$content .= "<li><a href='" . get_permalink( $this->get_page_categories_listings() ) . "' class='rental_button'>continue browsing</a></li>";							
							$content .= "</ul>";
							$content .= "<br clear='all'>";
						$content .= "</div>";


						$content .= "<div class='cart-utility'>";
							$content .= "<ul>";
								$content .= "<li><a href='" . $this->empty_cart_url() . "' class='rental_button red' id='btn-empty-cart'>empty cart</a></li>";
								
								$content .= "<li>";								
									$content .= "<form method='post' action='' id='form-update-cart'>";
										$content .= $this->get_cart_values();
									$content .= "<a href='#' id='update-cart' class='rental_button'>update cart</a></form>";
								$content .= "</li>";

							$content .= "</ul>";
							$content .= "<br clear='all'>";
						$content .= "</div>";

					endif;

					
				else:
					$content .= '<h3>No Products have been selected. Please browse our product categories to get started.</h3><br clear=all>';
					$content .= '<a href="' . get_permalink( $this->get_page_categories_listings() ) . '" class="rental_button">Browse Products</a>';
				endif;

			$content .= "</div>";

			if( $quote_id && $echo ) :
				echo $content;
			else:
				return $content;
			endif;

		}

		/**
		 * the_cart_table
		 * Adds the cart html table to the cart page using add_filter( 'the_content' );
		 */
		public function the_cart_table( $content ){			
			// if the current page is the cart page, display the cart table before the content
			global $post;
			$post_id = $post->ID;
			if( $this->is_cart_page( $post_id ) ){				
				$the_content .= $this->get_cart_table();
				$the_content .= $content;
				return $the_content;			
			}else{
				return $content;
			}
		}
		/**
		 * cart_listeners
		 * listen for actions using $_GET, $_REQUEST, or $_POST variables.
		 */
		public function cart_listeners(){			
			$this->empty_cart();
			$this->update_cart();
			$this->remove_product();
		}
		/**
		 * convert_to_quick_quote_cart
		 * converts a quick quote cart to a regular cart to streamline the checkout process 
		 * We also check to see if the customer has a regular cart in the database; if they do, then we delete that
		 * to make room for the quick quote cart
		 */
		public function convert_to_quick_quote_cart(){
			global $wpdb;

			idea_cart()->empty_cart( true, false );

			$users_cart_id = idea_cart()->users_cart();
			$quick_cart_id = idea_cart()->users_quick_cart();

			$update = $wpdb->update( 
				$wpdb->options,
				array(
				  'option_name' => $users_cart_id
				),
				array(
				  'option_name' =>  $quick_cart_id
				),
				array(
				  '%s'
				),
				array(
				  '%s'
				)    
			);
		}

		/**
		 * empty_cart
		 * empties the cart; invoked from cart_listeners
		 * $force = bypass the nonce and request checks and empty the cart ( used when order is completed )
		 * $redirect_page_id = redirect to a page after emptied cart using the page id and creating the permalink
		 */
		public function empty_cart( $force = false, $redirect = true, $redirect_page_id = null, $quick_quote_empty = false ){
			
			// remove the quick quote cart
			if( $force && $quick_quote_empty ) :
				delete_option( $this->users_quick_cart() );
				$emptied = true;
			endif;

			// forcefully empty the cart; useful when completing a checkout
			if( $force && !$quick_quote_empty ) :
				delete_option( $this->users_cart() );
				$emptied = true;
			endif;

			if( ( $_REQUEST['_wpnonce'] && $_REQUEST['empty_cart'] ) && ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'empty-cart' ) ) ) :
				delete_option( $this->users_cart() );
				$emptied = true;
			endif;

			if( $emptied && $redirect ) :
				$redirect_id = ( $redirect_page_id ) ? $redirect_page_id : $this->get_page_categories_listings();
					wp_redirect( get_permalink( $redirect_id ) );
				exit();
			endif;

		}

		/**
		 * update_cart
		 * updates the cart with new quantites; invoked from cart_listeners
		 */
		private function update_cart(){			
			if( ( $_REQUEST['_wpnonce'] && $_REQUEST['update_cart'] ) && ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'update-cart' ) ) ) :	
				foreach( $_POST['product'] as $pid => $product ) :						
					$this->set_cart_contents( $pid, true, $product['quantity'] );
				endforeach;
				wp_redirect( get_permalink( $this->get_page_rental_form() ) );
				exit();
			endif;
		}

		/**
		 * remove_product
		 * removes a product from the cart; invoked from cart_listeners
		 */
		private function remove_product(){
			if( ( $_REQUEST['_wpnonce'] && $_REQUEST['remove'] && $_REQUEST['pid'] ) && ( wp_verify_nonce( $_GET['_wpnonce'], 'remove-product' ) ) ) :
				$this->product_remove_from_cart( $_REQUEST['pid'] );
				wp_redirect( get_permalink( $this->get_page_rental_form() ) );
				exit();
			endif;
		}		

		
	} // eof: Idea_cart class

endif; // eof: class_exists::Idea_cart

function idea_cart(){
	return Idea_cart::instance();	
}

$GLOBALS['idea_cart'] = idea_cart();