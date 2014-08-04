<?php
/*
Plugin Name: GravityView - Visual Composer Extension
Plugin URI: https://gravityview.co/extensions/visual-composer/
Description: Enable enhanced GravityView integration with the <a href="http://codecanyon.net/item/visual-composer-page-builder-for-wordpress/242431?ref=katzwebservices">Visual Composer</a> plugin
Version: 1.0
Text Domain:       	gravityview-visual-composer
License:           	GPLv2 or later
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
Domain Path:			/languages
Author: Katz Web Services, Inc.
Author URI: https://gravityview.co
*/

add_action( 'plugins_loaded', 'gv_extension_visual_composer_load', 20 );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 * @return void
 */
function gv_extension_visual_composer_load() {

	// We prefer to use the one bundled with GravityView, but if it doesn't exist, go here.
	if( !class_exists( 'GravityView_Extension' ) ) {
		include_once plugin_dir_path( __FILE__ ) . 'lib/class-gravityview-extension.php';
	}


	class GravityView_Visual_Composer extends GravityView_Extension {

		protected $_title = 'Visual Composer';

		protected $_version = '1.0';

		protected $_min_gravityview_version = '1.0';

		protected $_text_domain = 'gravityview-visual-composer';

		protected $_path = __FILE__;

		function add_hooks() {

			if( !function_exists( 'vc_map') ) {
				do_action( 'gravityview_log_debug', 'GravityView_Visual_Composer[add_hooks] Not loading: Visual Composer isnt active.');
				return;
			}

			add_action( 'admin_head', array( $this, 'add_icon' ) );

			add_action( 'admin_init', array( $this, 'vc_map' ) );
		}

		/**
		 * Add a custom icon
		 */
		function add_icon() {

			echo '<style>.vc_shortcode-link .icon-wpb-vc_gravityview, .wpb_gravityview .icon-wpb-vc_gravityview { background-image: url('.plugins_url('assets/img/icon.png', __FILE__ ).'); }</style>';
		}

		/**
		 * Add the GravityView menu to the Visual Composer menu
		 * @uses vc_map
		 * @return void
		 */
		function vc_map() {
			global $vc_manager;

			if ( !class_exists( 'GravityView_Plugin' ) ) {
				do_action( 'gravityview_log_debug', 'GravityView_Visual_Composer[vc_map] Not loading: GravityView isnt active.');
				return;
			}

			$views = get_posts( array(
				'post_type' => 'gravityview',
				'numberposts' => -1,
				'status' => 'publish'
			));

			if ( empty($views) || is_wp_error( $views ) ) {

				// By default, there are no Views found.
				$gravityview_array[__( 'No GravityView Views found.', 'gravityview-visual-composer' )] = '';

				$params = array(
					array(
						'type' => 'dropdown',
						'heading' => __( 'View', 'gravityview-visual-composer' ),
						'param_name' => 'id',
						'value' => $gravityview_array,
						'description' => GravityView_Post_Types::no_views_text(),
						'admin_label' => true
					)
				);

			} else {

				// Overwrite the default
				$gravityview_array = array(
					__( 'Select a View to display', 'gravityview-visual-composer' ) => ''
				);

				// Map the title of the view to the ID
				foreach ( $views as $view ) {
					$gravityview_array[ esc_attr( $view->post_title ) ] = $view->ID;
				}

				$params = $this->get_params( $gravityview_array );

			}

			vc_map( array(
				'name' => __( 'GravityView', 'gravityview-visual-composer' ),
				'base' => 'gravityview',
				'icon' => 'icon-wpb-vc_gravityview',
				'category' => __( 'Content', 'gravityview-visual-composer' ),
				'description' => __( 'Embed a GravityView View', 'gravityview-visual-composer' ),
				'params' => $params
			) );

		} // if gravityview active

		/**
		 * Map GravityView
		 * @see GravityView_View_Data::get_default_args()
		 * @param  array $gravityview_array Array of posts
		 * @return array                    Array of parameters
		 */
		function get_params( $gravityview_array ) {

			$default_params = GravityView_View_Data::get_default_args( true );

			$params = array();
			foreach ( $default_params as $key => $param ) {

				// Only show the ones we want.
				if( empty( $param['show_in_shortcode'] ) ) { continue; }

				$type = $param['type'];
				$heading = $param['name'];
				$value = $param['value'];

				// Different name for dropdown
				switch ( $param['type'] ) {
					case 'select':
						$type = 'dropdown';
						$value = isset( $param['options'] ) ? $param['options'] : array();
						break;
					case 'checkbox':
						$heading = '';
						$value = array( $param['name'] => $param['value'] );
						break;
					case 'number':
					case 'text':
						$type = 'textfield';
						break;
				}

				if( $key === 'id' ) {
					$value = $gravityview_array;
					$type = 'dropdown';
				}

				$params[] = array(
					'type' => $type,
					'heading' => $heading,
					'class'	=> !empty( $param['class'] ) ? $param['class'] : NULL,
					'param_name' => $key,
					'description' => (empty($param['desc']) ? NULL : $param['desc']),
					'value' => $value,
					'admin_label' => true,
				);
			}

			return $params;

		}
	}

	new GravityView_Visual_Composer;
}