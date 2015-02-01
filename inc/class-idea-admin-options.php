<?php

function load_custom_wp_admin_style() {
		global $wp;
		/*
        wp_register_style( 'custom_wp_admin_css', get_template_directory_uri() . '/admin-style.css', false, '1.0.0' );
        wp_enqueue_style( 'custom_wp_admin_css' );
        */
        wp_register_script(
		'med-upload',
		trailingslashit( get_template_directory_uri() ) . "js/custom-options.js",
		array( 'jquery' ),
		null,
		true
		);

		wp_enqueue_script( 'med-upload' );

}

//add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

if( !class_exists( 'Idea_admin_options' ) ) :

    class Idea_admin_options extends Idea_rental {
        /**
         * Holds the values to be used in the fields callbacks
         */
        private $options;

        /**
         * Start up
         */
        public function __construct() {

            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );   

            // custom meta boxes
            add_action('add_meta_boxes', array( $this, 'add_my_meta_boxes' ) );   

        }

        function add_my_meta_boxes() {
           add_meta_box('quote-products', 'Quote Contents', array( $this, 'show_gravity_form_submission' ), 'quote', 'normal', 'high');
        }

        function show_gravity_form_submission( $post ) {           
            
            // Show Gravity form entry in post type
            $gf_entry_id = get_post_meta( $post->ID, '_gf_lead_id', true );

            include( ABSPATH . 'wp-content/plugins/gravityforms/entry_detail.php' );
            
            $form = GFAPI::get_form( $this->get_page_rental_form_id() );
            $lead = GFAPI::get_entry( $gf_entry_id );
                                    
            foreach( $lead as $lead_value ) :                            
                if( trim($lead_value) == 'quick_quote' ) :                    
                    $quick_quote = true;                  
                endif;
            endforeach;

            idea_cart()->get_cart_table( $post->ID, true, false, $quick_quote );                        
            
            echo "<div class='lead-submission'>";
              GFEntryDetail::lead_detail_grid( $form, $lead, false);
            echo "</div>";
        }

        /**
         * Add options page
         */
        public function add_plugin_page() {
            // This page will be under "Settings"
            
            add_options_page(
                'Rental Settings', 
                'Idea Rental Settings', 
                'manage_options', 
                'idea-settings-admin', 
                array( $this, 'create_admin_page' )
            );
            
            add_menu_page( "Rental Settings", "Rental Settings", 'manage_options', 'options-general.php?page=idea-settings-admin', false, false, '3.08');

        }

        /**
         * Options page callback
         */
        public function create_admin_page() {
            // Set class property
            $this->options = get_option( 'idea_rental_options' );
            ?>
            <div class="wrap">
                <?php screen_icon(); ?>
                <h2>Idea Rental Settings</h2>           
                <form method="post" action="options.php">
                <?php
                    // This prints out all hidden setting fields
                    settings_fields( 'my_option_group' );   
                    do_settings_sections( 'idea-settings-admin' );
                    submit_button(); 
                ?>
                </form>
            </div>
            <?php
        }

        /**
         * Register and add settings
         */
        public function page_init() {        

            register_setting(
                'my_option_group', // Option group
                'idea_rental_options', // Option name
                array( $this, 'sanitize' ) // Sanitize
            );

            /* BOF: Page Settings */
            add_settings_section(
                'rental_cart_page_settings', // ID
                'Rental Cart Page Settings', // Title
                array( $this, 'print_section_info' ), // Callback
                'idea-settings-admin' // Page
            );  

                add_settings_field(
                    'idea_rental_form_page', // ID
                    'Rental Submit Form Page', // Title 
                    array( $this, 'cb_rental_form_page' ), // Callback
                    'idea-settings-admin', // Page
                    'rental_cart_page_settings' // Section           
                );      

                add_settings_field(
                    'idea_rental_quick_quote_form_page', // ID
                    'Rental Submit Quick Quote Form Page', // Title 
                    array( $this, 'cb_rental_quick_quote_form_page' ), // Callback
                    'idea-settings-admin', // Page
                    'rental_cart_page_settings' // Section           
                );      

                add_settings_field(
                    'idea_rental_categories_listings_page', // ID
                    'Category Listings Page', // Title 
                    array( $this, 'cb_rental_categories_page' ), // Callback
                    'idea-settings-admin', // Page
                    'rental_cart_page_settings' // Section           
                );    

                add_settings_field(
                    'idea_rental_thank_you_page', // ID
                    'Rental Thank You Page', // Title 
                    array( $this, 'cb_rental_thank_you_page' ), // Callback
                    'idea-settings-admin', // Page
                    'rental_cart_page_settings' // Section           
                );      

            /* EOF: Page Settings */

            
            /* BOF: Gravity Form Settings */            
            add_settings_section(
                'rental_cart_gravity_forms_settings', // ID
                'Gravity Forms Settings', // Title
                array( $this, 'print_gf_section_info' ), // Callback
                'idea-settings-admin' // Page
            );  


                add_settings_field(
                    'idea_rental_gravity_form', // ID
                    'Gravity Form ID', // Title 
                    array( $this, 'cb_rental_gravity_form' ), // Callback
                    'idea-settings-admin', // Page
                    'rental_cart_gravity_forms_settings' // Section           
                );      

                add_settings_field(
                    'idea_rental_gravity_field_first_name', // ID
                    'Form Field First Name<br>(or full name field)', // Title 
                    array( $this, 'cb_rental_gravity_form_field_first_name' ), // Callback
                    'idea-settings-admin', // Page
                    'rental_cart_gravity_forms_settings' // Section           
                );

                add_settings_field(
                    'idea_rental_gravity_field_last_name', // ID
                    'Form Field Last Name', // Title 
                    array( $this, 'cb_rental_gravity_form_field_last_name' ), // Callback
                    'idea-settings-admin', // Page
                    'rental_cart_gravity_forms_settings' // Section           
                );
            
            /* EOF: Gravity Form Settings */

                

        }

        /**
         * Sanitize each setting field as needed
         *
         * @param array $input Contains all settings fields as array keys
         */
        public function sanitize( $input ) {
            
            $new_input = array();
            
            if( isset( $input['idea_rental_gravity_form'] ) )
                $new_input['idea_rental_gravity_form'] = absint( $input['idea_rental_gravity_form'] );
            
            if( isset( $input['idea_rental_form_page'] ) )
                $new_input['idea_rental_form_page'] = absint( $input['idea_rental_form_page'] );

            if( isset( $input['idea_rental_quick_quote_form_page'] ) )
                $new_input['idea_rental_quick_quote_form_page'] = absint( $input['idea_rental_quick_quote_form_page'] );

            if( isset( $input['idea_rental_thank_you_page'] ) )
                $new_input['idea_rental_thank_you_page'] = absint( $input['idea_rental_thank_you_page'] );

            if( isset( $input['idea_rental_categories_listings_page'] ) )
                $new_input['idea_rental_categories_listings_page'] = absint( $input['idea_rental_categories_listings_page'] );

            if( isset( $input['idea_rental_gravity_field_first_name'] ) )
                $new_input['idea_rental_gravity_field_first_name'] = absint( $input['idea_rental_gravity_field_first_name'] );

            if( isset( $input['idea_rental_gravity_field_last_name'] ) )
                $new_input['idea_rental_gravity_field_last_name'] = absint( $input['idea_rental_gravity_field_last_name'] );

            return $new_input;

        }

        /** 
         * Print the Section text
         */
        public function print_section_info() {
            print 'Select the pages below to match them up with the Idea Rental Pages';
        }

        /** 
         * Print the Section text
         */
        public function print_gf_section_info() {
            $content .= "
            <ol>
                <li><a href='" . get_admin_url() . "admin.php?page=gf_new_form'>Create Gravity Form</a> and select it below</li>
                <li>Select the First Name field of selected gravity form</li>
                <li>Select the Last Name field of selected gravity form</li>
            </ol>
            <p><strong style='color: red;'>If your gravity form only has a Name field for both First and Last Name, select it for the First Name, and leave the Last Name option as 'Select Last Name Field'</strong></p>
            ";
            echo $content;
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function cb_rental_form_page() {
                        
            $args = array(
                'post_type'         =>  'page',
                'post_status'       =>  'publish',
                'posts_per_page'    =>  -1,
                'orderby'           =>  'title',
                'order'             =>  'asc'
            );

            $pages = new WP_Query( $args );
            
            if( $pages->have_posts() ) :
                $content .= "<select name='idea_rental_options[idea_rental_form_page]'>";
                        $content .= "<option>Select a Page</option>";
                    while( $pages->have_posts() ) : $pages->the_post();
                        $selected = ( $this->options['idea_rental_form_page'] == get_the_ID() ) ? "selected" : "";
                        $content .= "<option value='" . get_the_id() . "' $selected>" . get_the_title() . "</option>";
                    endwhile;
                $content .= "</select>";
                $content .= "<p>This page is used to display and send the quote form</p>";
            endif;
            
            echo $content;

            wp_reset_query();

        }        

        /** 
         * Get the settings option array and print one of its values
         */
        public function cb_rental_quick_quote_form_page() {
                        
            $args = array(
                'post_type'         =>  'page',
                'post_status'       =>  'publish',
                'posts_per_page'    =>  -1,
                'orderby'           =>  'title',
                'order'             =>  'asc'
            );

            $pages = new WP_Query( $args );
            
            if( $pages->have_posts() ) :
                $content .= "<select name='idea_rental_options[idea_rental_quick_quote_form_page]'>";
                        $content .= "<option>Select a Page</option>";
                    while( $pages->have_posts() ) : $pages->the_post();
                        $selected = ( $this->options['idea_rental_quick_quote_form_page'] == get_the_ID() ) ? "selected" : "";
                        $content .= "<option value='" . get_the_id() . "' $selected>" . get_the_title() . "</option>";
                    endwhile;
                $content .= "</select>";
                $content .= "<p>This page is used to display and send the QUICK quote form</p>";
            endif;
            
            echo $content;

            wp_reset_query();

        }        

        /** 
         * Get the settings option array and print one of its values
         */
        public function cb_rental_thank_you_page() {
                        
            $args = array(
                'post_type'         =>  'page',
                'post_status'       =>  'publish',
                'posts_per_page'    =>  -1,
                'orderby'           =>  'title',
                'order'             =>  'asc'
            );

            $pages = new WP_Query( $args );
            
            if( $pages->have_posts() ) :
                $content .= "<select name='idea_rental_options[idea_rental_thank_you_page]'>";
                        $content .= "<option>Select a Page</option>";
                    while( $pages->have_posts() ) : $pages->the_post();
                        $selected = ( $this->options['idea_rental_thank_you_page'] == get_the_ID() ) ? "selected" : "";
                        $content .= "<option value='" . get_the_id() . "' $selected>" . get_the_title() . "</option>";
                    endwhile;
                $content .= "</select>";
                $content .= "<p>This page is used to display the Thank You page after completed checkout</p>";
            endif;
            
            echo $content;

            wp_reset_query();

        }        

        public function cb_rental_categories_page() {            
            
            $args = array(
                'post_type'         =>  'page',
                'post_status'       =>  'publish',
                'posts_per_page'    =>  -1,
                'orderby'           =>  'title',
                'order'             =>  'asc'
            );

            $pages = new WP_Query( $args );
            
            if( $pages->have_posts() ) :
                $content .= "<select name='idea_rental_options[idea_rental_categories_listings_page]'>";
                        $content .= "<option>Select a Page</option>";
                    while( $pages->have_posts() ) : $pages->the_post();
                        $selected = ( $this->options['idea_rental_categories_listings_page'] == get_the_ID() ) ? "selected" : "";
                        $content .= "<option value='" . get_the_id() . "' $selected>" . get_the_title() . "</option>";
                    endwhile;
                $content .= "</select>";
                $content .= "<p>This page is used to redirect back to the categories page to continue browsing, and other actions</p>";
            endif;
            
            echo $content;

            wp_reset_query();

        }        

        public function cb_rental_gravity_form(){        
            
            $forms = $this->get_active_gravity_forms();

            //$content .= "<input type='text' name='idea_rental_options[idea_rental_gravity_form]' value='" . ( $this->options['idea_rental_gravity_form'] ? $this->options['idea_rental_gravity_form'] : "") . "'>";
            
            $content .= "<select name='idea_rental_options[idea_rental_gravity_form]'>";
                $content .= "<option>Select a Gravity Form</option>";
                if( count( $forms ) ) :
                    foreach( $forms as $form ) :
                        $content .= "<option value='" . $form->id . "'";
                                $content .= ( $this->options['idea_rental_gravity_form'] == $form->id ? "selected" : "" );
                            $content .= ">";
                        $content .= $form->id . ": " . $form->title. "</option>";                        
                    endforeach;
                endif;
            $content .= "</select>";
            echo $content;

        }

        public function get_active_gravity_forms(){            
            global $wpdb;
            $forms = $wpdb->get_results( "SELECT id, title FROM " . $wpdb->prefix . "rg_form WHERE is_active = '1'" );            
            return $forms;
        }

        public function cb_rental_gravity_form_field_first_name(){
            $fields = $this->get_gravity_form_fields( $this->options['idea_rental_gravity_form'] );

            $content .= "<select name='idea_rental_options[idea_rental_gravity_field_first_name]' " . ( $this->options['idea_rental_gravity_form'] ? '' : "disabled" ) . ">";
                $content .= "<option>Select First Name Field</option>";
                if( count( $fields ) ) :
                    foreach( $fields as $field ) :                        
                        $content .= "<option value='" . $field['field_id'] . "'";   
                                $content .= ( $this->options['idea_rental_gravity_field_first_name'] == $field['field_id'] ? "selected" : "" );    
                            $content .= ">";
                        $content .= $field['label']. "</option>";                        
                    endforeach;
                endif;
            $content .= "</select>";

            if( !$this->options['idea_rental_gravity_form'] ) :
                $content .= '<br><small style="color: red;">Please select a gravity form</small>';
            endif;
            
            echo $content;

        }

        public function cb_rental_gravity_form_field_last_name(){
            $fields = $this->get_gravity_form_fields( $this->options['idea_rental_gravity_form'] );
            
            $content .= "<select name='idea_rental_options[idea_rental_gravity_field_last_name]' " . ( $this->options['idea_rental_gravity_form'] ? '' : "disabled" ) . ">";
                $content .= "<option>Select Last Name Field</option>";
                if( count( $fields ) ) :
                    foreach( $fields as $field ) :                        
                        $content .= "<option value='" . $field['field_id'] . "'";   
                                $content .= ( $this->options['idea_rental_gravity_field_last_name'] == $field['field_id'] ? "selected" : "" );                             
                            $content .= ">";
                        $content .= $field['label']. "</option>";                        
                    endforeach;
                endif;
            $content .= "</select>";

            if( !$this->options['idea_rental_gravity_form'] ) :
                $content .= '<br><small style="color: red;">Please select a gravity form</small>';
            endif;
            
            echo $content;

        }


        public function get_gravity_form_fields( $gravity_form_id ){
            
            if( $gravity_form_id ) :
                global $wpdb;
                $forms = $wpdb->get_var( "SELECT display_meta FROM " . $wpdb->prefix . "rg_form_meta WHERE form_id = '$gravity_form_id'" );            
                $fields = json_decode( $forms );            
                
                foreach( $fields->fields as $key => $field ) :

                    if( $field->type != 'section' ) :
                        $valid_fields[$key]['field_id'] = $field->id;
                        $valid_fields[$key]['label'] = $field->label;
                    endif;

                endforeach;

                return $valid_fields;   
            endif;

        }


    } // eof: class


endif;

if( is_admin() )
    $idea_admin_options = new Idea_admin_options();


/*
add_action( 'init', 'delete_quotes' );
function delete_quotes(){
    $args = array(
        'post_type'         =>     'quote',
        'posts_per_page'    =>     -1,
        'post_status'       =>      'any'
    );
    
    $quote = new WP_Query( $args );
    
    if ( $quote->have_posts() ) :
        
        while ( $quote->have_posts() ) : $quote->the_post();
            wp_delete_post( get_the_ID(), true );
        endwhile;
    
    endif;
        
}
*/

?>
