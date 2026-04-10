<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Inventory {

	public static function get_products( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_products';
		
		$where = "1=1";

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where .= $wpdb->prepare( " AND (name LIKE %s OR barcode LIKE %s OR serial_number LIKE %s)", $search, $search, $search );
		}

		if ( ! empty( $args['category'] ) ) {
			$where .= $wpdb->prepare( " AND category = %s", $args['category'] );
		}
		
		return $wpdb->get_results( "SELECT * FROM $table WHERE $where ORDER BY created_at DESC" );
	}

	public static function get_product( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_products';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function get_product_by_barcode( $barcode ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_products';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE barcode = %s OR factory_barcode = %s", $barcode, $barcode ) );
	}

	public static function add_product( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_products';

		// Auto-generate serial and barcode if not provided
		if ( empty( $data['barcode'] ) ) {
			$data['barcode'] = 'AC-' . strtoupper( wp_generate_password( 8, false ) );
		}
		if ( empty( $data['serial_number'] ) ) {
			$data['serial_number'] = $data['barcode'];
		}

		return $wpdb->insert( $table, $data );
	}

	public static function update_product( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_products';

		// Auto-generate serial if empty
		if ( empty( $data['serial_number'] ) && ! empty( $data['barcode'] ) ) {
			$data['serial_number'] = $data['barcode'];
		}

		return $wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	public static function delete_product( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_products';
		return $wpdb->delete( $table, array( 'id' => $id ) );
	}

}
