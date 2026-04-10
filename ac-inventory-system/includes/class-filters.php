<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Filters {

	public static function get_all_tracking( $args = array() ) {
		global $wpdb;
		$table_tracking = $wpdb->prefix . 'ac_is_filter_tracking';
		$table_customers = $wpdb->prefix . 'ac_is_customers';
		$table_products = $wpdb->prefix . 'ac_is_products';

		$where = "1=1";
		if ( ! empty($args['status']) && $args['status'] === 'alert' ) {
			$where .= " AND t.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
		}

		if ( ! empty($args['search']) ) {
			$s = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where .= $wpdb->prepare( " AND (c.name LIKE %s OR c.phone LIKE %s OR p.name LIKE %s)", $s, $s, $s );
		}

		// Group by invoice/customer operation
		$results = $wpdb->get_results( "
			SELECT t.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.email as customer_email, p.name as product_name
			FROM $table_tracking t
			JOIN $table_customers c ON t.customer_id = c.id
			JOIN $table_products p ON t.product_id = p.id
			WHERE $where
			ORDER BY t.expiry_date ASC
		" );

		$grouped = array();
		foreach ( $results as $row ) {
			$key = $row->customer_id . '_' . $row->invoice_id;
			if ( ! isset($grouped[$key]) ) {
				$grouped[$key] = (object) array(
					'customer_id'   => $row->customer_id,
					'customer_name' => $row->customer_name,
					'customer_phone'=> $row->customer_phone,
					'customer_address' => $row->customer_address,
					'customer_email' => $row->customer_email,
					'product_name'  => $row->product_name,
					'invoice_id'    => $row->invoice_id,
					'stages'        => array()
				);
			}
			$grouped[$key]->stages[] = $row;
		}

		return $grouped;
	}

	public static function replace_candle( $tracking_id ) {
		global $wpdb;
		$table_tracking = $wpdb->prefix . 'ac_is_filter_tracking';
		$table_logs = $wpdb->prefix . 'ac_is_filter_logs';

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_tracking WHERE id = %d", $tracking_id ) );
		if ( ! $item ) return false;

		// Get custom validity from settings if exists
		$validity_setting = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = %s", 'filter_stage_' . $item->stage_number . '_validity'));

		if ( $validity_setting ) {
			$validity = intval($validity_setting);
		} else {
			$validity = ($item->stage_number == 1) ? 3 : (($item->stage_number <= 3) ? 6 : 12);
		}

		$new_expiry = date('Y-m-d', strtotime("+$validity months"));

		$wpdb->update( $table_tracking, array(
			'installation_date' => current_time('mysql'),
			'expiry_date'       => $new_expiry,
			'status'            => 'active'
		), array( 'id' => $tracking_id ) );

		$current_user = AC_IS_Auth::current_user();
		$operator_id = $current_user ? $current_user->id : 0;

		$wpdb->insert( $table_logs, array(
			'tracking_id' => $tracking_id,
			'action_type' => 'replacement',
			'operator_id' => $operator_id,
			'notes'       => sprintf( __( 'تم تغيير الشمعة رقم %d', 'ac-inventory-system' ), $item->stage_number )
		) );

		return true;
	}
}
