<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Ajax {

	public function __construct() {
		$actions = array(
			'logout', 'add_user', 'save_user', 'delete_user', 'save_settings',
			'verify_fullscreen_password', 'undo_activity', 'register'
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

		// Basic validation
		if ( empty($data['first_name']) || empty($data['last_name']) || empty($data['phone']) || empty($data['password']) ) {
			wp_send_json_error( array( 'message' => __( 'يرجى ملء جميع الحقول المطلوبة.', 'sukna' ) ) );
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

		$username = sanitize_text_field( $_POST['username'] );
		$phone    = sanitize_text_field( $_POST['phone'] );

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE username = %s OR phone = %s", $username, $phone ) );
		if ( $exists ) wp_send_json_error( 'Username or Phone already exists' );

		$data = array(
			'username' => $username,
			'phone'    => $phone,
			'password' => password_hash( $_POST['password'], PASSWORD_DEFAULT ),
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),
		);

		$wpdb->insert( $table, $data );
		Sukna_Audit::log('add_user', "User $username added");
		wp_send_json_success();
	}

	public function save_user() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$username = sanitize_text_field( $_POST['username'] );
		$phone    = sanitize_text_field( $_POST['phone'] );

		$data = array(
			'username' => $username,
			'phone'    => $phone,
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),
		);

		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = password_hash( $_POST['password'], PASSWORD_DEFAULT );
		}

		$wpdb->update( $wpdb->prefix . 'sukna_staff', $data, array( 'id' => $id ) );
		Sukna_Audit::log('edit_user', "User $username updated");
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
			Sukna_Audit::log( 'delete_user', "User: {$user->username}", $user );
			$wpdb->delete( $wpdb->prefix . 'sukna_staff', array( 'id' => $id ) );
			wp_send_json_success();
		} else {
			wp_send_json_error( 'User not found' );
		}
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
		unset($data['id']); // Prevent ID collisions

		if ( $log->action_type === 'delete_user' ) {
			$wpdb->insert( "{$wpdb->prefix}sukna_staff", $data );
			$wpdb->delete( "{$wpdb->prefix}sukna_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		wp_send_json_error( 'Cannot undo this action' );
	}
}

new Sukna_Ajax();
