<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Investments {

	public static function add_investment( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_investments';

		// Enforce strict investment limits
		$property = Sukna_Properties::get_property($data['property_id']);
		$perf = Sukna_Properties::get_property_performance($data['property_id']);
		$remaining = $perf['total_project_cost'] - $perf['total_invested'];

		if ( $data['amount'] > $remaining ) {
			return new WP_Error('overfunding', sprintf(__('المبلغ يتجاوز التمويل المطلوب. المتبقي: %s AED', 'sukna'), number_format($remaining)));
		}

		$wpdb->insert( $table, $data );
		$investment_id = $wpdb->insert_id;

		// Record transaction for the investor's wallet
		if ( $data['amount'] > 0 ) {
			self::record_transaction( $data['investor_id'], $data['amount'], 'investment', sprintf( __('مساهمة في مشروع: %d', 'sukna'), $data['property_id'] ) );
		}

		return $investment_id;
	}

	public static function get_property_investments( $property_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_investments';
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT i.*, s.name as investor_name, s.role as investor_role
			FROM $table i
			JOIN {$wpdb->prefix}sukna_staff s ON i.investor_id = s.id
			WHERE i.property_id = %d
		", $property_id ) );
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

	public static function get_wallet( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sukna_wallets';
		$wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id ) );

		if ( ! $wallet ) {
			$wpdb->insert( $table, array( 'user_id' => $user_id, 'balance' => 0, 'available_balance' => 0, 'pending_balance' => 0, 'reserved_balance' => 0 ) );
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id ) );
		}

		return $wallet;
	}

	public static function record_transaction( $user_id, $amount, $type, $description = '' ) {
		global $wpdb;
		$wpdb->insert( "{$wpdb->prefix}sukna_transactions", array(
			'user_id' => $user_id,
			'amount' => $amount,
			'type' => $type,
			'description' => $description
		) );

		// Update wallet balances
		$wallet = self::get_wallet($user_id);
		$update_data = array();

		if ($type === 'investment') {
			$update_data['balance'] = $wallet->balance - $amount;
		} elseif ($type === 'room_revenue') {
			$update_data['balance'] = $wallet->balance + $amount;
			$update_data['pending_balance'] = $wallet->pending_balance + $amount;
		} elseif ($type === 'expense' || $type === 'lease_obligation') {
			$update_data['balance'] = $wallet->balance - $amount;
			$update_data['pending_balance'] = $wallet->pending_balance - $amount;
		} elseif ($type === 'payout') {
			$update_data['balance'] = $wallet->balance - $amount;
			$update_data['available_balance'] = $wallet->available_balance - $amount;
		} elseif ($type === 'dividend') {
			$update_data['balance'] = $wallet->balance + $amount;
			$update_data['pending_balance'] = $wallet->pending_balance + $amount;
		} else {
			$update_data['balance'] = $wallet->balance + $amount;
		}

		$wpdb->update( "{$wpdb->prefix}sukna_wallets", $update_data, array('user_id' => $user_id) );
	}

	public static function get_wallet_balance( $user_id ) {
		$wallet = self::get_wallet($user_id);
		return $wallet->balance;
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

	public static function distribute_proportional_amount( $property_id, $amount, $type, $description = '' ) {
		global $wpdb;
		$property = Sukna_Properties::get_property($property_id);
		if ( ! $property ) return;

		$investments = self::get_property_investments($property_id);
		$total_project_cost = floatval($property->base_value) + floatval($property->total_setup_cost) + floatval($property->gov_fees);
		if ( $total_project_cost <= 0 ) return;

		$total_investor_contribution = 0;
		foreach ($investments as $inv) {
			$share_percent = ($inv->amount / $total_project_cost);
			$investor_share = $amount * $share_percent;
			$total_investor_contribution += $inv->amount;

			self::record_transaction( $inv->investor_id, abs($investor_share), $type, $description . sprintf(' (%s%%)', round($share_percent * 100, 2)) );
		}

		// Owner Share
		$owner_contribution = $total_project_cost - $total_investor_contribution;
		if ( $owner_contribution > 0 ) {
			$owner_share_percent = ($owner_contribution / $total_project_cost);
			$owner_share = $amount * $owner_share_percent;
			self::record_transaction( $property->owner_id, abs($owner_share), $type, $description . sprintf(' (%s%% %s)', round($owner_share_percent * 100, 2), __('حصة المالك', 'sukna')) );
		}
	}

	public static function distribute_revenue( $property_id, $net_profit ) {
		self::distribute_proportional_amount($property_id, $net_profit, 'dividend', sprintf(__('توزيع أرباح يدوية - عقار #%d', 'sukna'), $property_id));
	}

	public static function release_monthly_profits() {
		global $wpdb;

		// 1. Process Master Lease Obligations for all Leased Properties
		$leased_properties = $wpdb->get_results("SELECT id, name, owner_id, base_value, contract_duration FROM {$wpdb->prefix}sukna_properties WHERE property_type = 'leased'");
		foreach ($leased_properties as $p) {
			if ($p->contract_duration > 0) {
				$monthly_obligation = floatval($p->base_value) / (intval($p->contract_duration) * 12);
				if ($monthly_obligation > 0) {
					// Proportional deduction from all investors for this property
					self::distribute_proportional_amount(
						$p->id,
						$monthly_obligation,
						'lease_obligation',
						sprintf(__('استقطاع التزام عقد الإيجار الشهري - %s', 'sukna'), $p->name)
					);
				}
			}
		}

		// 2. Release pending to available for all users
		$table = $wpdb->prefix . 'sukna_wallets';
		$wpdb->query("UPDATE $table SET available_balance = available_balance + pending_balance, pending_balance = 0");

		Sukna_Audit::log('profit_release', "Processed monthly obligations and released pending balances to available for withdrawal.");
	}
}
