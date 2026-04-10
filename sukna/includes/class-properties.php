<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Properties {

	public static function add_property( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function update_property( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';
		$wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	public static function delete_property( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';
		$wpdb->delete( $table, array( 'id' => $id ) );
		// Also delete rooms
		$wpdb->delete( $wpdb->prefix . 'sukna_rooms', array( 'property_id' => $id ) );
	}

	public static function get_property( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function get_all_properties( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';

		$query = "SELECT p.*, s.name as owner_name FROM $table p LEFT JOIN {$wpdb->prefix}sukna_staff s ON p.owner_id = s.id";

		if ( ! empty( $args['owner_id'] ) ) {
			$query .= $wpdb->prepare( " WHERE p.owner_id = %d", $args['owner_id'] );
		}

		$query .= " ORDER BY p.created_at DESC";

		return $wpdb->get_results( $query );
	}

	public static function add_room( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_rooms';
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function update_room( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_rooms';
		$wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	public static function delete_room( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_rooms';
		$wpdb->delete( $table, array( 'id' => $id ) );
	}

	public static function get_rooms( $property_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_rooms';
		return $wpdb->get_results( $wpdb->prepare( "SELECT r.*, s.name as tenant_name FROM $table r LEFT JOIN {$wpdb->prefix}sukna_staff s ON r.tenant_id = s.id WHERE r.property_id = %d", $property_id ) );
	}
}
