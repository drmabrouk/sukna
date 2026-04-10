<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Customers {

	public static function get_customer_by_phone( $phone ) {
		if ( empty($phone) || $phone === '-' ) return null;
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_customers';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE phone = %s OR phone_secondary = %s", $phone, $phone ) );
	}

	public static function add_customer( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_customers';
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function update_customer( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_customers';
		return $wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	public static function delete_customer( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_customers';
		return $wpdb->delete( $table, array( 'id' => $id ) );
	}

	public static function get_customer( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_customers';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function get_all_customers() {
		global $wpdb;
		$table_customers = $wpdb->prefix . 'ac_is_customers';
		$table_invoices  = $wpdb->prefix . 'ac_is_invoices';
		$table_sales     = $wpdb->prefix . 'ac_is_sales';
		$table_products  = $wpdb->prefix . 'ac_is_products';

		return $wpdb->get_results( "
			SELECT c.*,
				COUNT(DISTINCT i.id) as total_invoices,
				SUM(i.total_amount) as total_revenue,
				(
					SELECT SUM(s.total_price - (p.purchase_cost * s.quantity))
					FROM $table_sales s
					JOIN $table_products p ON s.product_id = p.id
					JOIN $table_invoices i2 ON s.invoice_id = i2.id
					WHERE i2.customer_id = c.id
				) as net_profit
			FROM $table_customers c
			LEFT JOIN $table_invoices i ON c.id = i.customer_id
			GROUP BY c.id
			ORDER BY total_revenue DESC
		" );
	}
}
