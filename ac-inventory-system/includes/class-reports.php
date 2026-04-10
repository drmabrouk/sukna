<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Reports {

	public static function get_daily_sales() {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_sales';
		return $wpdb->get_results( "SELECT DATE(sale_date) as date, SUM(total_price) as total, COUNT(*) as count FROM $table GROUP BY DATE(sale_date) ORDER BY date DESC LIMIT 7" );
	}

	public static function get_weekly_sales() {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_sales';
		return $wpdb->get_results( "SELECT YEARWEEK(sale_date) as week, SUM(total_price) as total, COUNT(*) as count FROM $table GROUP BY YEARWEEK(sale_date) ORDER BY week DESC LIMIT 4" );
	}

	public static function get_monthly_sales() {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_sales';
		return $wpdb->get_results( "SELECT DATE_FORMAT(sale_date, '%Y-%m') as month, SUM(total_price) as total, COUNT(*) as count FROM $table GROUP BY DATE_FORMAT(sale_date, '%Y-%m') ORDER BY month DESC LIMIT 6" );
	}

	public static function get_stock_overview() {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_products';
		return $wpdb->get_results( "SELECT name, category, stock_quantity FROM $table WHERE stock_quantity < 10 ORDER BY stock_quantity ASC" );
	}

	public static function export_sales_csv() {
		if ( ! isset( $_GET['ac_export'] ) || $_GET['ac_export'] !== 'sales' ) {
			return;
		}

		if ( ! AC_IS_Auth::is_admin() ) {
			return;
		}

		if ( ! isset( $_GET['ac_nonce'] ) || ! wp_verify_nonce( $_GET['ac_nonce'], 'ac_is_export' ) ) {
			return;
		}

		global $wpdb;
		$table_sales = $wpdb->prefix . 'ac_is_sales';
		$table_products = $wpdb->prefix . 'ac_is_products';
		$sales = $wpdb->get_results("
			SELECT s.id as sale_id, s.sale_date, p.name as product_name, s.serial_number, s.quantity, s.total_price
			FROM $table_sales s
			JOIN $table_products p ON s.product_id = p.id
			ORDER BY s.sale_date DESC
		");

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=sales_report_' . date('Y-m-d') . '.csv');
		$output = fopen('php://output', 'w');
		// Output UTF-8 BOM for Excel Arabic support
		fputs($output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		fputcsv($output, array('رقم الفاتورة', 'التاريخ', 'اسم المنتج', 'السيريال', 'الكمية', 'الإجمالي'));
		foreach ($sales as $sale) {
			fputcsv($output, (array)$sale);
		}
		fclose($output);
		exit;
	}
}

add_action( 'init', array( 'AC_IS_Reports', 'export_sales_csv' ) );
