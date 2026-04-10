<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Shortcode {

	public function __construct() {
		add_shortcode( 'sukna_system', array( $this, 'render_dashboard' ) );
	}

	public function render_dashboard() {
		ob_start();

		if ( ! Sukna_Auth::is_logged_in() ) {
			include SUKNA_PATH . 'templates/login.php';
			return ob_get_clean();
		}

		$view = isset( $_GET['sukna_view'] ) ? sanitize_text_field( $_GET['sukna_view'] ) : 'dashboard';
		$is_admin = Sukna_Auth::is_admin();

		include SUKNA_PATH . 'templates/header.php';

		switch ( $view ) {
			case 'properties':
				if ( ! Sukna_Auth::is_admin() && ! Sukna_Auth::is_owner() ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'sukna' ) . '</p>';
				} else {
					include SUKNA_PATH . 'templates/properties.php';
				}
				break;
			case 'users':
				if ( ! $is_admin ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'sukna' ) . '</p>';
				} else {
					include SUKNA_PATH . 'templates/users.php';
				}
				break;
			case 'settings':
				if ( ! $is_admin ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'sukna' ) . '</p>';
				} else {
					include SUKNA_PATH . 'templates/settings.php';
				}
				break;
			default:
				include SUKNA_PATH . 'templates/dashboard-home.php';
				break;
		}

		include SUKNA_PATH . 'templates/footer.php';
		return ob_get_clean();
	}
}

new Sukna_Shortcode();
