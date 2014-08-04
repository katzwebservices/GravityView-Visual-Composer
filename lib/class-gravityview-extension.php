<?php
/**
 * @version 1.0
 */
abstract class GravityView_Extension {

	protected $_title = NULL;

	protected $_version = NULL;

	protected $_text_domain = 'gravity-view';

	protected $_min_gravityview_version = '1.1.2';

	protected $_remote_update_url = 'https://gravityview.co';

	protected $_author = 'Katz Web Services, Inc.';

	static private $admin_notices = array();

	static $is_compatible = true;

	function __construct() {

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'admin_init', array( $this, 'settings') );

		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		if( false === $this->is_extension_supported() ) {
			return;
		}

		add_filter( 'gravityview_tooltips', array( $this, 'tooltips' ) );

		// Save the form configuration. Run at 20 so that View metadata is already saved (at 10)
		add_action( 'save_post', array( $this, 'save_post' ), 20 );

		$this->add_hooks();

	}

	/**
	 * Load translations for the extension
	 * @return void
	 */
	function load_plugin_textdomain() {

		if( empty( $this->_text_domain ) ) { return; }

		load_plugin_textdomain( $this->_text_domain , false, plugin_dir_path( __FILE__ ). 'languages/' );
	}

	function settings( $settings ) {

		if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			include_once plugin_dir_path( __FILE__ ) . 'EDD_SL_Plugin_Updater.php';
		}

		$license = GravityView_Settings::getSetting('license');

		// Don't update if invalid license.
		if( empty( $license['status'] ) || strtolower( $license['status'] ) !== 'valid' ) {
			do_action('gravityview_log_debug', 'License is not valid; not checking for updates.', $license );
			return;
		}

		$updater = new EDD_SL_Plugin_Updater(
			$this->_remote_update_url,
			$this->_path,
			array(
            	'version'	=> $this->_version, // current version number
            	'license'	=> $license['license'],
            	'item_name' => $this->_title,  // name of this plugin
            	'author' 	=> strip_tags( $this->_author )  // author of this plugin
          	)
        );
	}

	/**
	 * Outputs the admin notices generated by the plugin
	 *
	 * @return void
	 */
	function admin_notice() {

		if( empty( self::$admin_notices ) ) {
			return;
		}

		foreach( self::$admin_notices as $notice ) {

			echo '<div id="message" class="'. esc_attr( $notice['class'] ).'">';

			if( !self::$is_compatible ) {
				echo '<h3>'.sprintf( esc_attr__('%s could not be activated.', 'gravity-view'), $this->_title ).'</h3>';
			}
			echo wpautop( $notice['message'] );
			echo '<div class="clear"></div>';
			echo '</div>';

		}

		//reset the notices handler
		self::$admin_notices = array();
	}

	/**
	 * Add a notice to be displayed in the admin.
	 * @param array $notice Array with `class` and `message` keys. The message is not escaped.
	 */
	public static function add_notice( $notice = array() ) {

		if( is_array( $notice ) && !isset( $notice['message'] ) ) {
			do_action( 'gravityview_log_error', __CLASS__.'[add_notice] Notice not set', $notice );
			return;
		} else if( is_string( $notice ) ) {
			$notice = array( 'message' => $notice );
		}

		$notice['class'] = empty( $notice['class'] ) ? 'error' : $notice['class'];

		self::$admin_notices[] = $notice;
	}

	function add_hooks() { }

	/**
	 * Store the filter settings in the `_gravityview_filters` post meta
	 * @param  int $post_id Post ID
	 * @return void
	 */
	function save_post( $post_id ) {}

	function tooltips( $tooltips = array() ) { return $tooltips; }

	private function is_extension_supported() {

		self::$is_compatible = true;

		if( !class_exists( 'GravityView_Plugin' ) ) {

			$message = __('GravityView is not active.', 'gravity-view');

			self::add_notice( $message );

			do_action( 'gravityview_log_error', __CLASS__.'[is_compatible] ' . $message );

			self::$is_compatible = false;

		} else if( false === version_compare(GravityView_Plugin::version, $this->_min_gravityview_version , ">=") ) {

			$message = sprintf( __('The extension requires GravityView Version %s or newer.', 'gravity-view' ), '<tt>'.$this->_min_gravityview_version.'</tt>');

			self::add_notice( $message );

			do_action( 'gravityview_log_error', __CLASS__.'[is_compatible] ' . $message );

			self::$is_compatible = false;

		} else if( !GravityView_Admin::check_gravityforms() ) {
			self::$is_compatible = false;
		}

		return self::$is_compatible;
	}

}