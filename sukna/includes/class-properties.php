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

	public static function save_contract( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_contracts';

		// Set checkout time to 12:00 PM for informational purposes
		// In a real DB we'd store the timestamp, but here we calculate based on start date

		$wpdb->insert( $table, $data );
		$contract_id = $wpdb->insert_id;

		// Generate Installments
		self::generate_installments( $contract_id, $data['total_value'], $data['duration_years'], $data['start_date'] );

		// Update room status
		$wpdb->update( "{$wpdb->prefix}sukna_rooms", array('status' => 'rented', 'tenant_id' => $data['tenant_id'], 'rental_start_date' => $data['start_date']), array('id' => $data['room_id']) );

		return $contract_id;
	}

	private static function generate_installments( $contract_id, $total_value, $years, $start_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_payments';

		$total_installments = $years * 4;
		$amount_per_installment = $total_value / $total_installments;

		for ( $i = 0; $i < $total_installments; $i++ ) {
			$due_date = date( 'Y-m-d', strtotime( "+ " . ($i * 3) . " months", strtotime( $start_date ) ) );
			$wpdb->insert( $table, array(
				'contract_id' => $contract_id,
				'amount'      => $amount_per_installment,
				'due_date'    => $due_date,
				'status'      => 'pending'
			) );
		}
	}

	public static function record_expense( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_expenses';
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function get_property_performance( $property_id ) {
		global $wpdb;

		// Income from all rented rooms of this property
		$income = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM(p.amount)
			FROM {$wpdb->prefix}sukna_payments p
			JOIN {$wpdb->prefix}sukna_contracts c ON p.contract_id = c.id
			JOIN {$wpdb->prefix}sukna_rooms r ON c.room_id = r.id
			WHERE r.property_id = %d AND p.status = 'paid'
		", $property_id ) ) ?: 0;

		// Expenses
		$expenses = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_expenses WHERE property_id = %d", $property_id ) ) ?: 0;

		return array(
			'income' => $income,
			'expenses' => $expenses,
			'net' => $income - $expenses
		);
	}
}
