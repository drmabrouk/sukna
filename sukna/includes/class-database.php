<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Database {

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_customers = $wpdb->prefix . 'sukna_customers';
		$table_staff     = $wpdb->prefix . 'sukna_staff';
		$table_settings  = $wpdb->prefix . 'sukna_settings';

		$sql = "CREATE TABLE $table_customers (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			phone varchar(50) NOT NULL,
			phone_secondary varchar(50),
			address text,
			email varchar(255),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_staff (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			username varchar(100) NOT NULL,
			password varchar(255) NOT NULL,
			name varchar(255),
			role varchar(50) DEFAULT 'employee',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY username (username)
		) $charset_collate;

		CREATE TABLE $table_settings (
			setting_key varchar(100) NOT NULL,
			setting_value text,
			PRIMARY KEY  (setting_key)
		) $charset_collate;

		CREATE TABLE {$wpdb->prefix}sukna_activity_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id varchar(100) NOT NULL,
			action_type varchar(100) NOT NULL,
			description text,
			device_type varchar(50),
			device_info text,
			ip_address varchar(50),
			meta_data longtext,
			action_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Seed initial data
		self::seed_data();
	}

	private static function seed_data() {
		global $wpdb;
		$table_staff    = $wpdb->prefix . 'sukna_staff';
		$table_settings = $wpdb->prefix . 'sukna_settings';

		// Default admin if not exists
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_staff WHERE username = %s", 'admin' ) );
		if ( ! $exists ) {
			$wpdb->insert( $table_staff, array(
				'username' => 'admin',
				'password' => password_hash( 'admin123', PASSWORD_DEFAULT ),
				'name'     => 'System Admin',
				'role'     => 'admin'
			) );
		}

		// Default settings
		$defaults = array(
			'fullscreen_password' => '123456789',
			'system_name'         => 'Sukna',
			'company_name'        => 'Sukna',
			'pwa_app_name'        => 'Sukna',
			'pwa_short_name'      => 'Sukna',
			'pwa_theme_color'     => '#2563eb',
			'pwa_bg_color'        => '#f1f5f9',
		);

		foreach ( $defaults as $key => $value ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT setting_key FROM $table_settings WHERE setting_key = %s", $key ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_settings, array(
					'setting_key'   => $key,
					'setting_value' => $value
				) );
			}
		}
	}
}
