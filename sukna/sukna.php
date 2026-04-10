<?php
/**
 * Plugin Name: Sukna
 * Description: Dedicated system for managing residential apartments, hotel apartments and hospitality units.
 * Version: 1.0.0
 * Author: Sukna Team
 * Text Domain: sukna
 * Domain Path: /languages
 * Official Email: info@sukna.online
 * Official Website: https://sukna.online
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'SUKNA_VERSION', '1.0.0' );
define( 'SUKNA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUKNA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Class
 */
class Sukna_System {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	private function define_constants() {
		// Already defined above for now, but can move more here if needed.
	}

	private function includes() {
		// Module classes
		require_once SUKNA_PATH . 'includes/class-database.php';
		require_once SUKNA_PATH . 'includes/class-auth.php';
		require_once SUKNA_PATH . 'includes/class-customers.php';
		require_once SUKNA_PATH . 'includes/class-audit.php';
		require_once SUKNA_PATH . 'includes/class-pwa.php';

		// Infrastructure
		require_once SUKNA_PATH . 'includes/class-shortcode.php';
		require_once SUKNA_PATH . 'includes/class-ajax.php';
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( 'Sukna_Database', 'create_tables' ) );
		add_action( 'init', array( 'Sukna_Auth', 'init' ) );
		add_action( 'init', array( 'Sukna_PWA', 'init' ) );
		add_action( 'init', array( $this, 'send_nocache_headers' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function send_nocache_headers() {
		if ( (isset( $_GET['page'] ) && strpos( $_GET['page'], 'sukna' ) !== false) || isset( $_GET['ac_view'] ) ) {
			nocache_headers();
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && strpos( $_REQUEST['action'], 'sukna_' ) === 0 ) {
			nocache_headers();
		}
	}

	public function enqueue_assets() {
		wp_enqueue_media();

		// Enqueue Cairo font as fallback
		wp_enqueue_style( 'sukna-font-cairo', 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap', array(), SUKNA_VERSION );

		wp_enqueue_style( 'sukna-rtl-style', SUKNA_URL . 'assets/css/style-rtl.css', array( 'sukna-font-cairo' ), SUKNA_VERSION );
		wp_enqueue_style( 'sukna-print-style', SUKNA_URL . 'assets/css/print.css', array(), SUKNA_VERSION, 'print' );

		// Enqueue JsBarcode from CDN
		wp_enqueue_script( 'jsbarcode', 'https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js', array(), '3.11.5', true );

		// Enqueue Html5Qrcode for camera scanning
		wp_enqueue_script( 'html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', array(), '2.3.8', true );

		// Enqueue Chart.js for dashboard metrics
		wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true );

		// Enqueue html2pdf for bulk export
		wp_enqueue_script( 'html2pdf', 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js', array(), '0.10.1', true );

		wp_enqueue_script( 'sukna-scripts', SUKNA_URL . 'assets/js/scripts.js', array( 'jquery', 'jsbarcode', 'html5-qrcode', 'chartjs' ), SUKNA_VERSION, true );

		global $wpdb;
		$fullscreen_pass = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'fullscreen_password'" ) ?: '123456789';

		wp_localize_script( 'sukna-scripts', 'sukna_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'sukna_nonce' ),
		) );
	}
}

function Sukna() {
	return Sukna_System::get_instance();
}

// Kick off the plugin
Sukna();
