<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Ajax {

	public function __construct() {
		$actions = array(
			'logout', 'add_staff', 'save_staff', 'delete_staff', 'save_settings',
			'save_customer', 'delete_customer',
			'verify_fullscreen_password', 'undo_activity'
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
		$username = sanitize_text_field( $_POST['username'] );
		$password = $_POST['password'];

		if ( Sukna_Auth::login( $username, $password ) ) {
			Sukna_Audit::log('login', "User $username logged in");
			wp_send_json_success();
		} else {
			Sukna_Audit::log('failed_login', "Failed login attempt for $username");
			wp_send_json_error();
		}
	}

	public function logout() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		Sukna_Auth::logout();
		wp_send_json_success();
	}

	public function add_staff() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'sukna_staff';

		$data = array(
			'username'      => sanitize_text_field( $_POST['staff_username'] ),
			'password'      => password_hash( $_POST['staff_password'], PASSWORD_DEFAULT ),
			'name'          => sanitize_text_field( $_POST['staff_name'] ),
			'role'          => sanitize_text_field( $_POST['staff_role'] ),
		);

		$wpdb->insert( $table, $data );
		wp_send_json_success();
	}

	public function save_staff() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );

		$data = array(
			'username'      => sanitize_text_field( $_POST['staff_username'] ),
			'name'          => sanitize_text_field( $_POST['staff_name'] ),
			'role'          => sanitize_text_field( $_POST['staff_role'] ),
		);

		if ( ! empty( $_POST['staff_password'] ) ) {
			$data['password'] = password_hash( $_POST['staff_password'], PASSWORD_DEFAULT );
		}

		$wpdb->update( $wpdb->prefix . 'sukna_staff', $data, array( 'id' => $id ) );
		wp_send_json_success();
	}

	public function delete_staff() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'sukna_staff', array( 'id' => $id ) );
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

	public function save_customer() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_manager() ) wp_send_json_error( 'Unauthorized' );

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$name = sanitize_text_field( $_POST['name'] );
		Sukna_Audit::log( $id ? 'edit_customer' : 'add_customer', "Customer: $name" );
		$data = array(
			'name'            => sanitize_text_field( $_POST['name'] ),
			'phone'           => sanitize_text_field( $_POST['phone'] ),
			'phone_secondary' => sanitize_text_field( $_POST['phone_secondary'] ),
			'address'         => sanitize_text_field( $_POST['address'] ),
			'email'           => sanitize_email( $_POST['email'] ),
		);

		if ( $id ) {
			Sukna_Customers::update_customer( $id, $data );
		} else {
			Sukna_Customers::add_customer( $data );
		}
		wp_send_json_success();
	}

	public function delete_customer() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_manager() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sukna_customers WHERE id = %d", $id ) );
		if ( $customer ) {
			Sukna_Audit::log( 'delete_customer', "Customer: {$customer->name}", $customer );
		}
		Sukna_Customers::delete_customer( $id );
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

		if ( $log->action_type === 'delete_customer' ) {
			$wpdb->insert( "{$wpdb->prefix}sukna_customers", $data );
			$wpdb->delete( "{$wpdb->prefix}sukna_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		wp_send_json_error( 'Cannot undo this action' );
	}
}

new Sukna_Ajax();
