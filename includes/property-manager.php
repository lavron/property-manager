<?php

defined( 'ABSPATH' ) || exit;


final class PropertyManager {

	/**
	 * PropertyManager version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * PropertyManager Schema version.
	 *
	 * @since 4.3 started with version string 430.
	 *
	 * @var string
	 */
	public $db_version = '430';

	/**
	 * The single instance of the class.
	 *
	 * @var PropertyManager
	 * @since 2.1
	 */
	protected static $_instance = null;



	/**
	 * Main PropertyManager Instance.
	 *
	 * Ensures only one instance of PropertyManager is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see PM()
	 * @return PropertyManager - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * PropertyManager Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	private function init_hooks() {

		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'init', array( $this, 'init' ), 0 );
		// add_action( 'init', array( 'PM_Shortcodes', 'init' ) );
		add_action( 'init', array( $this, 'add_image_sizes' ) );
	}


	private function define_constants() {
		define( 'PM_ABSPATH', dirname( PM_PLUGIN_FILE ) . '/' );
		define( 'PM_PLUGIN_BASENAME', plugin_basename( PM_PLUGIN_FILE ) );
		define( 'PM_VERSION', $this->version );
		define( 'PM_TEMPLATE_DEBUG_MODE', false );
	}


	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Abstract classes.
		 */
		include_once PM_ABSPATH . 'includes/abstracts/data.php';
		include_once PM_ABSPATH . 'includes/abstracts/property.php';

		/**
		 * Core classes.
		 */
        include_once PM_ABSPATH . 'includes/functions.php';
		include_once PM_ABSPATH . 'includes/post-types.php';
		include_once PM_ABSPATH . 'includes/install.php';
		include_once PM_ABSPATH . 'includes/structured-data.php';
		include_once PM_ABSPATH . 'includes/shortcodes.php';
		
	
		// if ( $this->is_request( 'admin' ) ) {
		// 	include_once PM_ABSPATH . 'includes/admin/admin.php';
		// }

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}

		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'pm_allow_tracking', 'no' ) ) {
			include_once PM_ABSPATH . 'includes/tracker.php';
		}

    }
    
    private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	public function frontend_includes() {
		include_once PM_ABSPATH . 'includes/template-loader.php';
		// include_once PM_ABSPATH . 'includes/frontend-scripts.php';
	}


	/**
	 * Init PropertyManager when WordPress Initialises.
	 */
	public function init() {


		$this->structured_data  = new PM_Structured_Data();
		
		// Init action.
		do_action( 'pm_init' );
	}


	public function add_image_sizes() {
		
		add_image_size( 'pm_thumbnail', '450', '450', true );
		add_image_size( 'pm_single', '450', '450', true );
		add_image_size( 'pm_gallery_thumbnail', '120', '120', true );
	}

	
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', PM_PLUGIN_FILE ) );
	}


	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( PM_PLUGIN_FILE ) );
	}


	public function template_path() {
		return '/property-manager/';
	}


}
