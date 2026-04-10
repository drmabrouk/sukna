<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Ajax {

	public function __construct() {
		$actions = array(
			'logout', 'add_user', 'save_user', 'delete_user', 'save_settings',
			'verify_fullscreen_password', 'undo_activity', 'register',
			'save_property', 'delete_property', 'save_room', 'delete_room', 'save_investment',
			'get_rooms', 'get_investments'
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_sukna_' . $action, array( $this, $action ) );
			add_action( 'wp_ajax_nopriv_sukna_' . $action, array( $this, $action ) );
		}

		add_action( 'wp_ajax_nopriv_sukna_login', array( $this, 'login' ) );
		add_action( 'wp_ajax_sukna_login', array( $this, 'login' ) );
	}

	public function login() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );

		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$password = $_POST['password'] ?? '';

		if ( Sukna_Auth::login( $phone, $password ) ) {
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

		// Basic regex for GCC/Egypt phone validation (simple check)
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

		$phone    = sanitize_text_field( $_POST['phone'] );

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
		$phone    = sanitize_text_field( $_POST['phone'] );

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
		if ( $user && $user->username === 'admin' ) wp_send_json_error( 'Cannot delete admin' );

		if ( $user ) {
			Sukna_Audit::log( 'delete_user', "User: {$user->phone}", $user );
			$wpdb->delete( $wpdb->prefix . 'sukna_staff', array( 'id' => $id ) );
			wp_send_json_success();
		} else {
			wp_send_json_error( 'User not found' );
		}
	}

	public function save_property() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] ?? 0 );
		$data = array(
			'name'     => sanitize_text_field( $_POST['name'] ),
			'address'  => sanitize_text_field( $_POST['address'] ),
			'owner_id' => intval( $_POST['owner_id'] ),
		);

		if ( $id ) {
			Sukna_Properties::update_property( $id, $data );
		} else {
			Sukna_Properties::add_property( $data );
		}
		wp_send_json_success();
	}

	public function delete_property() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		Sukna_Properties::delete_property( $id );
		wp_send_json_success();
	}

	public function get_rooms() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$property_id = intval( $_POST['property_id'] );
		$rooms = Sukna_Properties::get_rooms($property_id);
		wp_send_json_success($rooms);
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
			'rental_start_date' => sanitize_text_field( $_POST['rental_start_date'] ) ?: null,
			'payment_frequency' => sanitize_text_field( $_POST['payment_frequency'] ),
		);

		if ( $id ) {
			Sukna_Properties::update_room( $id, $data );
		} else {
			Sukna_Properties::add_room( $data );
		}
		wp_send_json_success();
	}

	public function delete_room() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );
		$id = intval( $_POST['id'] );
		Sukna_Properties::delete_room($id);
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

		$data = array(
			'investor_id' => intval( $_POST['investor_id'] ),
			'property_id' => intval( $_POST['property_id'] ),
			'amount'      => floatval( $_POST['amount'] ),
		);

		Sukna_Investments::add_investment( $data );
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

	public function verify_fullscreen_password() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		global $wpdb;
		$stored_pass = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'fullscreen_password'" ) ?: '123456789';
		$provided_pass = $_POST['password'];

		if ( $provided_pass === $stored_pass ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
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

		wp_send_json_error( 'Cannot undo this action' );
	}
}

new Sukna_Ajax();
