<?php 

if (!class_exists('Idea_post_status')):

    class Idea_post_status extends Idea_rental {

        protected static $_instance = null;

        public static function instance() {         
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        function __construct(){            

            add_action( 'init', array( $this, 'idea_custom_post_status' ) );            
            add_action( 'init', array( $this, 'change_quote_post_status' ) );

            add_action('admin_footer-post.php', array( $this, 'idea_append_post_status_list' ) );
            add_filter('display_post_states', array( $this, 'idea_display_archive_state' ) );
            add_action('admin_footer-edit.php', array( $this, 'idea_append_post_status_bulk_edit' ) );
            add_action( 'admin_menu', array( $this, 'add_unread_bubble' ) );

            // Constants
            define('CUSTOM_POST_TYPE', 'quote');            
            define('ELIGIBLE_POST_STATUS', 'unread');
        }

        public function change_quote_post_status(){     
            
            if( is_admin() ) :
                $post_id        =  $_GET['post'];
                $action         =  $_GET['action'];
                $post_status    =  get_post_status($post_id );
                $post_type      =  get_post_type( $post_id );

                
                if( $post_id && $action == 'edit' ) :
                    if( $post_status == 'unread' && $post_type == 'quote' ) :
                        $new_args = array( 
                            "ID"            => $post_id,
                            'post_status'   =>  'publish'
                        );
                        wp_update_post( $new_args );
                    endif;
                endif;

            endif;
        }

        // bubble notifications for custom posts with status pending        
        public function add_unread_bubble() {            
            global $menu;
            $custom_post_count = wp_count_posts('quote');            
            $custom_post_pending_count = $custom_post_count->unread;    

            if ( $custom_post_pending_count ) {
                foreach ( $menu as $key => $value ) {
                    if ( $menu[$key][2] == 'edit.php?post_type=quote' ) {
                        $menu[$key][0] .= ' <span class="update-plugins count-' . $custom_post_pending_count . '"><span class="plugin-count">' . $custom_post_pending_count . '</span></span>';
                        return;
                    }
                }
            }
        }

        public function idea_custom_post_status() {

            register_post_status(ELIGIBLE_POST_STATUS, array(        
                'label' => _x(ELIGIBLE_POST_STATUS, CUSTOM_POST_TYPE),        
                'public' => true,        
                /*        
                'show_in_admin_all_list'    => true,        
                'show_in_admin_status_list' => true,        
                'exclude_from_search'       => false,        
                */        
                'label_count' => _n_noop(ucwords(ELIGIBLE_POST_STATUS) . ' <span class="count">(%s)</span>', ucwords(ELIGIBLE_POST_STATUS) . ' <span class="count">(%s)</span>')        
            ));
        }
        
        public function idea_append_post_status_list() {
            
            global $post;
            $complete = '';    
            $label = '';

            if ($post->post_type == CUSTOM_POST_TYPE):
                if ($post->post_status == ELIGIBLE_POST_STATUS) {            
                    $complete = ' selected=\"selected\"';            
                    $label = '<span id=\"post-status-display\"> ' . ucwords(ELIGIBLE_POST_STATUS) . '</span>';            
                }
                
                echo '
                  <script>
                  jQuery(document).ready(function($){
                       $("select#post_status").append("<option value=\"' . ELIGIBLE_POST_STATUS . '\" ' . $complete . '>' . ucwords(ELIGIBLE_POST_STATUS) . '</option>");
                       $(".misc-pub-section label").append("' . $label . '");
                  });
                  </script>
                  ';
            endif;
            
        }
        
        function idea_display_archive_state($states) {            
            global $post;    
            $arg = get_query_var('post_status');
            if ($arg != 'eligible') {        
                if ($post->post_status == ELIGIBLE_POST_STATUS) {            
                    return array(
                        ucwords(ELIGIBLE_POST_STATUS)
                    );            
                }        
            }
            return $states;
        }

        /* Adding custom post status to Bulk and Quick Edit boxes: Status dropdown */
        function idea_append_post_status_bulk_edit() {
            global $post;
            
            if ($post->post_type == CUSTOM_POST_TYPE) {                
                echo '<script>
                       jQuery(document).ready(function($){
                            $(".inline-edit-status select ").append("<option value=\'' . ELIGIBLE_POST_STATUS . '\'>' . ucwords(ELIGIBLE_POST_STATUS) . '</option>");
                       });
                </script>';                
            }

        }

    }

endif;

function idea_post_status(){
    return Idea_post_status::instance();    
}

$GLOBALS['idea_post_status'] = idea_post_status();
?>