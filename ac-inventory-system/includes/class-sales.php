<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Sales {

	public static function record_sale( $data ) {
		global $wpdb;
		$table_sales = $wpdb->prefix . 'ac_is_sales';
		$table_products = $wpdb->prefix . 'ac_is_products';

		// Start transaction
		$wpdb->query('START TRANSACTION');

		// Insert sale record
		$current_user = AC_IS_Auth::current_user();
		$operator_id  = $current_user ? $current_user->id : 0;

		$sale_data = array(
			'invoice_id'    => $data['invoice_id'],
			'product_id'    => $data['product_id'],
			'serial_number' => $data['serial_number'],
			'quantity'      => $data['quantity'],
			'total_price'   => $data['total_price'],
			'operator_id'   => $operator_id,
			'sale_date'     => current_time('mysql'),
		);

		$inserted = $wpdb->insert( $table_sales, $sale_data );

		if ( ! $inserted ) {
			$wpdb->query('ROLLBACK');
			return false;
		}

		// Update stock
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE $table_products SET stock_quantity = stock_quantity - %d WHERE id = %d AND stock_quantity >= %d",
			$data['quantity'], $data['product_id'], $data['quantity']
		) );

		if ( ! $updated ) {
			$wpdb->query('ROLLBACK');
			return false;
		}

		// Water Filter Auto-Tracking Logic
		$product = $wpdb->get_row($wpdb->prepare("SELECT category, filter_stages FROM $table_products WHERE id = %d", $data['product_id']));
		if ( $product && $product->category === 'filter' && $product->filter_stages > 0 ) {
			$invoice = self::get_invoice($data['invoice_id']);
			if ( $invoice && $invoice->customer_id ) {
				for ( $i = 1; $i <= $product->filter_stages; $i++ ) {
					// Logic: Stage 1 = 3 months, 2-3 = 6 months, others = 12 months (Customizable default)
					$validity = ($i == 1) ? 3 : (($i <= 3) ? 6 : 12);
					$wpdb->insert( $wpdb->prefix . 'ac_is_filter_tracking', array(
						'customer_id'       => $invoice->customer_id,
						'product_id'        => $data['product_id'],
						'invoice_id'        => $data['invoice_id'],
						'stage_number'      => $i,
						'installation_date' => current_time('mysql'),
						'expiry_date'       => date('Y-m-d', strtotime("+$validity months")),
					) );
				}
			}
		}

		$wpdb->query('COMMIT');
		return $wpdb->insert_id;
	}

	public static function get_sale( $id ) {
		global $wpdb;
		$table_sales = $wpdb->prefix . 'ac_is_sales';
		$table_products = $wpdb->prefix . 'ac_is_products';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT s.*, p.name as product_name, p.barcode as product_barcode
			FROM $table_sales s
			JOIN $table_products p ON s.product_id = p.id
			WHERE s.id = %d",
			$id
		) );
	}

	public static function get_invoice( $id ) {
		global $wpdb;
		$table_invoices = $wpdb->prefix . 'ac_is_invoices';
		$table_customers = $wpdb->prefix . 'ac_is_customers';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT i.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.email as customer_email
			FROM $table_invoices i
			LEFT JOIN $table_customers c ON i.customer_id = c.id
			WHERE i.id = %d",
			$id
		) );
	}

	public static function get_invoice_items( $invoice_id ) {
		global $wpdb;
		$table_sales    = $wpdb->prefix . 'ac_is_sales';
		$table_products = $wpdb->prefix . 'ac_is_products';
		$table_brands   = $wpdb->prefix . 'ac_is_brands';

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT s.*, p.name as product_name, p.barcode as product_barcode, b.logo_url as brand_logo
			FROM $table_sales s
			JOIN $table_products p ON s.product_id = p.id
			LEFT JOIN $table_brands b ON p.brand_id = b.id
			WHERE s.invoice_id = %d",
			$invoice_id
		) );
	}

	public static function delete_invoice( $invoice_id ) {
		global $wpdb;
		$table_invoices = $wpdb->prefix . 'ac_is_invoices';
		$table_sales    = $wpdb->prefix . 'ac_is_sales';
		$table_products = $wpdb->prefix . 'ac_is_products';

		// Get items to restore stock
		$items = self::get_invoice_items( $invoice_id );

		$wpdb->query('START TRANSACTION');

		foreach ( $items as $item ) {
			// Restore stock
			$wpdb->query( $wpdb->prepare(
				"UPDATE $table_products SET stock_quantity = stock_quantity + %d WHERE id = %d",
				$item->quantity, $item->product_id
			) );
		}

		// Delete sales records
		$wpdb->delete( $table_sales, array( 'invoice_id' => $invoice_id ) );

		// Delete invoice
		$wpdb->delete( $table_invoices, array( 'id' => $invoice_id ) );

		$wpdb->query('COMMIT');
		return true;
	}
}
