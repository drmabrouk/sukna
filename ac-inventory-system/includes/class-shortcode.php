<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Shortcode {

	public function __construct() {
		add_shortcode( 'ac_inventory_system', array( $this, 'render_dashboard' ) );
	}

	public function render_dashboard() {
		ob_start();

		if ( ! AC_IS_Auth::is_logged_in() ) {
			include AC_IS_PATH . 'templates/login.php';
			return ob_get_clean();
		}

		$view = isset( $_GET['ac_view'] ) ? sanitize_text_field( $_GET['ac_view'] ) : 'dashboard';
		$is_admin = AC_IS_Auth::is_admin();

		include AC_IS_PATH . 'templates/header.php';

		switch ( $view ) {
			case 'inventory':
				include AC_IS_PATH . 'templates/inventory.php';
				break;
			case 'add-product':
			case 'edit-product':
				if ( ! $is_admin ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'ac-inventory-system' ) . '</p>';
				} else {
					include AC_IS_PATH . 'templates/product-form.php';
				}
				break;
			case 'sales':
				include AC_IS_PATH . 'templates/sales-form.php';
				break;
			case 'sales-history':
				include AC_IS_PATH . 'templates/sales-history.php';
				break;
			case 'customers':
				include AC_IS_PATH . 'templates/customers.php';
				break;
			case 'filter-tracking':
				if ( ! AC_IS_Auth::can_access_filters() ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'ac-inventory-system' ) . '</p>';
				} else {
					include AC_IS_PATH . 'templates/filter-tracking.php';
				}
				break;
			case 'payroll':
				include AC_IS_PATH . 'templates/payroll.php';
				break;
			case 'invoice':
				include AC_IS_PATH . 'templates/invoice.php';
				break;
			case 'reports':
				if ( ! $is_admin ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'ac-inventory-system' ) . '</p>';
				} else {
					include AC_IS_PATH . 'templates/reports.php';
				}
				break;
			case 'settings':
				if ( ! $is_admin ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'ac-inventory-system' ) . '</p>';
				} else {
					include AC_IS_PATH . 'templates/settings.php';
				}
				break;
			default:
				include AC_IS_PATH . 'templates/dashboard-home.php';
				break;
		}

		include AC_IS_PATH . 'templates/footer.php';
		return ob_get_clean();
	}
}

new AC_IS_Shortcode();
