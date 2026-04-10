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
		$total_payouts  = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_transactions WHERE type = 'dividend'" ) ?: 0;
		$total_expenses = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_expenses" ) ?: 0;

		// Sum of all paid payments
		$total_revenue  = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}sukna_payments WHERE status = 'paid'" ) ?: 0;

		return array(
			'total_invested' => $total_invested,
			'total_revenue'  => $total_revenue,
			'total_expenses' => $total_expenses,
			'net_profit'     => $total_revenue - $total_expenses,
			'investor_count' => $wpdb->get_var( "SELECT COUNT(DISTINCT id) FROM {$wpdb->prefix}sukna_staff WHERE role = 'investor'" ) ?: 0,
		);
	}

	public static function distribute_revenue( $property_id, $total_revenue ) {
		global $wpdb;

		$property = Sukna_Properties::get_property($property_id);
		if ( ! $property ) return;

		$investments = self::get_property_investments($property_id);

		$base_value = floatval($property->base_value);
		if ( $base_value <= 0 ) return;

		$total_investor_contribution = 0;

		// Investor Shares (Proportional)
		foreach ($investments as $inv) {
			$share_percent = ($inv->amount / $base_value);
			$investor_share = $total_revenue * $share_percent;
			$total_investor_contribution += $inv->amount;

			self::record_transaction($inv->investor_id, $investor_share, 'dividend', sprintf(__('عائد إيرادات عقار #%d', 'sukna'), $property_id));
		}

		// Owner Share (Remaining profit)
		$owner_share_percent = ($base_value - $total_investor_contribution) / $base_value;
		if ( $owner_share_percent < 0 ) $owner_share_percent = 0;

		$owner_profit = $total_revenue * $owner_share_percent;
		self::record_transaction($property->owner_id, $owner_profit, 'dividend', sprintf(__('عائد إيرادات عقار #%d', 'sukna'), $property_id));
	}
}
