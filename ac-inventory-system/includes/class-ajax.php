<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Ajax {

	public function __construct() {
		$actions = array(
			'save_product', 'delete_product', 'record_sale', 'multi_sale',
			'search_products', 'get_customer', 'delete_invoice',
			'logout', 'record_attendance', 'add_staff', 'save_staff', 'delete_staff', 'save_settings',
			'save_customer', 'delete_customer', 'save_brand', 'delete_brand',
			'search_sales', 'update_staff_payroll', 'save_work_settings', 'approve_shifts',
			'verify_fullscreen_password', 'replace_candle', 'get_brands_by_category',
			'get_staff_logs', 'recognize_product', 'undo_activity'
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_ac_is_' . $action, array( $this, $action ) );
			add_action( 'wp_ajax_nopriv_ac_is_' . $action, array( $this, $action ) );
		}

		add_action( 'wp_ajax_nopriv_ac_is_login', array( $this, 'login' ) );
		add_action( 'wp_ajax_ac_is_login', array( $this, 'login' ) );
	}

	public function save_product() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::can_edit_products() ) wp_send_json_error( 'Unauthorized' );

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$data = array(
			'name'             => sanitize_text_field( $_POST['name'] ),
			'category'         => sanitize_text_field( $_POST['category'] ),
			'subcategory'      => sanitize_text_field( $_POST['subcategory'] ),
			'original_price'   => floatval( $_POST['original_price'] ),
			'purchase_cost'    => floatval( $_POST['purchase_cost'] ),
			'discount'         => floatval( $_POST['discount'] ),
			'final_price'      => floatval( $_POST['final_price'] ),
			'stock_quantity'   => intval( $_POST['stock_quantity'] ),
			'brand_id'         => intval( $_POST['brand_id'] ),
			'model_number'     => sanitize_text_field( $_POST['model_number'] ),
			'filter_stages'    => intval( $_POST['filter_stages'] ),
			'serial_number'    => sanitize_text_field( $_POST['serial_number'] ),
			'barcode'          => sanitize_text_field( $_POST['barcode'] ),
			'factory_barcode'  => sanitize_text_field( $_POST['factory_barcode'] ),
			'default_warranty' => sanitize_text_field( $_POST['default_warranty'] ),
		);

		if ( $id ) {
			AC_IS_Inventory::update_product( $id, $data );
		} else {
			AC_IS_Inventory::add_product( $data );
		}

		wp_send_json_success( 'Product saved' );
	}

	public function delete_product() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::can_delete_products() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		$product = AC_IS_Inventory::get_product( $id );
		if ( $product ) {
			AC_IS_Reports_Audit::log( 'delete_product', "Product: {$product->name}", $product );
		}
		AC_IS_Inventory::delete_product( $id );
		wp_send_json_success( 'Product deleted' );
	}

	public function record_sale() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		$data = array(
			'product_id'    => intval( $_POST['product_id'] ),
			'serial_number' => sanitize_text_field( $_POST['serial_number'] ),
			'quantity'      => intval( $_POST['quantity'] ),
			'total_price'   => floatval( $_POST['total_price'] ),
		);

		$sale_id = AC_IS_Sales::record_sale( $data );

		if ( $sale_id ) {
			wp_send_json_success( array( 'sale_id' => $sale_id ) );
		} else {
			wp_send_json_error( 'Failed to record sale' );
		}
	}

	public function save_branch() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$data = array(
			'name'     => sanitize_text_field( $_POST['name'] ),
			'location' => sanitize_textarea_field( $_POST['location'] ),
		);

		AC_IS_Inventory::add_branch( $data );
		wp_send_json_success( 'Branch saved' );
	}

	public function login() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		$username = sanitize_text_field( $_POST['username'] );
		$password = $_POST['password'];

		if ( AC_IS_Auth::login( $username, $password ) ) {
			AC_IS_Reports_Audit::log('login', "User $username logged in");
			wp_send_json_success();
		} else {
			AC_IS_Reports_Audit::log('failed_login', "Failed login attempt for $username");
			wp_send_json_error();
		}
	}

	public function record_attendance() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_manager() ) wp_send_json_error( 'Unauthorized' );

		$data = array(
			'staff_id'  => intval( $_POST['staff_id'] ),
			'work_date' => sanitize_text_field( $_POST['work_date'] ),
			'check_in'  => sanitize_text_field( $_POST['check_in'] ),
			'check_out' => sanitize_text_field( $_POST['check_out'] ),
			'status'    => sanitize_text_field( $_POST['status'] ),
		);

		AC_IS_Payroll::record_attendance( $data );
		wp_send_json_success();
	}

	public function logout() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		AC_IS_Auth::logout();
		wp_send_json_success();
	}

	public function add_staff() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_staff';

		$data = array(
			'username'      => sanitize_text_field( $_POST['staff_username'] ),
			'password'      => password_hash( $_POST['staff_password'], PASSWORD_DEFAULT ),
			'name'          => sanitize_text_field( $_POST['staff_name'] ),
			'role'          => sanitize_text_field( $_POST['staff_role'] ),
			'base_salary'   => floatval( $_POST['base_salary'] ),
			'working_days'  => intval( $_POST['working_days'] ),
			'working_hours' => intval( $_POST['working_hours'] ),
		);

		$wpdb->insert( $table, $data );
		wp_send_json_success();
	}

	public function save_staff() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );

		$data = array(
			'username'      => sanitize_text_field( $_POST['staff_username'] ),
			'name'          => sanitize_text_field( $_POST['staff_name'] ),
			'role'          => sanitize_text_field( $_POST['staff_role'] ),
			'base_salary'   => floatval( $_POST['base_salary'] ),
			'working_days'  => intval( $_POST['working_days'] ),
			'working_hours' => intval( $_POST['working_hours'] ),
		);

		if ( ! empty( $_POST['staff_password'] ) ) {
			$data['password'] = password_hash( $_POST['staff_password'], PASSWORD_DEFAULT );
		}

		$wpdb->update( $wpdb->prefix . 'ac_is_staff', $data, array( 'id' => $id ) );
		wp_send_json_success();
	}

	public function delete_staff() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'ac_is_staff', array( 'id' => $id ) );
		wp_send_json_success();
	}

	public function save_settings() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_settings';

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'ac_is_' ) === false && $key !== 'action' && $key !== 'nonce' ) {
				$wpdb->replace( $table, array(
					'setting_key'   => sanitize_key( $key ),
					'setting_value' => sanitize_text_field( $value )
				) );
			}
		}

		wp_send_json_success();
	}

	public function save_customer() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_manager() ) wp_send_json_error( 'Unauthorized' );

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$name = sanitize_text_field( $_POST['name'] );
		AC_IS_Reports_Audit::log( $id ? 'edit_product' : 'add_product', "Product: $name" );
		$data = array(
			'name'            => sanitize_text_field( $_POST['name'] ),
			'phone'           => sanitize_text_field( $_POST['phone'] ),
			'phone_secondary' => sanitize_text_field( $_POST['phone_secondary'] ),
			'address'         => sanitize_text_field( $_POST['address'] ),
			'email'           => sanitize_email( $_POST['email'] ),
		);

		if ( $id ) {
			AC_IS_Customers::update_customer( $id, $data );
		} else {
			AC_IS_Customers::add_customer( $data );
		}
		wp_send_json_success();
	}

	public function delete_customer() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_manager() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ac_is_customers WHERE id = %d", $id ) );
		if ( $customer ) {
			AC_IS_Reports_Audit::log( 'delete_customer', "Customer: {$customer->name}", $customer );
		}
		AC_IS_Customers::delete_customer( $id );
		wp_send_json_success();
	}

	public function delete_invoice() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::can_delete_records() ) wp_send_json_error( 'Unauthorized' );

		$invoice_id = intval( $_POST['invoice_id'] );
		AC_IS_Sales::delete_invoice( $invoice_id );
		wp_send_json_success();
	}

	public function save_brand() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$data = array(
			'name'     => sanitize_text_field( $_POST['name'] ),
			'logo_url' => esc_url_raw( $_POST['logo_url'] ),
		);

		if ( $id ) {
			AC_IS_Brands::update_brand( $id, $data );
		} else {
			AC_IS_Brands::add_brand( $data );
		}
		wp_send_json_success();
	}

	public function delete_brand() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		AC_IS_Brands::delete_brand( $id );
		wp_send_json_success();
	}

	public function update_staff_payroll() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() && ! AC_IS_Auth::is_manager() ) wp_send_json_error();

		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'ac_is_staff', array(
			'base_salary'   => floatval( $_POST['base_salary'] ),
			'working_hours' => intval( $_POST['working_hours'] ),
		), array( 'id' => intval( $_POST['staff_id'] ) ) );
		wp_send_json_success();
	}

	public function save_work_settings() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error();

		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_settings';
		$wpdb->replace( $table, array( 'setting_key' => 'default_start', 'setting_value' => sanitize_text_field( $_POST['default_start'] ) ) );
		$wpdb->replace( $table, array( 'setting_key' => 'default_end', 'setting_value' => sanitize_text_field( $_POST['default_end'] ) ) );
		wp_send_json_success();
	}

	public function approve_shifts() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_manager() ) wp_send_json_error();

		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'ac_is_attendance',
			array( 'status' => 'shift_approved' ),
			array( 'staff_id' => intval( $_POST['staff_id'] ), 'status' => 'shift_request' )
		);
		wp_send_json_success();
	}

	public function verify_fullscreen_password() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		global $wpdb;
		$stored_pass = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = 'fullscreen_password'" ) ?: '123456789';
		$provided_pass = $_POST['password'];

		if ( $provided_pass === $stored_pass ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	public function replace_candle() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['tracking_id'] );
		$res = AC_IS_Filters::replace_candle( $id );
		if ( $res ) wp_send_json_success();
		else wp_send_json_error();
	}

	public function get_brands_by_category() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		$category = sanitize_text_field( $_POST['category'] );
		$brands = AC_IS_Brands::get_brands( $category );
		wp_send_json_success( $brands );
	}

	public function get_staff_logs() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_manager() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$staff_id = intval( $_POST['staff_id'] );
		$month = sanitize_text_field( $_POST['month'] );
		$logs = AC_IS_Payroll::get_staff_attendance_logs( $staff_id, $month );
		$staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ac_is_staff WHERE id = %d", $staff_id));

		$html = '';
		$total_minutes = 0;
		if ( $logs ) {
			foreach ( $logs as $log ) {
				$duration = '-';
				if ( $log->check_in && $log->check_out ) {
					$diff = strtotime($log->check_out) - strtotime($log->check_in);
					$mins = floor($diff / 60);
					$total_minutes += $mins;
					$duration = floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
				}
				$status_map = array('present' => 'حاضر', 'absent' => 'غائب', 'leave' => 'إجازة', 'shift_approved' => 'شيفت إضافي');
				$st_label = $status_map[$log->status] ?? $log->status;

				$html .= "<tr>
					<td>{$log->work_date}</td>
					<td>" . ($log->check_in ?: '-') . "</td>
					<td>" . ($log->check_out ?: '-') . "</td>
					<td>{$duration}</td>
					<td><span class='ac-is-capsule " . (strpos($log->status, 'absent') !== false ? 'capsule-danger' : 'capsule-success') . "'>{$st_label}</span></td>
				</tr>";
			}
			$total_h = floor($total_minutes / 60);
			$total_m = $total_minutes % 60;
			$html .= "<tr style='background:#f8fafc; font-weight:bold;'>
				<td colspan='3'>" . __('إجمالي الساعات العمل:', 'ac-inventory-system') . "</td>
				<td colspan='2'>{$total_h}h {$total_m}m</td>
			</tr>";
		} else {
			$html = '<tr><td colspan="5" style="text-align:center;">' . __('لا توجد سجلات', 'ac-inventory-system') . '</td></tr>';
		}
		wp_send_json_success( array( 'html' => $html ) );
	}

	public function undo_activity() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$log_id = intval( $_POST['log_id'] );
		$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ac_is_activity_logs WHERE id = %d", $log_id));

		if ( ! $log || ! $log->meta_data ) wp_send_json_error( 'No undo data' );

		$data = json_decode( $log->meta_data, true );
		unset($data['id']); // Prevent ID collisions

		if ( $log->action_type === 'delete_product' ) {
			$wpdb->insert( "{$wpdb->prefix}ac_is_products", $data );
			$wpdb->delete( "{$wpdb->prefix}ac_is_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		} elseif ( $log->action_type === 'delete_customer' ) {
			$wpdb->insert( "{$wpdb->prefix}ac_is_customers", $data );
			$wpdb->delete( "{$wpdb->prefix}ac_is_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		wp_send_json_error( 'Cannot undo this action' );
	}

	public function recognize_product() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		$barcode = sanitize_text_field( $_POST['barcode'] );
		$product = AC_IS_Inventory::get_product_by_barcode( $barcode );

		if ( $product ) {
			wp_send_json_success( $product );
		} else {
			wp_send_json_error();
		}
	}

	public function search_sales() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );
		global $wpdb;
		$search = '%' . $wpdb->esc_like( sanitize_text_field( $_POST['query'] ) ) . '%';

		$sales = $wpdb->get_results( $wpdb->prepare( "
			SELECT s.*, i.invoice_date, c.name as customer_name, c.phone as customer_phone, p.name as product_name, i.operator_id
			FROM {$wpdb->prefix}ac_is_sales s
			JOIN {$wpdb->prefix}ac_is_invoices i ON s.invoice_id = i.id
			JOIN {$wpdb->prefix}ac_is_products p ON s.product_id = p.id
			LEFT JOIN {$wpdb->prefix}ac_is_customers c ON i.customer_id = c.id
			WHERE (c.name LIKE %s OR c.phone LIKE %s OR s.serial_number LIKE %s OR p.name LIKE %s OR p.barcode LIKE %s OR i.id = %s)
			ORDER BY i.invoice_date DESC LIMIT 50
		", $search, $search, $search, $search, $search, sanitize_text_field( $_POST['query'] ) ) );

		$html = '';
		if ( $sales ) {
			foreach ( $sales as $s ) {
				$operator_name = __('غير معروف', 'ac-inventory-system');
				if ( strpos( (string)$s->operator_id, 'wp_' ) === 0 ) {
					$wp_id = str_replace('wp_', '', (string)$s->operator_id);
					$user = get_userdata($wp_id);
					if ($user) $operator_name = $user->display_name;
				} else {
					$staff = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ac_is_staff WHERE id = %s", $s->operator_id));
					if ($staff) $operator_name = $staff->name;
				}

				$html .= '<tr>';
				$html .= '<td><strong>#' . $s->invoice_id . '</strong><br><small style="color:#64748b;">' . date('Y-m-d H:i', strtotime($s->invoice_date)) . '</small></td>';
				$html .= '<td><strong>' . esc_html($s->customer_name ?: __('عميل سريع', 'ac-inventory-system')) . '</strong><br><small>' . esc_html($s->customer_phone ?: '-') . '</small></td>';
				$html .= '<td><strong>' . esc_html($s->product_name) . '</strong><br><small>SN: ' . esc_html($s->serial_number ?: '-') . '</small></td>';
				$html .= '<td><span style="font-weight:700; color:var(--ac-primary);">' . number_format($s->total_price, 2) . ' EGP</span></td>';
				$html .= '<td><span class="ac-is-capsule capsule-info">' . esc_html($operator_name) . '</span></td>';
				$html .= '<td><div style="display:flex; gap:5px;"><a href="' . add_query_arg(array('ac_view' => 'invoice', 'invoice_id' => $s->invoice_id)) . '" class="ac-is-btn" style="padding:4px 8px; font-size:0.75rem; background:#64748b;"><span class="dashicons dashicons-visibility"></span></a>';
				if ( AC_IS_Auth::can_delete_records() ) {
					$html .= '<button class="ac-is-btn ac-is-delete-invoice" data-id="' . $s->invoice_id . '" style="padding:4px 8px; font-size:0.75rem; background:#ef4444;"><span class="dashicons dashicons-trash"></span></button>';
				}
				$html .= '</div></td></tr>';
			}
		} else {
			$html = '<tr><td colspan="6" style="text-align:center; padding:40px;">' . __('لم يتم العثور على نتائج', 'ac-inventory-system') . '</td></tr>';
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	public function search_products() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		$args = array(
			'search'   => sanitize_text_field( $_POST['search'] ),
			'category' => sanitize_text_field( $_POST['category'] ),
		);

		$products = AC_IS_Inventory::get_products( $args );
		wp_send_json_success( $products );
	}

	public function get_customer() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		$phone = sanitize_text_field( $_POST['phone'] );
		$customer = AC_IS_Customers::get_customer_by_phone( $phone );
		if ( $customer ) {
			wp_send_json_success( $customer );
		} else {
			wp_send_json_error( 'Not found' );
		}
	}

	public function multi_sale() {
		check_ajax_referer( 'ac_is_nonce', 'nonce' );
		if ( ! AC_IS_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$items = $_POST['items'];
		$customer_data = array(
			'name'    => sanitize_text_field( $_POST['customer_name'] ),
			'phone'   => sanitize_text_field( $_POST['customer_phone'] ),
			'address' => sanitize_text_field( $_POST['customer_address'] ),
			'email'   => sanitize_email( $_POST['customer_email'] ),
		);

		// Handle customer
		$customer_id = null;
		if ( $customer_data['phone'] !== '-' ) {
			$customer = AC_IS_Customers::get_customer_by_phone( $customer_data['phone'] );
			if ( $customer ) {
				AC_IS_Customers::update_customer( $customer->id, $customer_data );
				$customer_id = $customer->id;
			} else {
				$customer_id = AC_IS_Customers::add_customer( $customer_data );
			}
		}

		$current_user = AC_IS_Auth::current_user();
		$operator_id  = $current_user ? $current_user->id : 0;

		// Create Invoice
		AC_IS_Reports_Audit::log('sale', "Customer: {$customer_data['name']}, Amount: {$_POST['total_amount']}");
		$wpdb->insert( $wpdb->prefix . 'ac_is_invoices', array(
			'customer_id' => $customer_id,
			'total_amount' => floatval( $_POST['total_amount'] ),
			'operator_id' => $operator_id,
			'warranty_years' => intval( $_POST['warranty_years'] ),
		) );
		$invoice_id = $wpdb->insert_id;

		// Record individual sales
		foreach ( $items as $item ) {
			AC_IS_Sales::record_sale( array(
				'invoice_id'    => $invoice_id,
				'product_id'    => intval( $item['product_id'] ),
				'serial_number' => sanitize_text_field( $item['serial_number'] ),
				'quantity'      => intval( $item['quantity'] ),
				'total_price'   => floatval( $item['total_price'] ),
			) );
		}

		// Send Email if requested
		if ( ! empty( $_POST['send_email'] ) && ! empty( $customer_data['email'] ) ) {
			$this->send_invoice_email( $invoice_id, $customer_data['email'] );
		}

		wp_send_json_success( array( 'invoice_id' => $invoice_id ) );
	}

	private function send_invoice_email( $invoice_id, $to ) {
		$invoice = AC_IS_Sales::get_invoice( $invoice_id );
		$items = AC_IS_Sales::get_invoice_items( $invoice_id );

		global $wpdb;
		$company_name = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = 'company_name'" ) ?: get_bloginfo('name');

		$subject = sprintf( __( 'فاتورة مبيعات رقم #%d - %s', 'ac-inventory-system' ), $invoice_id, $company_name );

		$message = "<h2>" . __( 'شكراً لتعاملكم معنا', 'ac-inventory-system' ) . "</h2>";
		$message .= "<p>" . __( 'رقم الفاتورة:', 'ac-inventory-system' ) . " #" . $invoice_id . "</p>";
		$message .= "<p>" . __( 'الإجمالي:', 'ac-inventory-system' ) . " " . number_format($invoice->total_amount, 2) . " EGP</p>";
		$message .= "<h3>" . __( 'المنتجات:', 'ac-inventory-system' ) . "</h3><ul>";

		foreach ( $items as $item ) {
			$message .= "<li>" . esc_html( $item->product_name ) . " (" . $item->quantity . ") - " . number_format($item->total_price, 2) . " EGP</li>";
		}
		$message .= "</ul>";

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $to, $subject, $message, $headers );
	}
}

new AC_IS_Ajax();
