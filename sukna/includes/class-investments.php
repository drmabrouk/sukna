<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Investments {

	public static function add_investment( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_investments';
		$wpdb->insert( $table, $data );
		$investment_id = $wpdb->insert_id;

		// Record transaction for the investor's wallet
		self::record_transaction( $data['investor_id'], $data['amount'], 'investment', sprintf( __('استثمار في عقار رقم #%d', 'sukna'), $data['property_id'] ) );

		return $investment_id;
	}

	public static function get_property_investments( $property_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_investments';
		return $wpdb->get_results( $wpdb->prepare( "SELECT i.*, s.name as investor_name FROM $table i JOIN {$wpdb->prefix}sukna_staff s ON i.investor_id = s.id WHERE i.property_id = %d", $property_id ) );
	}

	public static function get_investor_properties( $investor_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT p.*, SUM(i.amount) as my_contribution
			FROM {$wpdb->prefix}sukna_properties p
			JOIN {$wpdb->prefix}sukna_investments i ON p.id = i.property_id
			WHERE i.investor_id = %d
			GROUP BY p.id
		", $investor_id ) );
	}

	public static function get_investor_performance( $investor_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_investments';

		$total_invested = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM $table WHERE investor_id = %d", $investor_id ) ) ?: 0;

		return array(
			'total_invested' => $total_invested,
			'property_count' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT property_id) FROM $table WHERE investor_id = %d", $investor_id ) ) ?: 0,
		);
	}

	public static function get_wallet_balance( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_wallets';
		$balance = $wpdb->get_var( $wpdb->prepare( "SELECT balance FROM $table WHERE user_id = %d", $user_id ) );

		if ( $balance === null ) {
			$wpdb->insert( $table, array( 'user_id' => $user_id, 'balance' => 0 ) );
			return 0;
		}

		return $balance;
	}

	public static function record_transaction( $user_id, $amount, $type, $description = '' ) {
		global $wpdb;
		$wpdb->insert( "{$wpdb->prefix}sukna_transactions", array(
			'user_id' => $user_id,
			'amount' => $amount,
			'type' => $type,
			'description' => $description
		) );

		// Update wallet balance
		$current_balance = self::get_wallet_balance($user_id);
		$new_balance = ($type === 'payout' || $type === 'investment') ? $current_balance - $amount : $current_balance + $amount;

		$wpdb->update( "{$wpdb->prefix}sukna_wallets", array('balance' => $new_balance), array('user_id' => $user_id) );
	}

	public static function get_transactions( $user_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sukna_transactions WHERE user_id = %d ORDER BY transaction_date DESC LIMIT 50", $user_id ) );
	}

	public static function get_system_wide_stats() {
		global $wpdb;

		$total_invested = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_investments" ) ?: 0;
		$total_expenses = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_expenses" ) ?: 0;

		// Sum of all real paid payments
		$total_revenue  = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_payments WHERE status = 'paid'" ) ?: 0;

		// Room stats
		$total_rooms = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sukna_rooms" ) ?: 0;
		$occupied_rooms = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sukna_rooms WHERE status = 'rented'" ) ?: 0;

		// Contract stats
		$active_contracts = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sukna_contracts WHERE status = 'active'" ) ?: 0;
		$expired_contracts = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sukna_contracts WHERE status = 'expired'" ) ?: 0;

		// Historical comparison (Current vs Previous Month)
		$prev_month_start = date('Y-m-01 00:00:00', strtotime('last month'));
		$prev_month_end   = date('Y-m-t 23:59:59', strtotime('last month'));
		$prev_revenue = $wpdb->get_var( $wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}sukna_payments WHERE status = 'paid' AND payment_date BETWEEN %s AND %s", $prev_month_start, $prev_month_end) ) ?: 0;
		$growth_rate = ($prev_revenue > 0) ? (($total_revenue - $prev_revenue) / $prev_revenue) * 100 : 0;

		// Top/Bottom performing properties
		$all_props = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}sukna_properties");
		foreach($all_props as &$p) {
			$p->perf = Sukna_Properties::get_property_performance($p->id);
		}
		usort($all_props, function($a, $b) { return $b->perf['roi'] <=> $a->perf['roi']; });

		$top_props = array_slice($all_props, 0, 3);
		$bottom_props = array_reverse(array_slice($all_props, -3));

		return array(
			'total_invested' => $total_invested,
			'total_revenue'  => $total_revenue,
			'total_expenses' => $total_expenses,
			'net_profit'     => $total_revenue - $total_expenses,
			'investor_count' => $wpdb->get_var( "SELECT COUNT(DISTINCT id) FROM {$wpdb->prefix}sukna_staff WHERE role = 'investor'" ) ?: 0,
			'total_rooms'    => $total_rooms,
			'occupied_rooms' => $occupied_rooms,
			'occupancy_rate' => $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0,
			'active_contracts' => $active_contracts,
			'expired_contracts' => $expired_contracts,
			'growth_rate'      => round($growth_rate, 2),
			'top_props'        => $top_props,
			'bottom_props'     => $bottom_props
		);
	}

	public static function distribute_revenue( $property_id, $net_profit ) {
		global $wpdb;

		$property = Sukna_Properties::get_property($property_id);
		if ( ! $property ) return;

		$investments = self::get_property_investments($property_id);

		// Unified formula: base_value + total_setup_cost + gov_fees
		$total_project_cost = floatval($property->base_value) + floatval($property->total_setup_cost) + floatval($property->gov_fees);
		if ( $total_project_cost <= 0 ) return;

		$total_investor_contribution = 0;

		// Investor Shares (Proportional to Total Project Cost)
		foreach ($investments as $inv) {
			$share_percent = ($inv->amount / $total_project_cost);
			$investor_share = $net_profit * $share_percent;
			$total_investor_contribution += $inv->amount;

			self::record_transaction( $inv->investor_id, $investor_share, 'dividend', sprintf(__('عائد أرباح عقار #%d (نسبة %s%%)', 'sukna'), $property_id, round($share_percent * 100, 2)));
		}

		// Owner Share (Remaining balance of the project cost)
		$owner_contribution = $total_project_cost - $total_investor_contribution;
		if ( $owner_contribution < 0 ) $owner_contribution = 0;

		$owner_share_percent = ($owner_contribution / $total_project_cost);
		$owner_profit = $net_profit * $owner_share_percent;

		self::record_transaction($property->owner_id, $owner_profit, 'dividend', sprintf(__('عائد أرباح عقار #%d (نسبة المالك %s%%)', 'sukna'), $property_id, round($owner_share_percent * 100, 2)));
	}
}
