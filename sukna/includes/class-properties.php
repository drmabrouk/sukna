<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Properties {

	public static function add_property( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';

		$setup_items = $data['setup_items'] ?? array();
		unset($data['setup_items']);

		$wpdb->insert( $table, $data );
		$id = $wpdb->insert_id;

		// Automatically add creator as an investor if they are master tenant/owner
		if ( ! empty($data['owner_id']) ) {
			Sukna_Investments::add_investment( array(
				'investor_id' => $data['owner_id'],
				'property_id' => $id,
				'amount'      => 0, // Initial entry, can be updated later
				'installments_paid' => 0
			) );
		}

		if ( ! empty( $setup_items ) ) {
			self::save_setup_items( $id, $setup_items );
		}

		if ( ! empty( $data['total_rooms'] ) ) {
			self::auto_generate_rooms( $id, intval( $data['total_rooms'] ) );
		}

		return $id;
	}

	public static function update_property( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';

		$old_rooms = $wpdb->get_var( $wpdb->prepare( "SELECT total_rooms FROM $table WHERE id = %d", $id ) );

		$setup_items = $data['setup_items'] ?? array();
		unset($data['setup_items']);

		$wpdb->update( $table, $data, array( 'id' => $id ) );

		if ( ! empty( $setup_items ) ) {
			self::save_setup_items( $id, $setup_items );
		}

		if ( isset( $data['total_rooms'] ) && intval( $data['total_rooms'] ) != intval( $old_rooms ) ) {
			self::auto_generate_rooms( $id, intval( $data['total_rooms'] ) );
		}
	}

	private static function save_setup_items( $property_id, $items ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_setup_items';

		$wpdb->delete( $table, array( 'property_id' => $property_id ) );

		$total_setup_cost = 0;
		foreach ( $items as $item ) {
			if ( empty($item['name']) ) continue;
			$cost = floatval($item['cost']);
			$wpdb->insert( $table, array(
				'property_id' => $property_id,
				'item_name'   => sanitize_text_field($item['name']),
				'item_cost'   => $cost
			) );
			$total_setup_cost += $cost;
		}

		$wpdb->update( $wpdb->prefix . 'sukna_properties', array( 'total_setup_cost' => $total_setup_cost ), array( 'id' => $property_id ) );
	}

	public static function get_setup_items( $property_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_setup_items';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE property_id = %d", $property_id ) );
	}

	private static function auto_generate_rooms( $property_id, $count ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_rooms';

		$existing_rooms = $wpdb->get_results( $wpdb->prepare( "SELECT room_number FROM $table WHERE property_id = %d", $property_id ) );
		$existing_numbers = wp_list_pluck( $existing_rooms, 'room_number' );

		for ( $i = 1; $i <= $count; $i++ ) {
			$room_number = (string) $i;
			if ( ! in_array( $room_number, $existing_numbers ) ) {
				$wpdb->insert( $table, array(
					'property_id' => $property_id,
					'room_number' => $room_number,
					'status'      => 'available'
				) );
			}
		}

		// Optionally delete rooms if count decreased?
		// For safety, we only add. If the user wants to remove, they should do it manually or we can add a cleanup logic.
		// Requirement says "Automatically generate and display all rooms based on the defined number".
	}

	public static function delete_property( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_properties';
		$wpdb->delete( $table, array( 'id' => $id ) );
		// Also delete rooms and setup items
		$wpdb->delete( $wpdb->prefix . 'sukna_rooms', array( 'property_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'sukna_setup_items', array( 'property_id' => $id ) );
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
		$where = array();

		if ( ! empty( $args['owner_id'] ) ) {
			$where[] = $wpdb->prepare( "p.owner_id = %d", $args['owner_id'] );
		}

		if ( ! empty( $args['country'] ) ) {
			$where[] = $wpdb->prepare( "p.country = %s", $args['country'] );
		}

		if ( ! empty( $args['state_emirate'] ) ) {
			$where[] = $wpdb->prepare( "p.state_emirate = %s", $args['state_emirate'] );
		}

		if ( ! empty( $args['property_type'] ) ) {
			$where[] = $wpdb->prepare( "p.property_type = %s", $args['property_type'] );
		}

		if ( ! empty( $where ) ) {
			$query .= " WHERE " . implode( " AND ", $where );
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

	public static function reset_rooms_occupancy( $property_id ) {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}sukna_rooms", array(
			'status' => 'available',
			'tenant_id' => null,
			'guest_tenant_name' => null,
			'rental_start_date' => null
		), array( 'property_id' => $property_id ) );
	}

	public static function get_rooms( $property_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_rooms';
		return $wpdb->get_results( $wpdb->prepare( "SELECT r.*, COALESCE(s.name, r.guest_tenant_name) as tenant_name FROM $table r LEFT JOIN {$wpdb->prefix}sukna_staff s ON r.tenant_id = s.id WHERE r.property_id = %d", $property_id ) );
	}

	public static function save_contract( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_contracts';

		$wpdb->insert( $table, $data );
		$contract_id = $wpdb->insert_id;

		// Generate Installments
		$installment_count = $data['installment_count'] ?? ( $data['duration_years'] * 4 );
		self::generate_installments( $contract_id, $data['total_value'], $installment_count, $data['start_date'] );

		// Update room status
		$room_data = array(
			'status'            => 'rented',
			'tenant_id'         => $data['tenant_id'] ?? null,
			'guest_tenant_name' => $data['guest_tenant_name'] ?? null,
			'rental_start_date' => $data['start_date']
		);
		$wpdb->update( "{$wpdb->prefix}sukna_rooms", $room_data, array('id' => $data['room_id']) );

		return $contract_id;
	}

	private static function generate_installments( $contract_id, $total_value, $count, $start_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_payments';

		// Get property installments setting
		$room = $wpdb->get_row($wpdb->prepare("SELECT property_id FROM {$wpdb->prefix}sukna_rooms WHERE id = (SELECT room_id FROM {$wpdb->prefix}sukna_contracts WHERE id = %d)", $contract_id));
		$property = self::get_property($room->property_id);
		$per_year = $property->installments_per_year ?: 4;

		$total_installments = intval( $count );
		if ( $total_installments <= 0 ) return;

		$amount_per_installment = $total_value / $total_installments;

		// Calculate step based on installments per year
		$months_step = round(12 / $per_year);

		for ( $i = 0; $i < $total_installments; $i++ ) {
			$due_date = date( 'Y-m-d', strtotime( "+ " . ($i * $months_step) . " months", strtotime( $start_date ) ) );
			$wpdb->insert( $table, array(
				'contract_id' => $contract_id,
				'amount'      => $amount_per_installment,
				'due_date'    => $due_date,
				'status'      => ($i === 0) ? 'paid' : 'pending',
				'payment_date' => ($i === 0) ? current_time('mysql') : null
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

		$property = self::get_property($property_id);
		if ( ! $property ) return array('income' => 0, 'expenses' => 0, 'net' => 0, 'roi' => 0, 'monthly_income' => 0, 'total_invested' => 0);

		// Total Investment (Sum of investor contributions)
		$total_invested = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_investments WHERE property_id = %d", $property_id ) ) ?: 0;

		// Monthly Income (Sum of monthly rents of occupied rooms)
		$monthly_income = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(rental_price) FROM {$wpdb->prefix}sukna_rooms WHERE property_id = %d AND status = 'rented'", $property_id ) ) ?: 0;

		// Total Income from all rented rooms of this property (Historical Paid)
		$income = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM(p.amount)
			FROM {$wpdb->prefix}sukna_payments p
			JOIN {$wpdb->prefix}sukna_contracts c ON p.contract_id = c.id
			JOIN {$wpdb->prefix}sukna_rooms r ON c.room_id = r.id
			WHERE r.property_id = %d AND p.status = 'paid'
		", $property_id ) ) ?: 0;

		// Total Expenses recorded
		$expenses = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_expenses WHERE property_id = %d", $property_id ) ) ?: 0;

		// Monthly Expenses (Current Month)
		$current_month_start = date('Y-m-01 00:00:00');
		$monthly_expenses = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_expenses WHERE property_id = %d AND expense_date >= %s", $property_id, $current_month_start ) ) ?: 0;

		// Total Setup/Initial Cost
		$initial_setup_cost = floatval($property->total_setup_cost) + floatval($property->gov_fees);

		// Total Project cost (Formula for ownership)
		$total_project_cost = floatval($property->base_value) + $initial_setup_cost;

		// Total Operational Costs = Expenses recorded
		$total_operational_costs = $expenses;

		$net = $income - ($total_operational_costs + $initial_setup_cost);

		// ROI calculation
		$roi = 0;
		if ( $total_project_cost > 0 ) {
			$roi = ($net / $total_project_cost) * 100;
		}

		// Initial funding check (25% of total project cost)
		$funding_threshold = $total_project_cost * 0.25;
		$is_activatable = ($total_invested >= $funding_threshold);
		$funding_completion = ($total_project_cost > 0) ? ($total_invested / $total_project_cost) * 100 : 0;

		// Forced Live Operational Mode
		$mode = 'live';

		$monthly_gross = $monthly_income;
		$monthly_net = $monthly_gross - $monthly_expenses;

		$active_contracts = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sukna_contracts c JOIN {$wpdb->prefix}sukna_rooms r ON c.room_id = r.id WHERE r.property_id = %d AND c.status = 'active'", $property_id)) ?: 0;
		$expired_contracts = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sukna_contracts c JOIN {$wpdb->prefix}sukna_rooms r ON c.room_id = r.id WHERE r.property_id = %d AND c.status = 'expired'", $property_id)) ?: 0;

		$avg_rent = $wpdb->get_var($wpdb->prepare("SELECT AVG(rental_price) FROM {$wpdb->prefix}sukna_rooms WHERE property_id = %d AND rental_price > 0", $property_id)) ?: 0;
		$op_margin = ($monthly_gross > 0) ? ($monthly_net / $monthly_gross) * 100 : 0;

		return array(
			'mode'                     => $mode,
			'total_project_cost'       => $total_project_cost,
			'initial_setup_cost'       => $initial_setup_cost,
			'income'                   => $income,
			'expenses'                 => $expenses,
			'monthly_expenses'         => $monthly_expenses,
			'monthly_net'              => $monthly_net,
			'net'                      => $net,
			'roi'                      => round($roi, 2),
			'monthly_income'           => $monthly_income, // Live monthly
			'total_invested'           => $total_invested,
			'is_activatable'           => $is_activatable,
			'funding_completion'       => round($funding_completion, 1),
			'active_contracts'         => $active_contracts,
			'expired_contracts'        => $expired_contracts,
			'avg_rent'                 => round($avg_rent, 2),
			'op_margin'                => round($op_margin, 2)
		);
	}
}
