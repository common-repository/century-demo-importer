<?php
/**
 * Plugin Name: Century Demo Importer
 * Plugin URI: https://wordpress.org/plugins/century-demo-importer
 * Description: The plugin is used for importing demos on the themes.
 * Version: 1.0.0
 * Author: Century Plugin
 * Author URI:  https://centuryplugin.com/
 * Text Domain: century-demo-importer
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 *
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die();
}

define( 'CDI_VERSION', '1.0.4' );

define( 'CDI_FILE', __FILE__ );
define( 'CDI_PLUGIN_BASENAME', plugin_basename( CDI_FILE ) );
define( 'CDI_PATH', plugin_dir_path( CDI_FILE ) );
define( 'CDI_URL', plugins_url( '/', CDI_FILE ) );

define( 'CDI_ASSETS_URL', CDI_URL . 'inc/assets/' );


if ( !class_exists( 'Century_Demo_Importer' ) ) {

    /**
     * Sets up and initializes the plugin.
     */
    class Century_Demo_Importer {



        /**
         * A reference to an instance of this class.
         *
         * @since  1.0.0
         * @access private
         * @var    object
         */
        private static $instance = null;

        /**
         * Plugin version
         *
         * @var string
         */
        private $version = CDI_VERSION;

        /**
         * Returns the instance.
         *
         * @since  1.0.0
         * @access public
         * @return object
         */
        public static function get_instance() {
            // If the single instance hasn't been set, set it now.
            if ( null == self::$instance ) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        /**
         * Sets up needed actions/filters for the plugin to initialize.
         *
         * @since 1.0.0
         * @access public
         * @return void
         */
        public function __construct() {


            // Load translation files
            add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

            // Load necessary files.
            add_action( 'plugins_loaded', array( $this, 'init' ) );
            add_action( 'admin_footer', array( $this, 'cdi_display_demo_iframe') );
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
            add_action('cdi_display_demos',array($this,'cdi_display_demos') );
            add_action( 'admin_menu', array( $this, 'cdi_register_menu' ) );

            //ajax actions for db reset    
            add_action( 'wp_ajax_cdi_demo_data_reset', array( $this, 'cdi_demo_data_reset' ) );
            add_action( 'wp_ajax_nopriv_cdi_demo_data_reset', array( $this, 'cdi_demo_data_reset' ) );

            add_filter( 'pt-ocdi/import_files', array( $this, 'cdi_ocdi_import_files') );
            add_action( 'pt-ocdi/after_import', array( $this, 'cdi_ocdi_after_import') );
            
            

        }

        /**
         * Loads the translation files.
         *
         * @since 1.0.0
         * @access public
         * @return void
         */
        public function load_plugin_textdomain() {
            load_plugin_textdomain( 'century-demo-importer', false, basename( dirname( __FILE__ ) ) . '/languages' );

        }

        /**
         * Returns plugin version
         *
         * @return string
         */
        public function get_version() {
            return $this->version;
        }

        /**
         * Manually init required modules.
         *
         * @return void
         */
        public function init() {

            require_once( CDI_PATH .'/inc/importers/class-helpers.php' );
            require( CDI_PATH . 'inc/demo-functions.php' );
            

        }

        /**
         * Load scripts
         *
         */
        public static function scripts( $hook_suffix ) {

            $template_slug = 'appearance_page_'.get_template().'-welcome-page';
            
            if ( ('appearance_page_demo-importer' == $hook_suffix) || ('appearance_page_welcome-page' == $hook_suffix) || ($template_slug == $hook_suffix) ) {

                // CSS
                wp_enqueue_style( 'cdi-demos-style', CDI_ASSETS_URL. 'css/demo-styles.css' );

                // JS
                wp_enqueue_script( 'cdi-demos-js', CDI_ASSETS_URL. 'js/demos.js', array( 'jquery', 'wp-util', 'updates' ), CDI_VERSION, true );

                wp_localize_script( 'cdi-demos-js', 'centuryLoc', array(
                    'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
                    'demo_data_nonce'           => wp_create_nonce( 'get-demo-data' ),
                    'cdi_import_data_nonce'     => wp_create_nonce( 'cdi_import_data_nonce' ),
                    'content_importing_error'   => esc_html__( 'There was a problem during the importing process resulting in the following error from your server:', 'century-demo-importer' ),
                    'button_activating'         => esc_html__( 'Activating', 'century-demo-importer' ) . '&hellip;',
                    'button_active'             => esc_html__( 'Active', 'century-demo-importer' ),
                    'button_activated'          => esc_html__( 'Activated', 'century-demo-importer' ),
                    'plugin_activate_info'      => esc_html__( 'Please install & activate all plugins first', 'century-demo-importer' ),
                    'reset_success'             => esc_html__( 'Data reset successful, the page is being reloaded.','century-demo-importer'),
                ) );

            }

        }


        /*
         *  Display the available demos
         */

        function cdi_display_demos() {

            $demos = CDI_Demos::get_demos_data();
            if( empty($demos)){
                esc_html_e('No demos are configured for this theme, please contact the theme author','century-demo-importer');
                return;
            }


            $prev_text      = esc_html__('Preview','century-demo-importer');
            $install_text   = esc_html__('Import','century-demo-importer');
            $pro_text       = esc_html__('Pro','century-demo-importer');
            $pro_upgrage    = esc_html__('Buy Now','century-demo-importer');

            ?>
            <div class="demos-wrapper clearfix">
                <div class="demos-top-title-wrapp">
                    <h3><?php esc_html_e('Choose one of the starter sites (demos) below, checkout preview and once you decide to install - go for it!','century-demo-importer'); ?></h3>
                    <p><?php esc_html_e('However please consider the following points before installing any demos: ','century-demo-importer'); ?></p>
                    <div class="info-wrapper">
                        <ul>
                            <li><?php esc_html_e('If your site already has content or is already in use, importing one of these demos is not suggested. The demo content and your existing content might mix up.','century-demo-importer'); ?></li>
                            <li><?php esc_html_e('Import demo on a FRESH WordPress installation to experience the exact demo you\'ve seen on our demo pages.','century-demo-importer'); ?> </li>
                            <li> <?php esc_html_e('Installing demo, will also install all the required plugins and activate them for you.','century-demo-importer'); ?> </li>
                            <li><?php esc_html_e('None of your existing content (page, post etc) will be deleted. ','century-demo-importer'); ?></li>
                            <li><?php esc_html_e('Demo installation will take some time, have patience. Reset the demo installation (option given at the bottom of this page) to start a new demo installation.','century-demo-importer'); ?> </li>

                        </ul>
                    </div>

                </div>

                <div class="demo-content-wrapper">
                    <?php 

                    foreach( $demos as $key => $demo ){ 

                        if( $key != 'premium_demos' ){
                            $demo_name = $demo['demo_name'];
                            ?>

                            <div class="demo">
                                <div class="img-wrapp">
                                    <a href="<?php echo esc_url($demo['preview_url']);?>" class="cdi-preview-url">
                                        <span class="preview-text"><?php echo esc_html($prev_text); ?></span>
                                        <img src="<?php echo esc_url($demo['screen']);?>">
                                    </a>
                                </div>
                                <div class="demo-btn-wrapp">
                                    <h4 class="demo-title"><?php echo esc_html($demo_name); ?></h4> 
                                    <div class="buttons-wrapp">
                                        <a href="#" class="button install-btn install-demo-btn-step cdi-open-popup" data-demo-id="<?php echo esc_attr($key); ?>"><?php echo $install_text; ?></a>
                                        <a href="<?php echo esc_url($demo['preview_url']);?>" class="button preview-btn button-primary" target="_blank"><?php echo esc_html($prev_text); ?></a>
                                    </div>
                                </div>
                            </div>
                        <?php } }

                        //pro demos 
                        $pro_demos = isset($demos['premium_demos']) ? $demos['premium_demos'] : '';

                        if( $pro_demos ):

                            foreach( $pro_demos as $pro_demo ){  ?>

                                <div class="demo pro-demo">
                                    <div class="img-wrapp">
                                        <a href="<?php echo esc_url($pro_demo['preview_url']);?>">
                                            <span class="preview-text"><?php echo esc_html($prev_text); ?></span>
                                            <img src="<?php echo esc_url($pro_demo['screen']);?>">
                                        </a>
                                    </div>
                                    <div class="demo-btn-wrapp">
                                        <h4 class="demo-title"><?php echo esc_html($pro_demo['demo_name']); ?></h4> 
                                        <div class="buttons-wrapp">
                                            <a href="<?php echo esc_url($pro_demo['upgrade_url']);?>" class="button " data-demo-id="<?php echo esc_attr($key); ?>" target="_blank"><?php echo esc_html($pro_upgrage); ?></a>
                                            <a href="<?php echo esc_url($pro_demo['preview_url']);?>" class="button preview-btn button-primary" target="_blank"><?php echo esc_html($prev_text); ?></a>
                                        </div>
                                    </div>
                                    <span class="pro-text"><?php echo esc_html($pro_text); ?></span>
                                </div>

                            <?php }
                        endif; 
                        ?>

                    </div>
                    
                </div>
                <?php $this->cdi_demo_data_reset_html(); 
            }

            public function cdi_display_demo_iframe(){

                $screen         = get_current_screen();  
                $template_slug  = 'appearance_page_'.get_template().'-welcome-page';

                if ( ('appearance_page_demo-importer' == $screen->id) || ('appearance_page_welcome-page' == $screen->id) || ($template_slug == $screen->id) ) {

                ?>
                    <div  class="cdi-popup-preview import-php hidden">                   
                        <div class="close-popup">
                            <i class="dashicons dashicons-no-alt"></i>
                            <span class="prev-close-info"><?php esc_html_e('Close Preview','century-demo-importer'); ?></span>
                        </div>
                        <div class="updating-message"></div>
                        <iframe id="cdi-popup-preview" src="" width="100%" height="100%"></iframe>
                    </div>

                    <div class="cdi-demo-confirm-message">
                        <div class="cdi-msg-wrapp">
                            <div class="cdi-msg-btn-wrapp">
                                <div class="conf-msg">
                                    <span class="conf-icon">?</span>
                                    <h2><?php esc_html_e('Are you sure you want to proceed ?','century-demo-importer' ); ?></h2>
                                    <div class="reset-info-sm">
                                        <?php esc_html_e('This will reset your databse and the process can\'t  be reversed','century-demo-importer');?>    
                                    </div>

                                </div>
                                <div class="cdi-confirm">
                                    <a href="javascript:void(0)" class="cdi-reset-confrm"><?php esc_html_e('Confirm','century-demo-importer'); ?></a>
                                    <a href="javascript:void(0)" class="cdi-reset-cancel"><?php esc_html_e('Cancel','century-demo-importer'); ?></a>
                                </div>
                            </div>

                            <div class="cdi-reset-progress" style="display: none;">
                                <div class="reset-info"><?php esc_html_e('Reset is in progress please wait...','century-demo-importer'); ?></div>
                                <div class="loader-icon"></div>
                            </div>

                        </div>

                    </div>

                <?php }
            }


            //database reset buttons
        public function cdi_demo_data_reset_html(){
            ?>
            <div class="cdi-reset-database-wrapper">
                <div class="inner-wrapp">
                    <a href="javascript:void(0)" class="button button-primary cdi-db-reset">
                        <?php esc_html_e('Reset Database','century-demo-importer'); ?>
                    </a>
                    <?php  esc_html_e( 'Reset Your Site ? This will reset your site to default again. ', 'century-demo-importer' ); ?>
                </div>
            </div>
            <?php
        }

            //reset database 
        function cdi_demo_data_reset() {

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            global $wpdb;
            $options = array(
                'offset' => 0,
                'orderby' => 'post_date',
                'order' => 'DESC',
                'post_type' => 'post',
                'post_status' => 'publish'
            );

            $statuses = array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'inherit', 'auto-draft', 'scheduled' );
            $types = array(
                'post',
                'page',
                'attachment',
                'nav_menu_item',
                'wpcf7_contact_form',
                'product',
                'portfolio',
                'testimonial',
                'team'
            );

                // delete posts
            foreach ( $types as $type ) {
                foreach ( $statuses as $status ) {
                    $options[ 'post_type' ] = $type;
                    $options[ 'post_status' ] = $status;

                    $posts = get_posts( $options );
                    $offset = 0;
                    while ( count( $posts ) > 0 ) {
                        if ( $offset == 10 ) {
                            break;
                        }
                        $offset++;
                        foreach ( $posts as $post ) {
                            wp_delete_post( $post->ID, true );
                        }
                        $posts = get_posts( $options );
                    }
                }
            }


                // Delete categories, tags, etc
            $taxonomies_array = array( 'category', 'post_tag', 'portfolio-category', 'testimonial-category', 'team-category', 'nav_menu', 'product_cat' );
            foreach ( $taxonomies_array as $tax ) {
                $cats = get_terms( $tax, array( 'hide_empty' => false, 'fields' => 'ids' ) );
                foreach ( $cats as $cat ) {
                    wp_delete_term( $cat, $tax );
                }
            }


                // Delete Slider Revolution Sliders
            if ( class_exists( 'RevSlider' ) ) {
                $sliderObj = new RevSlider();
                foreach ( $sliderObj->getArrSliders() as $slider ) {
                    $slider->initByID( $slider->getID() );
                    $slider->deleteSlider();
                }
            }

                // Delete Widgets
            global $wp_registered_widget_controls;

            $widget_controls = $wp_registered_widget_controls;

            $available_widgets = array();

            foreach ( $widget_controls as $widget ) {
                if ( !empty( $widget[ 'id_base' ] ) && !isset( $available_widgets[ $widget[ 'id_base' ] ] ) ) { // no dupes
                    $available_widgets[] = $widget[ 'id_base' ];
                }


            }

            update_option( 'sidebars_widgets', array( 'wp_inactive_widgets' => array() ) );
            foreach ( $available_widgets as $widget_data ) {
                update_option( 'widget_' . $widget_data, array() );
            }


            //Clear "uploads" folder
            $this->cdi_clear_uploads( $this->uploads_dir[ 'basedir' ] );

            // this is required to return a proper result
            die();
        }

        /**
         * Clear "uploads" folder
         * @param string $dir
         * @return bool
         */
        private function cdi_clear_uploads( $dir ) {
            $files = array_diff( scandir( $dir ), array( '.', '..' ) );
            foreach ( $files as $file ) {
                ( is_dir( "$dir/$file" ) ) ? $this->cdi_clear_uploads( "$dir/$file" ) : unlink( "$dir/$file" );
            }

            return ( $dir != $this->uploads_dir[ 'basedir' ] ) ? rmdir( $dir ) : true;
        }


            //compatible for OCDI 
        public function cdi_ocdi_import_files() {

            $demos = CDI_Demos::get_demos_data();
            if( empty($demos)){
                return;
            }

            $demos_data = array();
            foreach( $demos as $demo ){

                $screen         = isset( $demo['screen'] )            ? $demo['screen']           : '';
                $demo_name      = isset( $demo['demo_name'] )         ? $demo['demo_name']        : '';
                $preview_url    = isset( $demo['preview_url'] )       ? $demo['preview_url']      : '';
                $xml_file       = isset( $demo['xml_file'] )          ? $demo['xml_file']         : '';
                $theme_settings = isset( $demo['theme_settings'] )    ? $demo['theme_settings']   : '';
                $widgets_file   = isset( $demo['widgets_file'] )      ? $demo['widgets_file']     : '';
                $rev_slider     = isset( $demo['rev_slider'] )        ? $demo['rev_slider']       : '';
                $import_redux   = isset( $demo['import_redux'] )      ? $demo['import_redux']     : '';
                $redux_array    = '';
                if( $import_redux ){
                    $option_filepath    = isset( $import_redux['file_url'] ) ? $import_redux['file_url'] : '';
                    $option_name        = isset( $import_redux['option_name'] ) ? $import_redux['option_name'] : '';

                    $redux_array =  array(array(
                        'file_url'    => $option_filepath,
                        'option_name' => $option_name,
                    ));
                }

                $demos_data[] =
                array(
                    'import_file_name'           => $demo_name,
                    'import_file_url'            => $xml_file,
                    'import_widget_file_url'     => $widgets_file,
                    'import_customizer_file_url' => $theme_settings,
                    'import_redux'               => $redux_array,
                    'import_preview_image_url'   => $screen,
                    'preview_url'                => $preview_url,
                );


                if( $import_redux ){
                    $demos_data['import_redux']  = $redux_array;
                }



            }
            return $demos_data;

        }

        public function cdi_ocdi_after_import( $selected_import ) {

            $demos = CDI_Demos::get_demos_data();
            if( empty($demos)){
                return;
            }

            foreach( $demos as $demo ){
                $demo_name       = isset( $demo['demo_name'] )         ? $demo['demo_name']        : '';
                $menus           = isset( $demo['menus'] )              ? $demo['menus']             : '';
                $home_title      = isset( $demo['home_title'] )         ? $demo['home_title']        : '';

                if( $selected_import['import_file_name'] == $demo_name ){

                    foreach( $menus as $key => $menu ){
                        $main_menu = get_term_by( 'name', $menus, 'nav_menu' );

                        set_theme_mod( 'nav_menu_locations', array(
                            $key => $main_menu->term_id,
                        ));    
                    }

                    $front_page_id = get_page_by_title( $home_title );

                    update_option( 'show_on_front', 'page' );
                    update_option( 'page_on_front', $front_page_id->ID );

                }
            }


        }


        /**
        * Register menu
        *
        */
        public function cdi_register_menu() {
         $title = esc_html__('Install Demos','century-demo-importer');
         add_theme_page( $title, $title , 'edit_theme_options', 'demo-importer', array( $this, 'cdi_display_demos' ));
     }



 }

}

if ( !function_exists( 'century_demo_instance' ) ) {

    /**
     * Returns instanse of the plugin class.
     *
     * @since  1.0.0
     * @return object
     */
    function century_demo_instance() {
        return Century_Demo_Importer::get_instance();
    }

}

century_demo_instance();
