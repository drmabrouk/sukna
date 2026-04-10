<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Database {

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_products  = $wpdb->prefix . 'ac_is_products';
		$table_sales     = $wpdb->prefix . 'ac_is_sales';
		$table_customers = $wpdb->prefix . 'ac_is_customers';
		$table_invoices  = $wpdb->prefix . 'ac_is_invoices';
		$table_staff     = $wpdb->prefix . 'ac_is_staff';
		$table_settings  = $wpdb->prefix . 'ac_is_settings';
		$table_brands    = $wpdb->prefix . 'ac_is_brands';

		$sql = "CREATE TABLE $table_brands (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			category varchar(100) DEFAULT 'all',
			logo_url text,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_products (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			category varchar(100),
			subcategory varchar(100),
			description text,
			original_price decimal(10,2) DEFAULT '0.00',
			discount decimal(10,2) DEFAULT '0.00',
			final_price decimal(10,2) DEFAULT '0.00',
			purchase_cost decimal(10,2) DEFAULT '0.00',
			stock_quantity int DEFAULT 0,
			brand_id mediumint(9),
			model_number varchar(100),
			filter_stages int,
			serial_number varchar(255),
			barcode varchar(255),
			factory_barcode varchar(255),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_customers (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			phone varchar(50) NOT NULL,
			phone_secondary varchar(50),
			address text,
			email varchar(255),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_invoices (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			customer_id mediumint(9),
			total_amount decimal(10,2) NOT NULL,
			operator_id varchar(100) NOT NULL,
			warranty_years int DEFAULT 0,
			invoice_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_sales (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			invoice_id mediumint(9) NOT NULL,
			product_id mediumint(9) NOT NULL,
			serial_number varchar(255),
			quantity int NOT NULL,
			total_price decimal(10,2) NOT NULL,
			operator_id varchar(100) NOT NULL,
			sale_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_staff (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			username varchar(100) NOT NULL,
			password varchar(255) NOT NULL,
			name varchar(255),
			role varchar(50) DEFAULT 'employee',
			base_salary decimal(10,2) DEFAULT '0.00',
			working_days int DEFAULT 26,
			working_hours int DEFAULT 8,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY username (username)
		) $charset_collate;

		CREATE TABLE {$wpdb->prefix}ac_is_attendance (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			staff_id mediumint(9) NOT NULL,
			work_date date NOT NULL,
			check_in time,
			check_out time,
			status varchar(50) DEFAULT 'present',
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $table_settings (
			setting_key varchar(100) NOT NULL,
			setting_value text,
			PRIMARY KEY  (setting_key)
		) $charset_collate;

		CREATE TABLE {$wpdb->prefix}ac_is_filter_tracking (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			customer_id mediumint(9) NOT NULL,
			product_id mediumint(9) NOT NULL,
			invoice_id mediumint(9) NOT NULL,
			stage_number int NOT NULL,
			installation_date date NOT NULL,
			expiry_date date NOT NULL,
			status varchar(50) DEFAULT 'active',
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE {$wpdb->prefix}ac_is_filter_logs (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			tracking_id mediumint(9) NOT NULL,
			action_date datetime DEFAULT CURRENT_TIMESTAMP,
			action_type varchar(50),
			operator_id varchar(100),
			notes text,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE {$wpdb->prefix}ac_is_activity_logs (
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
		$table_staff    = $wpdb->prefix . 'ac_is_staff';
		$table_settings = $wpdb->prefix . 'ac_is_settings';
		$table_brands   = $wpdb->prefix . 'ac_is_brands';

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
			'system_name'         => 'نظام البيع',
			'company_name'        => get_bloginfo('name'),
			'pwa_app_name'        => 'نظام المبيعات',
			'pwa_short_name'      => 'المبيعات',
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

		// Seed 15 Brands per category (Arabic)
		$categories = array(
			'ac'      => array('كارير', 'ال جي', 'سامسونج', 'شارب', 'جري', 'ميديا', 'يونيون اير', 'تورنيدو', 'فريش', 'يورك', 'دايكن', 'ميتسوبيشي', 'هاير', 'الزامل', 'ترين'),
			'filter'  => array('تانك', 'فريش', 'باناسونيك', 'أكوا', 'بيور', 'بلو سكاي', 'ووتر وورلد', 'كريستال', 'بيو', 'نانو', 'فلتر كينج', 'إيكو', 'سيف', 'ماجيك', 'جولد'),
			'cooling' => array('كولداير', 'أيس بيرج', 'أركتيك', 'بولار', 'فروستي', 'جليسير', 'زيرو', 'فريجو', 'كول تك', 'ميجا كول', 'سوبر أيس', 'تشيل', 'نوفا', 'ألترا', 'إكستريم')
		);

		foreach($categories as $cat => $brands) {
			foreach($brands as $brand_name) {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_brands WHERE name = %s AND category = %s", $brand_name, $cat ) );
				if ( ! $exists ) {
					$wpdb->insert( $table_brands, array( 'name' => $brand_name, 'category' => $cat ) );
				}
			}
		}
	}
}
