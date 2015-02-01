<?php

if (!class_exists('Idea_cpt')):

    class Idea_cpt extends Idea_rental {

    	protected static $_instance = null;

    	public static function instance() {			
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
    
        function __construct() {
            add_action( 'init', array( $this, 'cptui_register_my_cpt_products' ) );
            add_action( 'init', array( $this, 'cptui_register_my_cpt_quote' ) );
            add_action( 'pre_get_posts', array( $this, 'disable_quote_front_view' ) );
            add_action('init', array( $this, 'cptui_register_my_taxes_product_cat') );
        }

        function disable_quote_front_view( $query ){        	
        	if( $query->query['post_type'] == 'quote' && !is_admin() )
        		exit();
        }
        
        function cptui_register_my_cpt_products() {
            register_post_type('product', array(
                'label' => 'Products',
                'description' => 'Modern Rental Products',
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'hierarchical' => false,
                'rewrite' => array(
                    'slug' => 'product',
                    'with_front' => true
                ),
                'query_var' => true,
                'menu_icon' => 'dashicons-products',
                'supports' => array(
                    'title',
                    'editor',
                    'excerpt',
                    'trackbacks',
                    'custom-fields',
                    'comments',
                    'revisions',
                    'thumbnail',
                    'author',
                    'page-attributes'
                ),
                'labels' => array(
                    'name' => 'Products',
                    'singular_name' => 'Product',
                    'menu_name' => 'Products',
                    'add_new' => 'Add Product',
                    'add_new_item' => 'Add New Product',
                    'edit' => 'Edit',
                    'edit_item' => 'Edit Product',
                    'new_item' => 'New Product',
                    'view' => 'View Product',
                    'view_item' => 'View Product',
                    'search_items' => 'Search Products',
                    'not_found' => 'No Products Found',
                    'not_found_in_trash' => 'No Products Found in Trash',
                    'parent' => 'Parent Product'
                )
            ));
        }
        
        function cptui_register_my_cpt_quote() {
            register_post_type('quote', array(
                'label' => 'Quotes',
                'description' => '',
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'hierarchical' => false,
                'rewrite' => array(
                    'slug' => 'quote',
                    'with_front' => true
                ),
                'query_var' => true,
                'menu_icon' => 'dashicons-format-quote',
                'supports' => array(
                    'title'                 
                ),
                'labels' => array(
                    'name' => 'Quotes',
                    'singular_name' => 'Quote',
                    'menu_name' => 'Quotes',
                    'add_new' => 'Add Quote',
                    'add_new_item' => 'Add New Quote',
                    'edit' => 'Edit',
                    'edit_item' => 'Edit Quote',
                    'new_item' => 'New Quote',
                    'view' => 'View Quote',
                    'view_item' => 'View Quote',
                    'search_items' => 'Search Quotes',
                    'not_found' => 'No Quotes Found',
                    'not_found_in_trash' => 'No Quotes Found in Trash',
                    'parent' => 'Parent Quote'
                )
            ));
        }


        function cptui_register_my_taxes_product_cat() {
        register_taxonomy( 'product-cat',array (
          0 => 'product',
        ),
        array( 'hierarchical' => true,
            'label' => 'Product Categories',
            'show_ui' => true,
            'query_var' => true,
            'show_admin_column' => false,
            'labels' => array (
          'search_items' => 'Product Category',
          'popular_items' => '',
          'all_items' => '',
          'parent_item' => '',
          'parent_item_colon' => '',
          'edit_item' => '',
          'update_item' => '',
          'add_new_item' => '',
          'new_item_name' => '',
          'separate_items_with_commas' => '',
          'add_or_remove_items' => '',
          'choose_from_most_used' => '',
        )
        ) ); 
        }
    }
endif;

function idea_cpt(){
	return Idea_cpt::instance();	
}

$GLOBALS['idea_cpt'] = idea_cpt();

/*
add_action('init', 'cptui_register_my_cpt_cpt_template');
function cptui_register_my_cpt_cpt_template() {
register_post_type('cpt_template', array(
'label' => 'CPT Templates',
'description' => '',
'public' => true,
'show_ui' => true,
'show_in_menu' => true,
'capability_type' => 'post',
'map_meta_cap' => true,
'hierarchical' => false,
'rewrite' => array('slug' => 'cpt_template', 'with_front' => true),
'query_var' => true,
'supports' => array('title'),
'labels' => array (
  'name' => 'CPT Templates',
  'singular_name' => 'CPT Template',
  'menu_name' => 'CPT Templates',
  'add_new' => 'Add CPT Template',
  'add_new_item' => 'Add New CPT Template',
  'edit' => 'Edit',
  'edit_item' => 'Edit CPT Template',
  'new_item' => 'New CPT Template',
  'view' => 'View CPT Template',
  'view_item' => 'View CPT Template',
  'search_items' => 'Search CPT Templates',
  'not_found' => 'No CPT Templates Found',
  'not_found_in_trash' => 'No CPT Templates Found in Trash',
  'parent' => 'Parent CPT Template',
)
) ); }
*/