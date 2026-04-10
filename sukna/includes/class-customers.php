<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Customers {

	public static function add_customer( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_customers';
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function update_customer( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_customers';
		$wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	public static function delete_customer( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_customers';
		$wpdb->delete( $table, array( 'id' => $id ) );
	}

	public static function get_customer( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_customers';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function get_customer_by_phone( $phone ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_customers';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE phone = %s", $phone ) );
	}

	public static function get_all_customers() {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_customers';
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
	}
}
