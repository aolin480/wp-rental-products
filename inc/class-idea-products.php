<?php

if( !class_exists( 'Idea_products' ) ) :
	
	class Idea_products extends Idea_rental{

		protected static $_instance = null;
		
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
			
		}

		function __construct(){
			add_action( 'pre_get_posts', array( $this, 'pagination_set_per_page' ) );
		}

		function category_has_products( $taxonomy = null, $term_id = null ){
			
			// if taxonomy is not defined, see if the global taxonomy has been set
			if( !$taxonomy && idea_rental()->taxonomy_product_cat )
				$taxonomy = idea_rental()->taxonomy_product_cat;

			if( $taxonomy && $term_id ) :				
				$args = array(
					'post_type'			=> 	'product',
					'posts_per_page'	=> 	-1,
					'tax_query'			=>	array(
						array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $term_id,							
						)
					),
				);
				
				$product = new WP_Query( $args );
				
				if ( $product->have_posts() ) :					
					$all_products = $product->get_posts();
					return $all_products;
				endif;									
			endif;

		}

		/* Modify posts per page on product-cat taxonomy page */
		function pagination_set_per_page( $query ) {
		  
		  // not an admin page and it is the main query
		  if (!is_admin() && $query->is_main_query()){

		    if(is_tax( 'product-cat' )){
		    	if( ( isset( $_GET['per_page'] ) && (int)$_GET['per_page'] > 0 ) || $_GET['per_page'] == 'all' ) :
		    		$posts_per_page = ( $_GET['per_page'] == 'all' ) ? -1 : $_GET['per_page'];
					$query->set( 'posts_per_page', $posts_per_page );
				else :
					$query->set( 'posts_per_page', 8 );
				endif;
		    }

		  }

		}


		public function add_to_quote_button( $title, $id ){			
			return false;
		}
		
	}

endif; // eof: class_exists::idea_products

function idea_products(  ){
	return Idea_products::instance();
}
$GLOBALS['idea_products'] = idea_products();

/* Frontend Product Functions */

function display_products( $post_parent, $post_type, $product_category ){
	// Enter parent that the children should show products on
	// $product_category is getting pulled from Advanced Custom Field on page
	global $post;	
	$current_page_id = $post->ID;
	$required_parent = 8;
	
	if ($post_parent == $required_parent) {
		$args = array(
			'post_type' => $post_type,
			'posts_per_page' => 99,
			'tax_query' => array(
					array(
						'taxonomy' => 'product-cat',
						'field' => 'slug',
						'terms' => $product_category,
					)
			 ),
		);

		$prods = new WP_Query($args);
		if($prods->have_posts()):
			while($prods->have_posts()): $prods->the_post();
				$image_id = get_post_thumbnail_id();
				$post_image_data = wp_get_attachment_image_src($image_id, 'large');
				$content .= '<div class="product">';
						$content .= '<a href="'.$post_image_data[0].'" rel="prettyphoto">';
							$content .= get_the_post_thumbnail($post->ID,'product_image');
						$content .= '</a>';
					$content .= '<h2>'. get_the_title() . '</h2>';
					//$content .= '<a class="quote-btn" href="#">Add to Quote</a>'; 
					$content .= apply_filters( 'rental_button_add_to_quote', $current_page_id, get_the_ID() );
					$content .= '<p>'.get_field('item_number').'</p>';
				$content .= '</div>';

			endwhile;
			//	echo '<div class="clr"></div>';
		else:
			 $content .= "<h3>No products found.</h3>";
		endif;

		echo $content;
	}
}

function get_acf_repeater_data( $parent_field = null, $sub_fields = array(), $post_id = null ){
  
  if( !$parent_field )
    return false;

  if( !$post_id ):
    global $post;
    $post_id = $post->ID;
  endif;

  if( have_rows( $parent_field ) ):
      
      $i=0;
      while ( have_rows( $parent_field, $post_id ) ) : the_row();
          foreach( $sub_fields as $key => $sub_field ) :
            $field_data[$i][$sub_field] = get_sub_field( $sub_field, $post_id );
          endforeach;
          $i++;
      endwhile;

      return $field_data;

  endif;

}


function the_product_specs( $post_id = null ){
	
	if( !$post_id ) :
		global $post;
		$post_id = $post->ID;
	endif;
	
	$specs = get_acf_repeater_data( 'product_specs', array( 'spec_name', 'spec_value' ), $post_id );
	
	
	if( count( $specs ) ) :
		$content .= "<div class='product-specs'>";
			$content .= "<table>";
				$content .= "<thead>";
					$content .= "<tr><th colspan='2' align='left'>SPECIFICATIONS</th></tr>";
				$content .= "</thead>";

			$content .= "<tbody>";
				$i = 0;
				foreach( $specs as $key => $spec ) :				
					$content .= "<tr class='" . ( $i % 2 != 0 ? 'even' : 'odd' ) . "'>";
						$content .= "<td class='spec_name'>{$spec['spec_name']}</td>";
						$content .= "<td class='spec_value'>{$spec['spec_value']}</td>";				
					$content .= "</tr>";
					$i++;
				endforeach;
			$content .= "</tbody>";
			$content .= "</table>";
		$content .= "</div>";

	endif;

	echo $content;
	
}

?>