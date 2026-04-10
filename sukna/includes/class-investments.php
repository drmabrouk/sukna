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
}
