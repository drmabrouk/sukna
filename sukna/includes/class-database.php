<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Database {

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_staff        = $wpdb->prefix . 'sukna_staff';
		$table_settings     = $wpdb->prefix . 'sukna_settings';
		$table_properties   = $wpdb->prefix . 'sukna_properties';
		$table_rooms        = $wpdb->prefix . 'sukna_rooms';
		$table_investments  = $wpdb->prefix . 'sukna_investments';
		$table_wallets      = $wpdb->prefix . 'sukna_wallets';
		$table_transactions = $wpdb->prefix . 'sukna_transactions';
		$table_payments     = $wpdb->prefix . 'sukna_payments';

		$sql = "CREATE TABLE $table_staff (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			username varchar(100),
			phone varchar(50) NOT NULL,
			password varchar(255) NOT NULL,
			name varchar(255),
			email varchar(255),
			role varchar(50) DEFAULT 'employee',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY phone (phone),
			UNIQUE KEY username (username)
		) $charset_collate;

		CREATE TABLE $table_properties (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			address text,
			owner_id mediumint(9),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_rooms (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			property_id mediumint(9) NOT NULL,
			room_number varchar(50) NOT NULL,
			rental_price decimal(10,2) DEFAULT '0.00',
			status varchar(50) DEFAULT 'available',
			tenant_id mediumint(9),
			rental_start_date date,
			payment_frequency varchar(50) DEFAULT 'monthly',
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_investments (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			investor_id mediumint(9) NOT NULL,
			property_id mediumint(9) NOT NULL,
			amount decimal(10,2) NOT NULL,
			investment_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_wallets (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9) NOT NULL,
			balance decimal(10,2) DEFAULT '0.00',
			last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id)
		) $charset_collate;

		CREATE TABLE $table_transactions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9) NOT NULL,
			amount decimal(10,2) NOT NULL,
			type varchar(50) NOT NULL,
			description text,
			transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_payments (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			room_id mediumint(9) NOT NULL,
			tenant_id mediumint(9) NOT NULL,
			amount decimal(10,2) NOT NULL,
			due_date date,
			payment_date datetime,
			status varchar(50) DEFAULT 'pending',
			PRIMARY KEY  (id)
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
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_staff WHERE username = %s OR phone = %s", 'admin', 'admin' ) );
		if ( ! $exists ) {
			$wpdb->insert( $table_staff, array(
				'username' => 'admin',
				'phone'    => 'admin',
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
