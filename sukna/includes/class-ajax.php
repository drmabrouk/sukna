<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Ajax {

	public function __construct() {
		// Private actions (Logged-in only)
		$private_actions = array(
			'logout', 'add_user', 'save_user', 'delete_user', 'save_settings',
			'undo_activity', 'save_property', 'delete_property', 'save_room',
			'delete_room', 'save_investment', 'get_rooms', 'get_investments',
			'save_contract', 'record_expense', 'distribute_revenue',
			'reset_property_rooms', 'toggle_user_restriction', 'get_setup_items'
		);

		foreach ( $private_actions as $action ) {
			add_action( 'wp_ajax_sukna_' . $action, array( $this, $action ) );
		}

		// Public actions (Non-logged-in)
		$public_actions = array( 'login', 'register', 'get_report_html' );
		foreach ( $public_actions as $action ) {
			add_action( 'wp_ajax_sukna_' . $action, array( $this, $action ) );
			add_action( 'wp_ajax_nopriv_sukna_' . $action, array( $this, $action ) );
		}
	}

	public function login() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );

		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$password = $_POST['password'] ?? '';

		$result = Sukna_Auth::login( $phone, $password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		} elseif ( $result ) {
			Sukna_Audit::log('login', "User with phone $phone logged in");
			wp_send_json_success();
		} else {
			Sukna_Audit::log('failed_login', "Failed login attempt for phone $phone");
			wp_send_json_error( array( 'message' => __( 'بيانات الدخول غير صحيحة.', 'sukna' ) ) );
		}
	}

	public function register() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );

		$data = array(
			'first_name' => sanitize_text_field( $_POST['first_name'] ),
			'last_name'  => sanitize_text_field( $_POST['last_name'] ),
			'phone'      => sanitize_text_field( $_POST['phone'] ),
			'email'      => sanitize_email( $_POST['email'] ),
			'password'   => $_POST['password'],
		);

		if ( empty($data['first_name']) || empty($data['last_name']) || empty($data['phone']) || empty($data['password']) ) {
			wp_send_json_error( array( 'message' => __( 'يرجى ملء جميع الحقول المطلوبة.', 'sukna' ) ) );
		}

		if ( ! preg_match('/^\+(20|971|966|965|974|973|968)[0-9]{7,12}$/', $data['phone']) ) {
			wp_send_json_error( array( 'message' => __( 'تنسيق رقم الهاتف غير صالح لهذه الدولة.', 'sukna' ) ) );
		}

		if ( strlen($data['password']) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'كلمة المرور يجب أن لا تقل عن 8 أحرف.', 'sukna' ) ) );
		}

		$result = Sukna_Auth::register_user( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		Sukna_Audit::log('registration', "New user registered: {$data['phone']}");
		wp_send_json_success();
	}

	public function logout() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		Sukna_Auth::logout();
		wp_send_json_success();
	}

	public function add_user() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'sukna_staff';
		$phone = sanitize_text_field( $_POST['phone'] );

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE phone = %s", $phone ) );
		if ( $exists ) wp_send_json_error( 'Phone already registered' );

		$data = array(
			'username' => $phone,
			'phone'    => $phone,
			'password' => password_hash( $_POST['password'], PASSWORD_DEFAULT ),
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),
		);

		$wpdb->insert( $table, $data );
		Sukna_Audit::log('add_user', "User $phone added by admin");
		wp_send_json_success();
	}

	public function save_user() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$phone = sanitize_text_field( $_POST['phone'] );

		$data = array(
			'username' => $phone,
			'phone'    => $phone,
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),
		);

		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = password_hash( $_POST['password'], PASSWORD_DEFAULT );
		}

		$wpdb->update( $wpdb->prefix . 'sukna_staff', $data, array( 'id' => $id ) );
		Sukna_Audit::log('edit_user', "User $phone updated by admin");
		wp_send_json_success();
	}

	public function delete_user() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sukna_staff WHERE id = %d", $id ) );
		if ( $user && ($user->username === 'admin' || $user->phone === '1234567890')) wp_send_json_error( 'Cannot delete admin' );

		if ( $user ) {
			Sukna_Audit::log( 'delete_user', sprintf(__('حذف المستخدم: %s', 'sukna'), $user->name), $user );
			$wpdb->delete( $wpdb->prefix . 'sukna_staff', array( 'id' => $id ) );
			wp_send_json_success();
		} else {
			wp_send_json_error( 'User not found' );
		}
	}

	public function toggle_user_restriction() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sukna_staff WHERE id = %d", $id ) );
		if ( ! $user ) wp_send_json_error( 'User not found' );
		if ( $user->username === 'admin' || $user->phone === '1234567890' ) wp_send_json_error( 'Cannot restrict admin' );

		$new_status = $user->is_restricted ? 0 : 1;
		$wpdb->update( "{$wpdb->prefix}sukna_staff", array( 'is_restricted' => $new_status ), array( 'id' => $id ) );

		$action = $new_status ? __('تقييد', 'sukna') : __('إلغاء تقييد', 'sukna');
		Sukna_Audit::log( 'toggle_restriction', sprintf(__('%s حساب المستخدم: %s', 'sukna'), $action, $user->name) );

		wp_send_json_success();
	}

	public function save_property() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] ?? 0 );
		$data = array(
			'name'                     => sanitize_text_field( $_POST['name'] ),
			'address'                  => sanitize_text_field( $_POST['address'] ),
			'owner_id'                 => intval( $_POST['owner_id'] ),
			'country'                  => sanitize_text_field( $_POST['country'] ),
			'city'                     => sanitize_text_field( $_POST['city'] ),
			'state_emirate'            => sanitize_text_field( $_POST['state_emirate'] ),
			'property_type'            => sanitize_text_field( $_POST['property_type'] ),
			'property_subtype'         => sanitize_text_field( $_POST['property_subtype'] ),
			'apartment_number'         => sanitize_text_field( $_POST['apartment_number'] ),
			'floor_number'             => sanitize_text_field( $_POST['floor_number'] ),
			'total_rooms'              => intval( $_POST['total_rooms'] ),
			'expected_rent_per_room'   => floatval( $_POST['expected_rent_per_room'] ),
			'contract_start_date'      => sanitize_text_field( $_POST['contract_start_date'] ),
			'investment_start_date'    => sanitize_text_field( $_POST['investment_start_date'] ),
			'contract_duration'        => intval( $_POST['contract_duration'] ),
			'base_value'               => floatval( $_POST['base_value'] ),
			'gov_fees'                 => floatval( $_POST['gov_fees'] ),
		);

		// Handle setup items
		$setup_items = array();
		if ( ! empty($_POST['setup_item_names']) && is_array($_POST['setup_item_names']) ) {
			foreach ( $_POST['setup_item_names'] as $idx => $name ) {
				if ( empty($name) ) continue;
				$setup_items[] = array(
					'name' => sanitize_text_field($name),
					'cost' => floatval($_POST['setup_item_costs'][$idx] ?? 0)
				);
			}
		}
		$data['setup_items'] = $setup_items;

		if ( $id ) {
			Sukna_Properties::update_property( $id, $data );
			Sukna_Audit::log('edit_property', sprintf(__('تعديل بيانات العقار: %s', 'sukna'), $data['name']));
		} else {
			$data['initiator_id'] = Sukna_Auth::current_user()->id;
			Sukna_Properties::add_property( $data );
			Sukna_Audit::log('add_property', sprintf(__('إضافة عقار جديد: %s', 'sukna'), $data['name']));
		}
		wp_send_json_success();
	}

	public function delete_property() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		$p = Sukna_Properties::get_property($id);
		if ( ! $p ) wp_send_json_error( 'Not found' );

		Sukna_Audit::log('delete_property', sprintf(__('حذف العقار: %s', 'sukna'), $p->name), $p);
		Sukna_Properties::delete_property( $id );

		wp_send_json_success();
	}

	public function get_rooms() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$property_id = intval( $_POST['property_id'] );
		$rooms = Sukna_Properties::get_rooms($property_id);
		wp_send_json_success($rooms);
	}

	public function save_contract() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$duration_years = intval( $_POST['duration_years'] );
		$data = array(
			'room_id'           => intval( $_POST['room_id'] ),
			'tenant_id'         => intval( $_POST['tenant_id'] ?: 0 ) ?: null,
			'guest_tenant_name' => sanitize_text_field( $_POST['guest_tenant_name'] ?? '' ),
			'start_date'        => sanitize_text_field( $_POST['start_date'] ),
			'duration_years'    => $duration_years,
			'total_value'       => floatval( $_POST['total_value'] ),
			'installment_count' => intval( $_POST['installment_count'] ?: ($duration_years * 4) ),
		);

		Sukna_Properties::save_contract($data);

		$tenant_name = $data['guest_tenant_name'] ?: 'ID: ' . $data['tenant_id'];
		Sukna_Audit::log('save_contract', sprintf(__('تفعيل عقد إيجار جديد للمستأجر: %s', 'sukna'), $tenant_name));

		wp_send_json_success();
	}

	public function save_room() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] ?? 0 );
		$data = array(
			'property_id'       => intval( $_POST['property_id'] ),
			'room_number'       => sanitize_text_field( $_POST['room_number'] ),
			'rental_price'      => floatval( $_POST['rental_price'] ),
			'status'            => sanitize_text_field( $_POST['status'] ),
			'tenant_id'         => intval( $_POST['tenant_id'] ?: 0 ) ?: null,
			'guest_tenant_name' => sanitize_text_field( $_POST['guest_tenant_name'] ?? '' ),
			'rental_start_date' => sanitize_text_field( $_POST['rental_start_date'] ) ?: null,
			'payment_frequency' => sanitize_text_field( $_POST['payment_frequency'] ),
		);

		if ( $id ) {
			Sukna_Properties::update_room( $id, $data );
			Sukna_Audit::log('edit_room', sprintf(__('تعديل بيانات الوحدة: %s', 'sukna'), $data['room_number']));
		} else {
			Sukna_Properties::add_room( $data );
			Sukna_Audit::log('add_room', sprintf(__('إضافة وحدة جديدة: %s', 'sukna'), $data['room_number']));
		}
		wp_send_json_success();
	}

	public function record_expense() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$data = array(
			'property_id' => intval( $_POST['property_id'] ),
			'category'    => sanitize_text_field( $_POST['category'] ),
			'amount'      => floatval( $_POST['amount'] ),
		);

		Sukna_Properties::record_expense($data);
		$p = Sukna_Properties::get_property($data['property_id']);
		Sukna_Audit::log('record_expense', sprintf(__('تسجيل مصروفات للعقار %s: %s EGP (%s)', 'sukna'), $p->name ?? $data['property_id'], $data['amount'], $data['category']));

		wp_send_json_success();
	}

	public function reset_property_rooms() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		Sukna_Properties::reset_rooms_occupancy($id);
		$p = Sukna_Properties::get_property($id);
		Sukna_Audit::log('reset_rooms', sprintf(__('تصفير كافة وحدات العقار: %s', 'sukna'), $p->name ?? $id));

		wp_send_json_success();
	}

	public function distribute_revenue() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$property_id = intval( $_POST['id'] );
		$net_profit  = floatval( $_POST['net_profit'] );

		Sukna_Investments::distribute_revenue($property_id, $net_profit);
		$p = Sukna_Properties::get_property($property_id);
		Sukna_Audit::log('distribute_revenue', sprintf(__('توزيع أرباح العقار %s بقيمة %s EGP', 'sukna'), $p->name ?? $property_id, $net_profit));

		wp_send_json_success();
	}

	public function get_investments() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$property_id = intval( $_POST['property_id'] );
		$investments = Sukna_Investments::get_property_investments($property_id);
		wp_send_json_success($investments);
	}

	public function save_investment() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$data = array(
			'investor_id' => intval( $_POST['investor_id'] ),
			'property_id' => intval( $_POST['property_id'] ),
			'amount'      => floatval( $_POST['amount'] ),
		);

		Sukna_Investments::add_investment( $data );
		$p = Sukna_Properties::get_property($data['property_id']);
		$u = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}sukna_staff WHERE id = %d", $data['investor_id']));
		Sukna_Audit::log('add_investment', sprintf(__('إضافة مساهمة من %s للعقار %s بقيمة %s EGP', 'sukna'), $u->name ?? $data['investor_id'], $p->name ?? $data['property_id'], $data['amount']));

		wp_send_json_success();
	}

	public function save_settings() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'sukna_settings';

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'sukna_' ) === false && $key !== 'action' && $key !== 'nonce' ) {
				$wpdb->replace( $table, array(
					'setting_key'   => sanitize_key( $key ),
					'setting_value' => sanitize_text_field( $value )
				) );
			}
		}

		wp_send_json_success();
	}


	public function get_setup_items() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$id = intval( $_POST['id'] );
		$items = Sukna_Properties::get_setup_items($id);
		wp_send_json_success($items);
	}

	public function get_report_html() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$id = intval( $_POST['id'] );
		require_once SUKNA_PATH . 'templates/report-pdf-template.php';
		$html = sukna_get_property_report_html($id);
		wp_send_json_success($html);
	}

	public function undo_activity() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$log_id = intval( $_POST['log_id'] );
		$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sukna_activity_logs WHERE id = %d", $log_id));

		if ( ! $log || ! $log->meta_data ) wp_send_json_error( 'No undo data' );

		$data = json_decode( $log->meta_data, true );
		unset($data['id']);

		if ( $log->action_type === 'delete_user' ) {
			$wpdb->insert( "{$wpdb->prefix}sukna_staff", $data );
			$wpdb->delete( "{$wpdb->prefix}sukna_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		if ( $log->action_type === 'delete_property' ) {
			$wpdb->insert( "{$wpdb->prefix}sukna_properties", $data );
			$wpdb->delete( "{$wpdb->prefix}sukna_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		wp_send_json_error( 'Cannot undo this action' );
	}
}

new Sukna_Ajax();
