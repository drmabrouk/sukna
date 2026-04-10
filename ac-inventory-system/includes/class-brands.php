<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Brands {

	public static function get_brands( $category = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_brands';
		if ( $category ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE category = %s OR category = 'all' ORDER BY name ASC", $category ) );
		}
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" );
	}

	public static function get_brand( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_brands';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function add_brand( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_brands';
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function update_brand( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_brands';
		return $wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	public static function delete_brand( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_brands';
		return $wpdb->delete( $table, array( 'id' => $id ) );
	}
}
