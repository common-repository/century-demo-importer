<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Start Class
if ( ! class_exists( 'CDI_Demos' ) ) {

	class CDI_Demos {

		/**
		 * Start things up
		 */
		public function __construct() {

			// Return if not in admin
			if ( ! is_admin() || is_customize_preview() ) {
				return;
			}

		

			// Disable WooCommerce Wizard
			add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );
			add_filter( 'woocommerce_show_admin_notice', '__return_false' );
			add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_false' );
	        

			// Start things
			add_action( 'admin_init', array( $this, 'init' ) );

			// Allows xml uploads
			add_filter( 'upload_mimes', array( $this, 'allow_xml_uploads' ) );

			// Demos popup
			add_action( 'admin_footer', array( $this, 'popup' ) );

		}

		/**
		 * Register the AJAX methods
		 *
		 * 
		 */
		public function init() {

			// Demos popup ajax
			add_action( 'wp_ajax_cdi_ajax_get_demo_data', array( $this, 'ajax_demo_data' ) );
			add_action( 'wp_ajax_cdi_ajax_required_plugins_activate', array( $this, 'ajax_required_plugins_activate' ) );


			// Get data to import
			add_action( 'wp_ajax_cdi_ajax_get_import_data', array( $this, 'ajax_get_import_data' ) );

			// Import XML file
			add_action( 'wp_ajax_cdi_ajax_import_xml', array( $this, 'ajax_import_xml' ) );

			// Import customizer settings
			add_action( 'wp_ajax_cdi_ajax_import_theme_settings', array( $this, 'ajax_import_theme_settings' ) );

			// Import widgets
			add_action( 'wp_ajax_cdi_ajax_import_widgets', array( $this, 'ajax_import_widgets' ) );

			add_action( 'wp_ajax_cdi_ajax_importSliderRev', array( $this, 'ajax_importSliderRev' ) );
			add_action( 'wp_ajax_cdi_ajax_importThemeOptions', array( $this, 'ajax_importThemeOptions' ) );
			
			add_action( 'wp_ajax_plugin_offline_installer', array( $this, 'plugin_offline_installer_callback' ) );

			// After import
			add_action( 'wp_ajax_cdi_after_import', array( $this, 'ajax_after_import' ) );


		}

		

		/**
		 * Allows xml uploads so we can import from server
		 *
		 * 
		 */
		public function allow_xml_uploads( $mimes ) {
			$mimes = array_merge( $mimes, array(
				'xml' 	=> 'application/xml'
			) );
			return $mimes;
		}



		

		/**
		 * Get demos data to add them in the Demo Import and Pro Demos plugins
		 *
		 * 
		 */
		public static function get_demos_data() {
			
			// git url for config.json
			$git_url  	= 'https://www.centurytheme.com/wordpress-themes/centu/demo-data/config.json';
			
			$git_url 	= apply_filters('cdi_git_config_location', $git_url );

			$data 		= CDI_Demos_Helpers::get_remote( $git_url );

			
			if ( is_wp_error( $data ) ) {
				return $data;
			}

			
		    $data = json_decode( $data,true );
		    if($data == ''){
		    	$data = array();
		    }

			

			// Return
			return apply_filters( 'cdi_demos_data', $data );

		}

		/**
		 * Get the category list of all categories used in the predefined demo imports array.
		 *
		 * 
		 */
		public static function get_demo_all_categories( $demo_imports ) {
			$categories = array();

			foreach ( $demo_imports as $item ) {
				if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
					foreach ( $item['categories'] as $category ) {
						$categories[ sanitize_key( $category ) ] = $category;
					}
				}
			}

			if ( empty( $categories ) ) {
				return false;
			}

			return $categories;
		}

		/**
		 * Return the concatenated string of demo import item categories.
		 * These should be separated by comma and sanitized properly.
		 *
		 * 
		 */
		public static function get_demo_item_categories( $item ) {
			$sanitized_categories = array();

			if ( isset( $item['categories'] ) ) {
				foreach ( $item['categories'] as $category ) {
					$sanitized_categories[] = sanitize_key( $category );
				}
			}

			if ( ! empty( $sanitized_categories ) ) {
				return implode( ',', $sanitized_categories );
			}

			return false;
		}

	    /**
	     * Demos popup
	     *
		 * 
	     */
	    public static function popup() {
	    	global $pagenow;
			?>
		        
		        <div id="cdi-demo-popup-wrap" class="cp-popup-main-wrapp">
					<div class="cdi-demo-popup-container">
						<div class="cdi-demo-popup-content-wrap">
							<div class="cdi-demo-popup-content-inner">
								<div id="cdi-demo-popup-content" class="popup-wrapp-outter"></div>
							</div>
						</div>
					</div>
					<div class="cdi-demo-popup-overlay"></div>
				</div>

	    	<?php
	    
	    }

		/**
		 * Demos popup ajax.
		 *
		 * 
		 */
		public static function ajax_demo_data() {

			if ( ! wp_verify_nonce( $_GET['demo_data_nonce'], 'get-demo-data' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Database reset url
			if ( is_plugin_active( 'wordpress-database-reset/wp-reset.php' ) ) {
				$plugin_link 	= admin_url( 'tools.php?page=database-reset' );
			} else {
				$plugin_link 	= admin_url( 'plugin-install.php?s=Wordpress+Database+Reset&tab=search' );
			}

			// Get all demos
			$demos = self::get_demos_data();

			// Get selected demo
			$demo = sanitize_text_field(wp_unslash($_GET['demo_name']));


			
			$xml_file 		= isset($demos[$demo]['xml_file']) 			? $demos[$demo]['xml_file'] 		: '';
			$theme_settings = isset($demos[$demo]['theme_settings']) 	? $demos[$demo]['theme_settings'] 	: '';
			$widgets_file 	= isset($demos[$demo]['widgets_file']) 		? $demos[$demo]['widgets_file'] 	: '';
			$rev_slider 	= isset($demos[$demo]['rev_slider']) 		? $demos[$demo]['rev_slider'] 		: '';
			$import_redux   = isset($demos[$demo]['import_redux'] )     ? $demos[$demo]['import_redux']     		: '';
			
			

			// Get required plugins
			$plugins 	= $demos[$demo][ 'required_plugins' ];
			$demo_name 	= isset($demos[$demo]['demo_name']) ? $demos[$demo]['demo_name'] : '';

			// Get free plugins
			$free = $plugins[ 'free' ];

			// Get premium plugins
			$premium = $plugins[ 'premium' ];

			 ?>

			<div id="cdi-demo-plugins" class="cp-active ad-popup-common">
				<div class="demo-title-wrapp">
					<h2 class="title"><?php echo  esc_html( $demo_name ); ?></h2>
					<a href="#" class="cdi-demo-popup-close"><span class="dashicons dashicons-no-alt"></span></a>
				</div>
				<div class="cdi-popup-text">

					<div class="cdi-required-plugins-wrap">
						<h3><?php esc_html_e( 'Required Plugins', 'century-demo-importer' ); ?></h3>
						<p><?php esc_html_e( 'The following plugins are required for the theme to look exactly like the demo, so please install and activate them. ','century-demo-importer' ); ?></p>
						<?php if( $premium || $free ){ ?>
						<div class="cdi-required-plugins oe-plugin-installer clearfix">
							<div class="msg-wrapp">
							<span class="pl-install-wraning"></span>
							</div>

							<?php
							self::required_plugins( $free, 'free' );
							self::required_plugins( $premium, 'premium' ); ?>
						</div>
					<?php }else{ ?>
						<h4><?php esc_html_e('You can proceed to next step, you don\'t have any plugins to install','century-demo-importer'); ?></h4>
					<?php } ?>
					</div>

				</div>

				<a class="cdi-button cdi-plugins-next" href="#">
					<?php esc_html_e( 'next step', 'century-demo-importer' ); ?>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>

			</div>
			<div class="cp-importer-form-wrapper cp-hidden ad-popup-common">
				<div class="demo-title-wrapp">
					<h2 class="title"><?php echo  esc_html( $demo_name ); ?></h2>
					<a href="#" class="cdi-demo-popup-close"><span class="dashicons dashicons-no-alt"></span></a>
				</div>
			<form method="post" id="cdi-demo-import-form">

				<input id="cdi_import_demo" type="hidden" name="cdi_import_demo" value="<?php echo esc_attr( $demo ); ?>" />

				<div class="cdi-demo-import-form-types">

					<div class="import-notice">
						<strong><i class="dashicons dashicons-info"></i><?php esc_html_e('Note:','century-demo-importer'); ?></strong>
						<p>
						<?php esc_html_e('If your website already has content or is already in use, we recommend you to backup your website content before attempting a full site import.','century-demo-importer'); ?>
						</p>
					</div>

					<h2 class="title"><?php esc_html_e( 'Select what you want to import:', 'century-demo-importer' ); ?></h2>
					
					<ul class="cdi-popup-text cp-importer-choices">
					
						<?php if( $xml_file ): ?>
						<li>
							<label for="cdi_import_xml">
								<input id="cdi_import_xml" type="checkbox" name="cdi_import_xml" checked="checked" />
								<strong><?php esc_html_e( 'Import XML Data', 'century-demo-importer' ); ?></strong> 
								<span class="text-sm">(<?php esc_html_e( 'pages, posts, images, menus, etc...', 'century-demo-importer' ); ?>)</span>
							</label>
						</li>
						<?php endif; ?>

						<?php if($theme_settings): ?>
						<li>
							<label for="cdi_theme_settings">
								<input id="cdi_theme_settings" type="checkbox" name="cdi_theme_settings" checked="checked" />
								<strong><?php esc_html_e( 'Import Customizer Settings', 'century-demo-importer' ); ?></strong>
							</label>
						</li>
						<?php endif; ?>
						
						<?php if($widgets_file): ?>
						<li>
							<label for="cdi_import_widgets">
								<input id="cdi_import_widgets" type="checkbox" name="cdi_import_widgets" checked="checked" />
								<strong><?php esc_html_e( 'Import Widgets', 'century-demo-importer' ); ?></strong>
							</label>
						</li>
						<?php endif; ?>
						
						<?php if($rev_slider): ?>
						<li>
							<label for="cdi_import_sliders">
								<input id="cdi_import_sliders" type="checkbox" name="cdi_import_sliders" checked="checked" />
								<strong><?php esc_html_e( 'Import Slider', 'century-demo-importer' ); ?></strong>
							</label>
						</li>
						<?php endif; ?>

						<?php if($import_redux): ?>
						<li>
							<label for="cdi_import_theme_options">
								<input id="cdi_import_theme_options" type="checkbox" name="cdi_import_theme_options" checked="checked" />
								<strong><?php esc_html_e( 'Import Theme Options', 'century-demo-importer' ); ?></strong>
							</label>
						</li>
						<?php endif; ?>

					</ul>

				</div>
				
				<?php wp_nonce_field( 'cdi_import_demo_data_nonce', 'cdi_import_demo_data_nonce' ); ?>
				<input type="submit" name="submit" class="cdi-button cdi-import" value="<?php esc_html_e( 'Start Importing', 'century-demo-importer' ); ?>" data-reset="false" />

			</form>
			</div>

			<div class="cdi-loader cp-hidden ad-popup-common">
				<h2 class="title"><?php esc_html_e( 'Demo installation will take some time, have patience.', 'century-demo-importer' ); ?></h2>
				<p class="policy-notice">
					<strong><?php esc_html_e('Note:','century-demo-importer'); ?> </strong>
					<?php esc_html_e('Some of the copy right images will be replaced by place holders.','century-demo-importer'); ?>
				</p>
				<div class="cdi-import-status cdi-popup-text"></div>
			</div>

			<div class="cdi-last cp-hidden">
				<a href="#" class="cdi-demo-popup-close"><span class="dashicons dashicons-no-alt"></span></a>
				<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
				  <circle class="path circle" fill="none" stroke="#73AF55" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
				  <polyline class="path check" fill="none" stroke="#73AF55" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				</svg>
				<h3><?php esc_html_e( 'Demo Imported!', 'century-demo-importer' ); ?></h3>
				<a href="<?php echo esc_url( get_home_url() ); ?>" target="_blank" class="btn-success-result"><?php esc_html_e( 'View Site', 'century-demo-importer' ); ?></a>
				
			</div>

			<?php
			die();
		}

		/**
		 * Required plugins.
		 *
		 * 
		 */
		public function required_plugins( $plugins, $return ) {

			foreach ( $plugins as $key => $plugin ) {

				$api = array(
					'slug' 		=> isset( $plugin['slug']  ) ? $plugin['slug']  : '',
					'init' 		=> isset( $plugin['init']  ) ? $plugin['init']  : '',
					'name' 		=> isset( $plugin['name']  ) ? $plugin['name']  : '',
					'class'		=> isset( $plugin['class'] ) ? $plugin['class'] : '',
				);

				

				if ( ! is_wp_error( $api ) ) { // confirm error free

					// Installed but Inactive.
					if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) && is_plugin_inactive( $plugin['init'] ) ) {

						$button_classes = 'button activate-now button-primary';
						$button_text 	= esc_html__( 'Activate', 'century-demo-importer' );

					// Not Installed.
					} elseif ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) ) {

						$button_classes = 'button install-now';
						$button_text 	= esc_html__( 'Install Now', 'century-demo-importer' );

					// Active.
					} else {
						$button_classes = 'button disabled';
						$button_text 	= esc_html__( 'Activated', 'century-demo-importer' );
					} ?>

					<div class="cdi-plugin cdi-clr cdi-plugin-<?php echo esc_attr($api['slug']); ?>" data-slug="<?php echo esc_attr($api['slug']); ?>" data-init="<?php echo esc_attr($api['init']); ?>">
						<div class="plugin-name"><?php echo esc_html($api['name']); ?></div>

						<?php
						// If premium plugins and not installed
						if ( 'premium' == $return
							&& ! file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) ) { 
							$plugin_zip 	= 'https://accesspressthemes.com/plugin-repo/'.$api['slug'].'/'.$api['slug'].'.zip';
							//$plugin_zip 	= 'https://centuryplugin.com/plugin-repo/'.$api['slug'].'/'.$api['slug'].'.zip';
							$get_pl_file 	= explode('/',$plugin['init']);
							$main_file 		= $get_pl_file[1];
							
							?>
							<button class="install-offline button" data-host-type="remote" data-file="<?php echo esc_attr($main_file); ?>" data-class="<?php echo esc_attr($api['class']); ?>" data-slug="<?php echo esc_attr($api['slug']); ?>" data-href="<?php echo esc_url($plugin_zip); ?>"><?php esc_html_e('Install Now','century-demo-importer');?></button>
						<?php
						} else { ?>
							<button class="<?php echo esc_attr($button_classes); ?>" data-init="<?php echo esc_attr($api['init']); ?>" data-slug="<?php echo esc_attr($api['slug']); ?>" data-name="<?php echo esc_attr($api['name']); ?>"><?php echo esc_html($button_text); ?></button>
						<?php
						} ?>
					</div>

				<?php
				}
			}

		}


		/**
		* Pro plugins install
		*
		*/
		public function plugin_offline_installer_callback() {
			$plugin = array();

			$file_location = $plugin['location'] = isset( $_POST['file_location'] ) ? sanitize_text_field( wp_unslash( $_POST['file_location'] ) ) : '';
			$file 			= isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
			$host_type 		= isset( $_POST['host_type'] ) ? sanitize_text_field( wp_unslash( $_POST['host_type'] ) ) : '';
			$plugin_class 	= $plugin['class'] = isset( $_POST['class_name'] ) ? sanitize_text_field( wp_unslash( $_POST['class_name'] ) ) : '';
			$plugin_slug 	= $plugin['slug'] = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
			$plugin_directory = WP_PLUGIN_DIR;

			$plugin_file = $plugin_slug . '/' . $file;

			if( $host_type == 'remote' ) {
				$file_location = $this->get_local_dir_path($plugin);
			}

			$zip = new ZipArchive();
			if ($zip->open($file_location) === TRUE) {
			    $zip->extractTo($plugin_directory);
			    $zip->close();

			    activate_plugin($plugin_file);

			    if( $host_type == 'remote' ) {
		    		unlink($file_location);
		    	}

			    echo 'success';

				die();
			} else {
			    echo 'failed';
			}

			die();
		}

		public function get_local_dir_path($plugin) {

	  		$upload_dir = wp_upload_dir();

	  		$file_location = $file_location = $upload_dir['path'] . '/' . $plugin['slug'].'.zip';

	  		if( file_exists( $file_location ) || class_exists( $plugin['class'] ) ) {
	  			return $file_location;
	  		}

      		$url = wp_nonce_url(admin_url('themes.php?page=demo-importer'),'remote-file-installation');
			if (false === ($creds = request_filesystem_credentials($url, '', false, false, null) ) ) {
				return; // stop processing here
			}

      		if ( ! WP_Filesystem($creds) ) {
				request_filesystem_credentials($url, '', true, false, null);
				return;
			}

			global $wp_filesystem;
			$file = $wp_filesystem->get_contents( $plugin['location'] );

			$wp_filesystem->put_contents( $file_location, $file, FS_CHMOD_FILE );

			return $file_location;
      	}

		/**
		 * Required plugins activate
		 *
		 * 
		 */
		public function ajax_required_plugins_activate() {

			if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['init'] ) || ! $_POST['init'] ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => __( 'No plugin specified', 'century-demo-importer' ),
					)
				);
			}

			$plugin_init = ( isset( $_POST['init'] ) ) ? esc_attr( $_POST['init'] ) : '';
			$activate 	 = activate_plugin( $plugin_init, '', false, true );

			if ( is_wp_error( $activate ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => $activate->get_error_message(),
					)
				);
			}

			wp_send_json_success(
				array(
					'success' => true,
					'message' => __( 'Plugin Successfully Activated', 'century-demo-importer' ),
				)
			);

		}

		/**
		 * Returns an array containing all the importable content
		 *
		 * 
		 */
		public function ajax_get_import_data() {
			check_ajax_referer( 'cdi_import_data_nonce', 'security' );

			echo json_encode( 
				array(
					array(
						'input_name' 	=> 'cdi_import_xml',
						'action' 		=> 'cdi_ajax_import_xml',
						'method' 		=> 'ajax_import_xml',
						'loader' 		=> esc_html__( 'Importing XML Data', 'century-demo-importer' )
					),

					array(
						'input_name' 	=> 'cdi_theme_settings',
						'action' 		=> 'cdi_ajax_import_theme_settings',
						'method' 		=> 'ajax_import_theme_settings',
						'loader' 		=> esc_html__( 'Importing Customizer Settings', 'century-demo-importer' )
					),

					array(
						'input_name' 	=> 'cdi_import_widgets',
						'action' 		=> 'cdi_ajax_import_widgets',
						'method' 		=> 'ajax_import_widgets',
						'loader' 		=> esc_html__( 'Importing Widgets', 'century-demo-importer' )
					),

					array(
						'input_name' 	=> 'cdi_import_sliders',
						'action' 		=> 'cdi_ajax_importSliderRev',
						'method' 		=> 'ajax_importSliderRev',
						'loader' 		=> esc_html__( 'Importing Slider', 'century-demo-importer' )
					),

					array(
						'input_name' 	=> 'cdi_import_theme_options',
						'action' 		=> 'cdi_ajax_importThemeOptions',
						'method' 		=> 'ajax_importThemeOptions',
						'loader' 		=> esc_html__( 'Importing Theme Settings', 'century-demo-importer' )
					),

				)
			);

			die();
		}


		

		/**
		 * Import XML file
		 *
		 * 
		 */
		public function ajax_import_xml() {
			if ( ! wp_verify_nonce( $_POST['cdi_import_demo_data_nonce'], 'cdi_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Get the selected demo
			$demo_type 			= sanitize_text_field(wp_unslash($_POST['cdi_import_demo']));

			// Get demos data
			$demo 				= CDI_Demos::get_demos_data()[ $demo_type ];

			// Content file
			$xml_file 			= isset( $demo['xml_file'] ) ? $demo['xml_file'] : '';

			// Delete the default post and page
			$sample_page 		= get_page_by_path( 'sample-page', OBJECT, 'page' );
			$hello_world_post 	= get_page_by_path( 'hello-world', OBJECT, 'post' );

			if ( ! is_null( $sample_page ) ) {
				wp_delete_post( $sample_page->ID, true );
			}

			if ( ! is_null( $hello_world_post ) ) {
				wp_delete_post( $hello_world_post->ID, true );
			}

			// Import Posts, Pages, Images, Menus.
			$result = $this->process_xml( $xml_file );

			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}

			die();
		}

		/**
		 * Import customizer settings
		 *
		 * 
		 */
		public function ajax_import_theme_settings() {
			if ( ! wp_verify_nonce( $_POST['cdi_import_demo_data_nonce'], 'cdi_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Include settings importer
			include CDI_PATH . 'inc/importers/class-settings-importer.php';

			// Get the selected demo
			$demo_type 			= sanitize_text_field(wp_unslash($_POST['cdi_import_demo']));

			// Get demos data
			$demo 				= CDI_Demos::get_demos_data()[ $demo_type ];

			// Settings file
			$theme_settings 	= isset( $demo['theme_settings'] ) ? $demo['theme_settings'] : '';

			// Import settings.
			$settings_importer = new CDI_Settings_Importer();
			$result = $settings_importer->process_import_file( $theme_settings );
			
			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}

			die();
		}



		/**
		 * Import widgets
		 *
		 * 
		 */
		public function ajax_import_widgets() {
			if ( ! wp_verify_nonce( $_POST['cdi_import_demo_data_nonce'], 'cdi_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Include widget importer
			include CDI_PATH . 'inc/importers/class-widget-importer.php';

			// Get the selected demo
			$demo_type 			= sanitize_text_field(wp_unslash($_POST['cdi_import_demo']));

			// Get demos data
			$demo 				= CDI_Demos::get_demos_data()[ $demo_type ];

			// Widgets file
			$widgets_file 		= isset( $demo['widgets_file'] ) ? $demo['widgets_file'] : '';

			// Import settings.
			$widgets_importer = new CDI_Widget_Importer();
			$result = $widgets_importer->process_import_file( $widgets_file );
			
			if ( is_wp_error( $result ) ) {
				echo json_encode( $result->errors );
			} else {
				echo 'successful import';
			}

			die();
		}

		

		 /*
         * Import Slider Revolution
         */
        public function ajax_importSliderRev() {
        	if ( ! wp_verify_nonce( $_POST['cdi_import_demo_data_nonce'], 'cdi_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// Get the selected demo
			$demo_type 			= sanitize_text_field(wp_unslash($_POST['cdi_import_demo']));
			$demo 				= CDI_Demos::get_demos_data()[ $demo_type ];
			$slider_file 		= isset( $demo['rev_slider'] ) ? $demo['rev_slider'] : '';
			$response 			= CDI_Demos_Helpers::get_remote( $slider_file );

			// No sample data found
			if ( $response === false ) {
				return new WP_Error( 'xml_import_error', __( 'Can not retrieve slider data. The server may be down at the moment please try again later. If you still have issues contact the theme developer for assistance.', 'century-demo-importer' ) );
			}

			
			$slider_link_temp 	= CDI_PATH.'/temp-data/furniture_temp.zip';
			file_put_contents( $slider_link_temp, $response );
			
			
            if ( class_exists( 'RevSlider' ) ) {
                
                $slider = new RevSlider();
                $slider->importSliderFromPost( true, true, $slider_link_temp );

                echo 'successful import';
                file_put_contents( $slider_link_temp, '' );
          
            } else {
                echo 'It looks like you don\'t have Slider Revolution installed and activated. Sliders were not imported!<br>';
            }

            die();
        }


        /**
	    * Import Theme Options
	    */
        public function ajax_importThemeOptions() {
        	if ( ! wp_verify_nonce( $_POST['cdi_import_demo_data_nonce'], 'cdi_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			$demo_type 			= sanitize_text_field(wp_unslash($_POST['cdi_import_demo']));
			$demo 				= CDI_Demos::get_demos_data()[ $demo_type ];
			$import_redux 		= isset( $demo['import_redux'] ) ? $demo['import_redux'] : '';
			$option_filepath 	= '';
			if($import_redux){
				$option_filepath 	= isset( $import_redux['file_url'] ) ? $import_redux['file_url'] : '';
				$option_name 		= isset( $import_redux['option_name'] ) ? $import_redux['option_name'] : '';
			}
			
            $json_file 			= wp_remote_get( $option_filepath );

            if ( $json_file[ 'response' ][ 'code' ] == '200' && !empty( $json_file[ 'body' ] ) ) {
                if ( update_option( $option_name, json_decode( $json_file[ 'body' ], true ), '', 'yes' ) ) {
                    echo 'successful import';
                }
            } else {
               return esc_html__( 'Theme Options could not be imported', 'century-demo-importer' );
            }

            die();
        }

		/**
		 * After import
		 *
		 * 
		 */
		public function ajax_after_import() {
			if ( ! wp_verify_nonce( $_POST['cdi_import_demo_data_nonce'], 'cdi_import_demo_data_nonce' ) ) {
				die( 'This action was stopped for security purposes.' );
			}

			// If XML file is imported
			if ( $_POST['cdi_import_is_xml'] === 'true' ) {

				// Get the selected demo
				$demo_type 			= sanitize_text_field(wp_unslash($_POST['cdi_import_demo']));

				// Get demos data
				$demo 				= CDI_Demos::get_demos_data()[ $demo_type ];

				// Elementor width setting
				$elementor_width 	= isset( $demo['elementor_width'] ) ? $demo['elementor_width'] : '';

				// Reading settings
				$homepage_title 	= isset( $demo['home_title'] ) ? $demo['home_title'] : '';

				$blog_title 		= isset( $demo['blog_title'] ) ? $demo['blog_title'] : '';

				// Posts to show on the blog page
				$posts_to_show 		= isset( $demo['posts_to_show'] ) ? $demo['posts_to_show'] : '';


				// If shop demo
				$shop_demo 			= isset( $demo['is_shop'] ) ? $demo['is_shop'] : false;

				// Product image size
				$image_size 		= isset( $demo['woo_image_size'] ) ? $demo['woo_image_size'] : '';
				$thumbnail_size 	= isset( $demo['woo_thumb_size'] ) ? $demo['woo_thumb_size'] : '';
				$crop_width 		= isset( $demo['woo_crop_width'] ) ? $demo['woo_crop_width'] : '';
				$crop_height 		= isset( $demo['woo_crop_height'] ) ? $demo['woo_crop_height'] : '';

				// Assign WooCommerce pages if WooCommerce Exists
				if ( class_exists( 'WooCommerce' ) && true == $shop_demo ) {

					$woopages = array(
						'woocommerce_shop_page_id' 				=> 'Shop',
						'woocommerce_cart_page_id' 				=> 'Cart',
						'woocommerce_checkout_page_id' 			=> 'Checkout',
						'woocommerce_pay_page_id' 				=> 'Checkout &#8594; Pay',
						'woocommerce_thanks_page_id' 			=> 'Order Received',
						'woocommerce_myaccount_page_id' 		=> 'My Account',
						'woocommerce_edit_address_page_id' 		=> 'Edit My Address',
						'woocommerce_view_order_page_id' 		=> 'View Order',
						'woocommerce_change_password_page_id' 	=> 'Change Password',
						'woocommerce_logout_page_id' 			=> 'Logout',
						'woocommerce_lost_password_page_id' 	=> 'Lost Password'
					);

					foreach ( $woopages as $woo_page_name => $woo_page_title ) {

						$woopage = get_page_by_title( $woo_page_title );
						if ( isset( $woopage ) && $woopage->ID ) {
							update_option( $woo_page_name, $woopage->ID );
						}

					}

					// We no longer need to install pages
					delete_option( '_wc_needs_pages' );
					delete_transient( '_wc_activation_redirect' );

					// Get products image size
					update_option( 'woocommerce_single_image_width', $image_size );
					update_option( 'woocommerce_thumbnail_image_width', $thumbnail_size );
					update_option( 'woocommerce_thumbnail_cropping', 'custom' );
					update_option( 'woocommerce_thumbnail_cropping_custom_width', $crop_width );
					update_option( 'woocommerce_thumbnail_cropping_custom_height', $crop_height );

				}

				// Set imported menus to registered theme locations
				$locations 			= get_theme_mod( 'nav_menu_locations' );
				$menus 				= wp_get_nav_menus();
				$config_menus 		= isset( $demo['menus'] ) ? $demo['menus'] : '';

				if( $config_menus ){
					foreach( $config_menus as $menu_id => $config_menu ){
						$locations[$menu_id] = $config_menu;
					}
				}

				if ( $menus ) {
					
					foreach ( $menus as $menu ) {
							
						if ( $menu->name == 'Top Menu' ) {
							$locations['topbar_menu'] = $menu->term_id;
						} else if ( $menu->name == 'Footer Menu' ) {
							$locations['footer_menu'] = $menu->term_id;
						} else if ( $menu->name == 'Sticky Footer' ) {
							$locations['sticky_footer_menu'] = $menu->term_id;
						}

					}

				}

				// Set menus to locations
				set_theme_mod( 'nav_menu_locations', $locations );

				// Disable Elementor default settings
				update_option( 'elementor_disable_color_schemes', 'yes' );
				update_option( 'elementor_disable_typography_schemes', 'yes' );
			    if ( ! empty( $elementor_width ) ) {
					update_option( 'elementor_container_width', $elementor_width );
				}

				// Assign front page and posts page (blog page).
			    $home_page = get_page_by_title( $homepage_title );
			    $blog_page = get_page_by_title( $blog_title );

			    if( empty($homepage_title) ){
					update_option( 'show_on_front', 'posts' );
				}else{
			   	 	update_option( 'show_on_front', 'page' );
				}

			    if ( is_object( $home_page ) ) {
					update_option( 'page_on_front', $home_page->ID );
				}

				if ( is_object( $blog_page ) ) {
					update_option( 'page_for_posts', $blog_page->ID );
				}

				// Posts to show on the blog page
			    if ( ! empty( $posts_to_show ) ) {
					update_option( 'posts_per_page', $posts_to_show );
				}
				
			}

			wp_redirect(admin_url('themes.php?page=demo-importer')); 

			die();
		}

		/**
		 * Import XML data
		 *
		 * 
		 */
		public function process_xml( $file ) {
			
			$response = CDI_Demos_Helpers::get_remote( $file );

			// No sample data found
			if ( $response === false ) {
				return new WP_Error( 'xml_import_error', __( 'Can not retrieve sample data xml file. The server may be down at the moment please try again later. If you still have issues contact the theme developer for assistance.', 'century-demo-importer' ) );
			}

			// Write sample data content to temp xml file
			$temp_xml = CDI_PATH .'temp-data/temp.xml';
			file_put_contents( $temp_xml, $response );

			// Set temp xml to attachment url for use
			$attachment_url = $temp_xml;

			// If file exists lets import it
			if ( file_exists( $attachment_url ) ) {
				$this->import_xml( $attachment_url );
			} else {
				// Import file can't be imported - we should die here since this is core for most people.
				return new WP_Error( 'xml_import_error', __( 'The xml import file could not be accessed. Please try again or contact the theme developer.', 'century-demo-importer' ) );
			}

		}
		
		/**
		 * Import XML file
		 *
		 * 
		 */
		private function import_xml( $file ) {

			// Make sure importers constant is defined
			if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
				define( 'WP_LOAD_IMPORTERS', true );
			}

			// Import file location
			$import_file = ABSPATH . 'wp-admin/includes/import.php';

			// Include import file
			if ( ! file_exists( $import_file ) ) {
				return;
			}

			// Include import file
			require_once( $import_file );

			// Define error var
			$importer_error = false;

			if ( ! class_exists( 'WP_Importer' ) ) {
				$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

				if ( file_exists( $class_wp_importer ) ) {
					require_once $class_wp_importer;
				} else {
					$importer_error = __( 'Can not retrieve class-wp-importer.php', 'century-demo-importer' );
				}
			}

			if ( ! class_exists( 'WP_Import' ) ) {
				$class_wp_import = CDI_PATH . 'inc/importers/class-wordpress-importer.php';

				if ( file_exists( $class_wp_import ) ) {
					require_once $class_wp_import;
				} else {
					$importer_error = __( 'Can not retrieve wordpress-importer.php', 'century-demo-importer' );
				}
			}

			// Display error
			if ( $importer_error ) {
				return new WP_Error( 'xml_import_error', $importer_error );
			} else {

				// No error, lets import things...
				if ( ! is_file( $file ) ) {
					$importer_error = __( 'Sample data file appears corrupt or can not be accessed.', 'century-demo-importer' );
					return new WP_Error( 'xml_import_error', $importer_error );
				} else {
					$importer = new WP_Import();
					$importer->fetch_attachments = true;
					$importer->import( $file );

					// Clear sample data content from temp xml file
					$temp_xml = CDI_PATH .'temp-data/temp.xml';
					file_put_contents( $temp_xml, '' );
				}
			}
		}



	}

}
new CDI_Demos();