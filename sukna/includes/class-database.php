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
		$table_contracts    = $wpdb->prefix . 'sukna_contracts';
		$table_expenses     = $wpdb->prefix . 'sukna_expenses';

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
			UNIQUE KEY phone (phone)
		) $charset_collate;

		CREATE TABLE $table_properties (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			address text,
			owner_id mediumint(9),
			valuation decimal(15,2) DEFAULT '0.00',
			annual_rent_value decimal(15,2) DEFAULT '0.00',
			property_type varchar(50) DEFAULT 'purchased',
			initiator_id mediumint(9),
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
			guest_tenant_name varchar(255),
			rental_start_date date,
			payment_frequency varchar(50) DEFAULT 'monthly',
			is_rented_to_third_party tinyint(1) DEFAULT 0,
			additional_rental_value decimal(10,2) DEFAULT '0.00',
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_contracts (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			room_id mediumint(9) NOT NULL,
			tenant_id mediumint(9),
			guest_tenant_name varchar(255),
			start_date date NOT NULL,
			duration_years int NOT NULL DEFAULT 1,
			total_value decimal(15,2) NOT NULL,
			installment_count int DEFAULT 4,
			status varchar(50) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
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
			balance decimal(15,2) DEFAULT '0.00',
			last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id)
		) $charset_collate;

		CREATE TABLE $table_transactions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9) NOT NULL,
			amount decimal(15,2) NOT NULL,
			type varchar(50) NOT NULL,
			description text,
			transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_expenses (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			property_id mediumint(9) NOT NULL,
			category varchar(100) NOT NULL,
			amount decimal(10,2) NOT NULL,
			expense_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_payments (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			contract_id mediumint(9) NOT NULL,
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
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_staff WHERE phone = %s", 'admin' ) );
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
			'pwa_theme_color'     => '#000000',
			'pwa_bg_color'        => '#ffffff',
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
