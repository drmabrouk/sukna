<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Auth {

	public static function init() {
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
	}

	public static function login( $phone, $password ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_staff';

		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE phone = %s", $phone ) );

		if ( $user && password_verify( $password, $user->password ) ) {
			if ( $user->is_restricted ) {
				return new WP_Error( 'restricted', __( 'هذا الحساب مقيد حالياً. يرجى التواصل مع الإدارة.', 'sukna' ) );
			}
			return self::set_user_session( $user );
		}
		return false;
	}

	private static function set_user_session( $user ) {
		$_SESSION['sukna_user_id']   = $user->id;
		$_SESSION['sukna_phone']     = $user->phone;
		$_SESSION['sukna_user_role'] = $user->role;
		$_SESSION['sukna_user_name'] = $user->name;
		return true;
	}

	public static function register_user( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_staff';

		// Check if phone or email already exists
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE phone = %s OR (email IS NOT NULL AND email != '' AND email = %s)", $data['phone'], $data['email'] ) );
		if ( $exists ) {
			return new WP_Error( 'duplicate', __( 'رقم الهاتف أو البريد الإلكتروني مسجل بالفعل.', 'sukna' ) );
		}

		$inserted = $wpdb->insert( $table, array(
			'name'     => $data['first_name'] . ' ' . $data['last_name'],
			'phone'    => $data['phone'],
			'username' => $data['phone'], // Username is the phone number
			'email'    => $data['email'],
			'password' => password_hash( $data['password'], PASSWORD_DEFAULT ),
			'role'     => 'employee', // Default role for registration
		) );

		if ( $inserted ) {
			$user_id = $wpdb->insert_id;
			$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $user_id ) );
			self::set_user_session( $user );
			return $user_id;
		}

		return new WP_Error( 'db_error', __( 'حدث خطأ أثناء حفظ البيانات.', 'sukna' ) );
	}

	public static function logout() {
		unset( $_SESSION['sukna_user_id'] );
		unset( $_SESSION['sukna_phone'] );
		unset( $_SESSION['sukna_user_role'] );
		unset( $_SESSION['sukna_user_name'] );
	}

	public static function is_logged_in() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return isset( $_SESSION['sukna_user_id'] );
	}

	public static function current_user() {
		if ( current_user_can( 'manage_options' ) ) {
			$wp_user = wp_get_current_user();
			return (object) array(
				'id'   => 'wp_' . $wp_user->ID,
				'username' => $wp_user->user_login,
				'role' => 'admin',
				'name' => $wp_user->display_name
			);
		}

		if ( ! isset( $_SESSION['sukna_user_id'] ) ) return null;

		return (object) array(
			'id'   => $_SESSION['sukna_user_id'],
			'phone' => $_SESSION['sukna_phone'],
			'role' => $_SESSION['sukna_user_role'],
			'name' => $_SESSION['sukna_user_name']
		);
	}

	public static function is_admin() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$user = self::current_user();
		return $user && $user->role === 'admin';
	}

	public static function is_manager() {
		if ( self::is_admin() ) return true;
		$user = self::current_user();
		return $user && $user->role === 'manager';
	}

	public static function is_investor() {
		if ( self::is_admin() ) return true;
		$user = self::current_user();
		return $user && $user->role === 'investor';
	}

	public static function is_owner() {
		if ( self::is_admin() ) return true;
		$user = self::current_user();
		return $user && $user->role === 'owner';
	}

	public static function is_tenant() {
		if ( self::is_admin() ) return true;
		$user = self::current_user();
		return $user && $user->role === 'tenant';
	}

	public static function is_system_admin() {
		if ( current_user_can( 'manage_options' ) ) return true;
		$user = self::current_user();
		return $user && $user->role === 'admin';
	}

	public static function can_delete_records() {
		return self::is_manager();
	}

	public static function get_all_users() {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_staff';
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC" );
	}
}
