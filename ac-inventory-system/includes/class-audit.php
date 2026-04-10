<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Reports_Audit {

	public static function log( $action, $description = '', $data = null ) {
		global $wpdb;
		$user = AC_IS_Auth::current_user();
		$user_id = $user ? $user->id : 'guest';

		$agent = $_SERVER['HTTP_USER_AGENT'];
		$device = 'Desktop';
		if ( wp_is_mobile() ) {
			$device = 'Mobile/Tablet';
		}

		$wpdb->insert( $wpdb->prefix . 'ac_is_activity_logs', array(
			'user_id'     => $user_id,
			'action_type' => $action,
			'description' => $description,
			'device_type' => $device,
			'device_info' => $agent,
			'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
			'meta_data'   => $data ? json_encode($data) : null
		) );
	}

	public static function get_logs() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ac_is_activity_logs ORDER BY action_date DESC LIMIT 200" );
	}
}
