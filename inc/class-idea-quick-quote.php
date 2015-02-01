<?php

if( !class_exists( 'Idea_quick_quote' ) ) :
	
	class Idea_quick_quote extends Idea_rental{

		protected static $_instance = null;

		function __construct(){
			add_filter( 'the_content', array( $this, 'the_quick_quote_form' ), 1, 99 );
			add_action( 'wp_enqueue_scripts', array( $this, 'idea_quick_quote_enqueue' ) );

			// Ajax select input functions
			add_action("wp_ajax_quick_quote_select", array( $this, 'quick_quote_select' ) );
			add_action("wp_ajax_nopriv_quick_quote_select", array( $this, 'quick_quote_select' ) );						

			// Ajax quanityt input functions
			add_action("wp_ajax_quick_quote_quantity", array( $this, 'quick_quote_quantity' ) );
			add_action("wp_ajax_nopriv_quick_quote_quantity", array( $this, 'quick_quote_quantity' ) );						

		}	
		
		public static function instance() {			
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function change_key( $array, $old_key, $new_key) {

		    if( ! array_key_exists( $old_key, $array ) )
		        return $array;

		    $keys = array_keys( $array );
		    $keys[ array_search( $old_key, $keys ) ] = $new_key;

		    return array_combine( $keys, $array );
		}

		public function update_quick_cart_contents( $old_product_id, $product_id, $product_options ){

			if( !intval( $product_id ) ) :
				return false;
			endif;			
						
			$product = $this->change_key( get_option( $this->users_quick_cart() ), $old_product_id, $product_id );	
			
			$product[$product_id] = array(
				'title'	=>	get_the_title( $product_id ),
				'quantity'	=>	$product_options['quantity'],
				'rental_term'	=>	$product_options['rental_term']				
			);

			return update_option( $this->users_quick_cart(), $product );
		}

		/**
		 * set_cart_contents
		 * adds and appends products to the cart. 
		 * This will automatically adjust quantities too
		 */
		public function set_quick_cart_contents( $product_id, $product_options ){
			
			if( !intval( $product_id ) ) :
				return false;
			endif;			

			$product = get_option( $this->users_quick_cart() );	
						
			if( $product_options['quantity'] <= 0 ) :			
				unset( $product[$product_id] );
			elseif( $product[$product_id] ) :							
				$product[$product_id]['quantity'] = $product_options['quantity'];
			else:	
			
				$product[$product_id] = array(
					'title'	=>	get_the_title( $product_id ),
					'quantity'	=>	$product_options['quantity'],
					'rental_term'	=>	$product_options['rental_term']			
				);
			endif;

			return update_option( $this->users_quick_cart(), $product );
		}

		public function quick_quote_quantity(){
			
			if( wp_verify_nonce( $_POST['_wpnonce'], 'qq_product_add' ) ) :			

				$old_product_id = ( $_POST['old_product_id'] ) ? $_POST['old_product_id'] : null;

				$product_id = $_POST['product_id'];
				$quantity = $_POST['qty'];
				$rental_term = $_POST['rental_term'];

				$product_options = array(
					'quantity' => $quantity,
					'rental_term'	=>	$rental_term
				);


				$add_product = ( $old_product_id ) ? $this->update_quick_cart_contents( $old_product_id, $product_id, $product_options ) : $this->set_quick_cart_contents( $product_id, $product_options );

				$response = $add_product;
				
				echo json_encode( $response );
				die();

			endif;
			
		}

		public function quick_quote_select(){			
			
			$taxonomy = idea_rental()->taxonomy_product_cat;

			// retrieve subcategories based on parent term_id
			if( wp_verify_nonce( $_POST['_wpnonce'], 'get_sub_category' ) ) :			
				
				$parent_term_id = $_POST['category'];				
				$terms = get_terms( $taxonomy, 
					array(
						'parent' => $parent_term_id, 
						'orderby' => 'menu_order', 
						'hide_empty' => true
						//'hide_empty' => false
					)
				);

				$response['subcategories'] = $terms;

			endif;

			// retrieve posts based on subcategory term_id
			if( wp_verify_nonce( $_POST['_wpnonce'], 'get_products' ) ) :														
			
				$subcategory_term_id = (int)$_POST['subcategory'];
				
				$products = idea_products()->category_has_products( $taxonomy, $subcategory_term_id );
				$response['products'] = $products;

			endif;

			// make sure the post is valid, and is part of the product post type
			if( wp_verify_nonce( $_POST['_wpnonce'], 'check_valid_product' ) ) :							
				$product_post_id = $_POST['product'];				
				if( get_post_type( $product_post_id ) == 'product' ) :
					$response['product_check'] = true;
				endif;
			endif;

			echo json_encode( $response );
			die();	
		}

		public function idea_quick_quote_enqueue(  ){
			
			global $post;
			$post_id = $post->ID;

			if( $this->is_quick_quote_page( $post_id ) ) :
				wp_enqueue_script('jquery');  		    
				
				// nprogress.js for ajax loading bar
				wp_enqueue_script( 'nprogress', $this->plugin_url() . '/assets/js/nprogress/nprogress.js', array( 'jquery', 'idea-quick-quote' ), '', true ); 
				wp_enqueue_style( 'nprogress', $this->plugin_url() . '/assets/js/nprogress/nprogress.css', array(), '' );

				wp_register_script( 'idea-quick-quote', $this->plugin_url() . '/assets/js/idea-quick-quote.js', array( 'jquery' ), '', true ); 
				wp_localize_script( 'idea-quick-quote', 'qq', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'quick_quote_hidden_id' => '#input_1_15' ) );				
				wp_enqueue_script( 'idea-quick-quote' );  				
			endif;

		}


		/**
		 * the_cart_table
		 * Adds the cart html table to the cart page using add_filter( 'the_content' );
		 */
		public function the_quick_quote_form( $content ){			
			// if the current page is the cart page, display the cart table before the content
			global $post;
			$post_id = $post->ID;
			if( $this->is_quick_quote_page( $post_id ) ){				
				$the_content .= $this->get_quick_quote_table();
				$the_content .= $content;
				return $the_content;			
			}else{
				return $content;
			}
		}

		public function get_product_parent_categories( $taxonomy = 'product-cat' ){			
			$current_term = $wp_query->query_vars[$taxonomy];								
			//This gets top layer terms only.  This is done by setting parent to 0.  			
			return get_terms( $taxonomy, array('parent' => 0, 'orderby' => 'menu_order', 'hide_empty' => false));   
		}

		private function get_quick_quote_form_fields( $count = 4 ){
			
			$taxonomy = idea_rental()->taxonomy_product_cat;

			$current_cart_items = idea_cart()->get_cart_contents( null, true );

			$c = 0;
			

			if( $current_cart_items ) :
				foreach( $current_cart_items as $key => $cart_item ) :
					$products[$c]['pid'] = $key;
					$products[$c]['title'] = $cart_item['title'];
					$products[$c]['quantity'] = $cart_item['quantity'];
					$products[$c]['rental_term'] = $cart_item['rental_term'];
					$c++;
				endforeach;
			endif;

			for( $i=0; $i<$count; $i++ ) :

				$form_number = $i+1;
				$content .= '<tr data-select-group="' . $form_number . '" data-product="' . $products[$i]['pid'] . '">';
					$content .= '<td class="select">
									<select name="category" data-nonce="' . wp_create_nonce( 'get_sub_category' ) . '" autocomplete="off">
										<option>Select Category</option>';																
					
					$child_term = array_shift( wp_get_post_terms( $products[$i]['pid'], $taxonomy, $args ) );			
					$parent_cats = $this->get_product_parent_categories();	

					foreach($parent_cats as $p_cat) :	

						if( idea_products()->category_has_products( null, $p_cat->term_id ) ) :

							$content .= '<option value="' . $p_cat->term_id . '" ' . ( $p_cat->term_id == $child_term->parent ? "selected=selected" : "" ) . '>' . $p_cat->name . '</option>';
						endif;

					endforeach;


					$content .= '
									</select>
								</td>';
					$content .= '<td class="select">
									<select name="subcategory" data-nonce="' . wp_create_nonce( 'get_products' ) . '" ' . ( !$products[$i]['pid'] && !$child_term->parent ? 'disabled="true"' : '' ) . 'autocomplete="off">
										<option>Select Subcategory</option>';
										
										if( $products[$i]['pid'] && $child_term->parent ) :

											$terms = get_terms( $taxonomy, 
												array(
													'parent' => $child_term->parent, 
													'orderby' => 'menu_order', 
													'hide_empty' => false
												)
											);

											foreach( $terms as $term ) :
												$content .= '<option value="' . $term->term_id . '" ' . ( $term->term_id == $child_term->term_id ? "selected=selected" : "" ) . '>' . $term->name . '</option>';
											endforeach;											
										endif;

					$content .= 	'</select>
								</td>';
					$content .= '<td class="select">
									<select class="qq_product_id" name="product" data-nonce="' . wp_create_nonce( 'check_valid_product' ) . '"' . ( !$products[$i]['pid'] && !$child_term->term_id ? 'disabled="true"' : '' ) . ' autocomplete="off">
										<option>Select Product</option>';

										if( $products[$i]['pid'] && $child_term->term_id ) :											
											$current_products = idea_products()->category_has_products( $taxonomy, $child_term->term_id );																																
											
											foreach( $current_products as $current_product ) :												
												$content .= '<option value="' . $current_product->ID . '" ' . ( $current_product->ID == $products[$i]['pid'] ? "selected=selected" : "" ) . '>' . $current_product->post_title . '</option>';
											endforeach;

										endif;

					$content .= '
									</select>
								</td>';

								$rental_terms = array( 'Event or Trade Show Rental', 'Monthly Business Rental', 'Weekly Business Rental', 'Daily Business Rental' );
								
					$content .= '<td class="select">
									<select class="qq_rental_term" name="rental_term" ' . ( !$products[$i]['rental_term'] ? 'disabled="true"' : ''  ) . ' autocomplete="off">
										<option selected="selected">Select Rental Term</option>';
										
										foreach( $rental_terms as $rental_term ) :
											$content .= "<option value='$rental_term' " . ( $rental_term == $products[$i]['rental_term'] ? "selected=selected" : "" ) . ">$rental_term</option>";
										endforeach;

					$content .= '
									</select>
								</td>';
					$content .= '<td class="quantity">
									<input type="text" data-nonce="' . wp_create_nonce( 'qq_product_add' ) . '" name="qty" placeholder="Qty" autocomplete="off" value="' . ( $products[$i]['quantity'] ? $products[$i]['quantity'] : '' ) . '">
								</td>';
				$content .= '</tr>';

			endfor;

			return $content;

		}

		/**
		 * get_cart_table
		 * Generates the cart table to be later inserted before the content on the cart page
		 */
		public function get_quick_quote_table( $quote_id = null, $echo = false, $email = false ){
			
			//$products = $this->get_cart_contents( $quote_id );

			$content .= "<div class='rental-cart quick-quote-table'>";

					$content .= "<form id='quick-quote-form'>";

						$content .= '<table role="grid" ' . ( $email ? 'cellspacing="0" cellpadding="10" width="100%" style="max-width: 768px;border:1px solid #ddd;"' : '' ) . '>';
						$content .= '
							<tbody>';
						$content .= '<tr>';
							$content .= '<td colspan="5">';
								$content .= "<div id='nprogress-bar'></div>";
							$content .= '</td>';
						$content .= '</tr>';
						$thumbnail = ( !$email ? 'thumbnail' : 'idea_cart_small' );					
							
							$content .= $this->get_quick_quote_form_fields(4);

						$content .= '							
							</tbody>
						</table>';

						if( !$quote_id ) :
							
							$content .= "<div class='other-utility'>";
								$content .= "<ul>";
									//$content .= "<li><a href='" . get_permalink( $this->get_page_categories_listings() ) . "' class='rental_button'>continue browsing</a></li>";							
								$content .= "</ul>";
								$content .= "<br clear='all'>";
							$content .= "</div>";


							$content .= "<div class='cart-utility'>";
								$content .= "<ul>";
									$content .= "<li class='quick-cart-total-wrap'>Cart Quantity Total: <span id='quick-cart-total'>0</span></li>";
								$content .= "</ul>";
								$content .= "<br clear='all'>";
							$content .= "</div>";

						endif;

					$content .= "</form>";

			$content .= "</div>";

			if( $quote_id && $echo ) :
				echo $content;
			else:
				return $content;
			endif;

		}

		/**
		 * is_quick_quote_page
		 * checks if current page is the quick quote page
		 */
		public function is_quick_quote_page( $current_page_id ){			
			if( $current_page_id == $this->get_page_rental_quick_quote_form() )
				return true;			
		}

		
	} // eof: Idea_quick_quote class

endif; // eof: class_exists::Idea_quick_quote

function Idea_quick_quote(){
	return Idea_quick_quote::instance();	
}

$GLOBALS['idea_quick_quote'] = idea_quick_quote();
?>