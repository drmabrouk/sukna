<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Ajax {

	public function __construct() {
		// Private actions (Logged-in only)
		$private_actions = array(
			'logout', 'add_user', 'save_user', 'delete_user', 'save_settings',
			'undo_activity', 'save_property', 'delete_property', 'save_room',
			'delete_room', 'save_investment', 'get_rooms', 'get_investments',
			'save_contract', 'record_expense', 'distribute_revenue',
			'reset_property_rooms', 'toggle_user_restriction', 'get_setup_items',
			'get_report_html', 'terminate_contract', 'export_data', 'import_data', 'get_invoice_html',
			'get_installments', 'record_payment'
		);

		foreach ( $private_actions as $action ) {
			add_action( 'wp_ajax_sukna_' . $action, array( $this, $action ) );
		}

		// Public actions (Non-logged-in)
		$public_actions = array( 'login', 'register' );
		foreach ( $public_actions as $action ) {
			add_action( 'wp_ajax_sukna_' . $action, array( $this, $action ) );
			add_action( 'wp_ajax_nopriv_sukna_' . $action, array( $this, $action ) );
		}
	}

	public function login() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );

		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$password = $_POST['password'] ?? '';

		$result = Sukna_Auth::login( $phone, $password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		} elseif ( $result ) {
			Sukna_Audit::log('login', "User with phone $phone logged in");
			wp_send_json_success();
		} else {
			Sukna_Audit::log('failed_login', "Failed login attempt for phone $phone");
			wp_send_json_error( array( 'message' => __( 'بيانات الدخول غير صحيحة.', 'sukna' ) ) );
		}
	}

	public function register() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );

		$data = array(
			'first_name' => sanitize_text_field( $_POST['first_name'] ),
			'last_name'  => sanitize_text_field( $_POST['last_name'] ),
			'phone'      => sanitize_text_field( $_POST['phone'] ),
			'email'      => sanitize_email( $_POST['email'] ),
			'password'   => $_POST['password'],
		);

		if ( empty($data['first_name']) || empty($data['last_name']) || empty($data['phone']) || empty($data['password']) ) {
			wp_send_json_error( array( 'message' => __( 'يرجى ملء جميع الحقول المطلوبة.', 'sukna' ) ) );
		}

		if ( ! preg_match('/^\+(20|971|966|965|974|973|968)[0-9]{7,12}$/', $data['phone']) ) {
			wp_send_json_error( array( 'message' => __( 'تنسيق رقم الهاتف غير صالح لهذه الدولة.', 'sukna' ) ) );
		}

		if ( strlen($data['password']) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'كلمة المرور يجب أن لا تقل عن 8 أحرف.', 'sukna' ) ) );
		}

		$result = Sukna_Auth::register_user( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		Sukna_Audit::log('registration', "New user registered: {$data['phone']}");
		wp_send_json_success();
	}

	public function logout() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		Sukna_Auth::logout();
		wp_send_json_success();
	}

	public function add_user() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'sukna_staff';
		$phone = sanitize_text_field( $_POST['phone'] );

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE phone = %s", $phone ) );
		if ( $exists ) wp_send_json_error( 'Phone already registered' );

		$data = array(
			'username' => $phone,
			'phone'    => $phone,
			'password' => password_hash( $_POST['password'], PASSWORD_DEFAULT ),
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),
		);

		$wpdb->insert( $table, $data );
		Sukna_Audit::log('add_user', "User $phone added by admin");
		wp_send_json_success();
	}

	public function save_user() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$phone = sanitize_text_field( $_POST['phone'] );

		$data = array(
			'username' => $phone,
			'phone'    => $phone,
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),
		);

		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = password_hash( $_POST['password'], PASSWORD_DEFAULT );
		}

		$wpdb->update( $wpdb->prefix . 'sukna_staff', $data, array( 'id' => $id ) );
		Sukna_Audit::log('edit_user', "User $phone updated by admin");
		wp_send_json_success();
	}

	public function delete_user() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sukna_staff WHERE id = %d", $id ) );
		if ( $user && ($user->username === 'admin' || $user->phone === '1234567890')) wp_send_json_error( 'Cannot delete admin' );

		if ( $user ) {
			Sukna_Audit::log( 'delete_user', sprintf(__('حذف المستخدم: %s', 'sukna'), $user->name), $user );
			$wpdb->delete( $wpdb->prefix . 'sukna_staff', array( 'id' => $id ) );
			wp_send_json_success();
		} else {
			wp_send_json_error( 'User not found' );
		}
	}

	public function toggle_user_restriction() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sukna_staff WHERE id = %d", $id ) );
		if ( ! $user ) wp_send_json_error( 'User not found' );
		if ( $user->username === 'admin' || $user->phone === '1234567890' ) wp_send_json_error( 'Cannot restrict admin' );

		$new_status = $user->is_restricted ? 0 : 1;
		$wpdb->update( "{$wpdb->prefix}sukna_staff", array( 'is_restricted' => $new_status ), array( 'id' => $id ) );

		$action = $new_status ? __('تقييد', 'sukna') : __('إلغاء تقييد', 'sukna');
		Sukna_Audit::log( 'toggle_restriction', sprintf(__('%s حساب المستخدم: %s', 'sukna'), $action, $user->name) );

		wp_send_json_success();
	}

	public function terminate_contract() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$room_id = intval( $_POST['room_id'] );

		// Update room to available
		$wpdb->update( "{$wpdb->prefix}sukna_rooms", array(
			'status' => 'available',
			'tenant_id' => null,
			'guest_tenant_name' => null,
			'rental_start_date' => null
		), array( 'id' => $room_id ) );

		// Update active contracts for this room to expired/terminated
		$wpdb->update( "{$wpdb->prefix}sukna_contracts", array('status' => 'expired'), array('room_id' => $room_id, 'status' => 'active') );

		$room = $wpdb->get_row($wpdb->prepare("SELECT room_number FROM {$wpdb->prefix}sukna_rooms WHERE id = %d", $room_id));
		Sukna_Audit::log('terminate_contract', sprintf(__('إنهاء التعاقد وإخلاء الوحدة رقم: %s', 'sukna'), $room->room_number ?? $room_id));

		wp_send_json_success();
	}

	public function save_property() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] ?? 0 );
		$current_user = Sukna_Auth::current_user();

		// Ownership check
		if ( $id && ! Sukna_Auth::is_admin() ) {
			$p = Sukna_Properties::get_property($id);
			if ( !$p || $p->owner_id != $current_user->id ) wp_send_json_error( 'Access Denied' );
		}
		$data = array(
			'name'                     => sanitize_text_field( $_POST['name'] ),
			'address'                  => sanitize_text_field( $_POST['address'] ),
			'owner_id'                 => intval( $_POST['owner_id'] ),
			'country'                  => sanitize_text_field( $_POST['country'] ),
			'city'                     => sanitize_text_field( $_POST['city'] ),
			'state_emirate'            => sanitize_text_field( $_POST['state_emirate'] ),
			'property_type'            => sanitize_text_field( $_POST['property_type'] ),
			'property_subtype'         => sanitize_text_field( $_POST['property_subtype'] ),
			'apartment_number'         => sanitize_text_field( $_POST['apartment_number'] ),
			'floor_number'             => sanitize_text_field( $_POST['floor_number'] ),
			'total_rooms'              => intval( $_POST['total_rooms'] ),
			'annual_rent'              => floatval( $_POST['annual_rent'] ),
			'monthly_fixed_opex'       => floatval( $_POST['monthly_fixed_opex'] ),
			'additional_setup_cost'    => floatval( $_POST['additional_setup_cost'] ),
			'projected_rent_per_room'  => floatval( $_POST['projected_rent_per_room'] ),
			'contract_start_date'      => sanitize_text_field( $_POST['contract_start_date'] ) ?: current_time('Y-m-d'),
			'investment_start_date'    => sanitize_text_field( $_POST['investment_start_date'] ) ?: current_time('Y-m-d'),
			'contract_duration'        => intval( $_POST['contract_duration'] ),
			'installments_per_year'    => intval( $_POST['installments_per_year'] ?? 4 ),
			'base_value'               => floatval( $_POST['base_value'] ),
			'gov_fees'                 => floatval( $_POST['gov_fees'] ),
		);

		// Handle setup items
		$setup_items = array();
		if ( ! empty($_POST['setup_item_names']) && is_array($_POST['setup_item_names']) ) {
			foreach ( $_POST['setup_item_names'] as $idx => $name ) {
				if ( empty($name) ) continue;
				$setup_items[] = array(
					'name' => sanitize_text_field($name),
					'cost' => floatval($_POST['setup_item_costs'][$idx] ?? 0)
				);
			}
		}
		$data['setup_items'] = $setup_items;

		if ( $id ) {
			Sukna_Properties::update_property( $id, $data );
			Sukna_Audit::log('edit_property', sprintf(__('تعديل بيانات العقار: %s', 'sukna'), $data['name']));
		} else {
			$data['initiator_id'] = Sukna_Auth::current_user()->id;
			Sukna_Properties::add_property( $data );
			Sukna_Audit::log('add_property', sprintf(__('إضافة عقار جديد: %s', 'sukna'), $data['name']));
		}
		wp_send_json_success();
	}

	public function delete_property() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		$p = Sukna_Properties::get_property($id);
		if ( ! $p ) wp_send_json_error( 'Not found' );

		Sukna_Audit::log('delete_property', sprintf(__('حذف العقار: %s', 'sukna'), $p->name), $p);
		Sukna_Properties::delete_property( $id );

		wp_send_json_success();
	}

	public function get_rooms() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$property_id = intval( $_POST['property_id'] );

		// Authorization check
		if ( ! Sukna_Auth::is_admin() ) {
			$current_user = Sukna_Auth::current_user();
			$p = Sukna_Properties::get_property($property_id);
			if ( !$p || ($p->owner_id != $current_user->id && !Sukna_Auth::is_investor()) ) {
				// Investors can see rooms too, but let's be strict for now or allow if they are investor
				$is_investor = $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("SELECT id FROM {$GLOBALS['wpdb']->prefix}sukna_investments WHERE property_id = %d AND investor_id = %d", $property_id, $current_user->id));
				if ( $p->owner_id != $current_user->id && !$is_investor ) wp_send_json_error( 'Access Denied' );
			}
		}

		$rooms = Sukna_Properties::get_rooms($property_id);
		wp_send_json_success($rooms);
	}

	public function save_contract() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$room_id = intval( $_POST['room_id'] );

		// Ownership check via room
		if ( ! Sukna_Auth::is_admin() ) {
			$current_user = Sukna_Auth::current_user();
			$room = $GLOBALS['wpdb']->get_row($GLOBALS['wpdb']->prepare("SELECT p.owner_id FROM {$GLOBALS['wpdb']->prefix}sukna_rooms r JOIN {$GLOBALS['wpdb']->prefix}sukna_properties p ON r.property_id = p.id WHERE r.id = %d", $room_id));
			if ( !$room || $room->owner_id != $current_user->id ) wp_send_json_error( 'Access Denied' );
		}

		$duration_years = intval( $_POST['duration_years'] );
		$start_date = sanitize_text_field( $_POST['start_date'] ) ?: current_time('Y-m-d');
		$data = array(
			'room_id'           => $room_id,
			'tenant_id'         => intval( $_POST['tenant_id'] ?: 0 ) ?: null,
			'guest_tenant_name' => sanitize_text_field( $_POST['guest_tenant_name'] ?? '' ),
			'start_date'        => $start_date,
			'duration_years'    => $duration_years,
			'total_value'       => floatval( $_POST['total_value'] ),
			'installment_count' => intval( $_POST['installment_count'] ?: ($duration_years * 4) ),
		);

		Sukna_Properties::save_contract($data);

		$tenant_name = $data['guest_tenant_name'] ?: 'ID: ' . $data['tenant_id'];
		Sukna_Audit::log('save_contract', sprintf(__('تفعيل عقد إيجار جديد للمستأجر: %s', 'sukna'), $tenant_name));

		wp_send_json_success();
	}

	public function save_room() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] ?? 0 );
		$property_id = intval( $_POST['property_id'] );

		// Ownership check
		if ( ! Sukna_Auth::is_admin() ) {
			$current_user = Sukna_Auth::current_user();
			$p = Sukna_Properties::get_property($property_id);
			if ( !$p || $p->owner_id != $current_user->id ) wp_send_json_error( 'Access Denied' );
		}

		$data = array(
			'property_id'       => $property_id,
			'room_number'       => sanitize_text_field( $_POST['room_number'] ),
			'rental_price'      => floatval( $_POST['rental_price'] ),
			'status'            => sanitize_text_field( $_POST['status'] ),
			'tenant_id'         => intval( $_POST['tenant_id'] ?: 0 ) ?: null,
			'guest_tenant_name' => sanitize_text_field( $_POST['guest_tenant_name'] ?? '' ),
			'rental_start_date' => sanitize_text_field( $_POST['rental_start_date'] ) ?: current_time('Y-m-d'),
			'payment_frequency' => sanitize_text_field( $_POST['payment_frequency'] ),
		);

		if ( $id ) {
			Sukna_Properties::update_room( $id, $data );
			Sukna_Audit::log('edit_room', sprintf(__('تعديل بيانات الوحدة: %s', 'sukna'), $data['room_number']));
		} else {
			Sukna_Properties::add_room( $data );
			Sukna_Audit::log('add_room', sprintf(__('إضافة وحدة جديدة: %s', 'sukna'), $data['room_number']));
		}
		wp_send_json_success();
	}

	public function record_expense() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$property_id = intval( $_POST['property_id'] );

		// Ownership check
		if ( ! Sukna_Auth::is_admin() ) {
			$current_user = Sukna_Auth::current_user();
			$p = Sukna_Properties::get_property($property_id);
			if ( !$p || $p->owner_id != $current_user->id ) wp_send_json_error( 'Access Denied' );
		}

		$data = array(
			'property_id' => $property_id,
			'category'    => sanitize_text_field( $_POST['category'] ),
			'amount'      => floatval( $_POST['amount'] ),
		);

		Sukna_Properties::record_expense($data);
		$p = Sukna_Properties::get_property($data['property_id']);

		// Proportional Deduction for Expense
		Sukna_Investments::distribute_proportional_amount(
			$data['property_id'],
			$data['amount'],
			'expense',
			sprintf(__('مصروف تشغيلي - %s (%s)', 'sukna'), $p->name, $data['category'])
		);

		Sukna_Audit::log('record_expense', sprintf(__('تسجيل مصروفات للعقار %s: %s AED (%s)', 'sukna'), $p->name ?? $data['property_id'], $data['amount'], $data['category']));

		wp_send_json_success();
	}

	public function reset_property_rooms() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );

		// Ownership check
		if ( ! Sukna_Auth::is_admin() ) {
			$current_user = Sukna_Auth::current_user();
			$p = Sukna_Properties::get_property($id);
			if ( !$p || $p->owner_id != $current_user->id ) wp_send_json_error( 'Access Denied' );
		}

		Sukna_Properties::reset_rooms_occupancy($id);
		$p = Sukna_Properties::get_property($id);
		Sukna_Audit::log('reset_rooms', sprintf(__('تصفير كافة وحدات العقار: %s', 'sukna'), $p->name ?? $id));

		wp_send_json_success();
	}

	public function distribute_revenue() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$property_id = intval( $_POST['id'] );
		$net_profit  = floatval( $_POST['net_profit'] );

		Sukna_Investments::distribute_revenue($property_id, $net_profit);
		$p = Sukna_Properties::get_property($property_id);
		Sukna_Audit::log('distribute_revenue', sprintf(__('توزيع أرباح العقار %s بقيمة %s AED', 'sukna'), $p->name ?? $property_id, $net_profit));

		wp_send_json_success();
	}

	public function get_investments() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$property_id = intval( $_POST['property_id'] );
		$investments = Sukna_Investments::get_property_investments($property_id);
		wp_send_json_success($investments);
	}

	public function save_investment() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$data = array(
			'investor_id' => intval( $_POST['investor_id'] ),
			'property_id' => intval( $_POST['property_id'] ),
			'amount'      => floatval( $_POST['amount'] ),
			'installments_paid' => intval( $_POST['installments_paid'] ?? 1 )
		);

		$result = Sukna_Investments::add_investment( $data );
		if ( is_wp_error($result) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$p = Sukna_Properties::get_property($data['property_id']);
		$u = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}sukna_staff WHERE id = %d", $data['investor_id']));
		Sukna_Audit::log('add_investment', sprintf(__('إضافة مساهمة من %s للعقار %s بقيمة %s AED', 'sukna'), $u->name ?? $data['investor_id'], $p->name ?? $data['property_id'], $data['amount']));

		wp_send_json_success();
	}

	public function save_settings() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'sukna_settings';

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'sukna_' ) === false && $key !== 'action' && $key !== 'nonce' ) {
				$wpdb->replace( $table, array(
					'setting_key'   => sanitize_key( $key ),
					'setting_value' => sanitize_text_field( $value )
				) );
			}
		}

		wp_send_json_success();
	}


	public function get_setup_items() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$id = intval( $_POST['id'] );
		$items = Sukna_Properties::get_setup_items($id);
		wp_send_json_success($items);
	}

	public function get_invoice_html() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['investment_id'] );
		global $wpdb;
		$inv = $wpdb->get_row($wpdb->prepare("
			SELECT i.*, s.name as investor_name, p.name as property_name, p.base_value, p.total_setup_cost, p.gov_fees
			FROM {$wpdb->prefix}sukna_investments i
			JOIN {$wpdb->prefix}sukna_staff s ON i.investor_id = s.id
			JOIN {$wpdb->prefix}sukna_properties p ON i.property_id = p.id
			WHERE i.id = %d
		", $id));

		if ( !$inv ) wp_send_json_error( 'Not found' );

		$total_project_cost = $inv->base_value + $inv->total_setup_cost + $inv->gov_fees;
		$share_pct = $total_project_cost > 0 ? ($inv->amount / $total_project_cost) * 100 : 0;

		ob_start();
		?>
		<div id="sukna-invoice-content" style="direction: rtl; font-family: 'Rubik', sans-serif; padding: 40px; background: #fff; color: #000; border: 1px solid #eee;">
			<div style="display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
				<div><h1 style="margin:0;"><?php _e('فاتورة مساهمة استثمارية', 'sukna'); ?></h1></div>
				<div style="text-align: left;"><strong>SUKNA System</strong></div>
			</div>

			<table style="width: 100%; margin-bottom: 30px;">
				<tr>
					<td><strong><?php _e('اسم المستثمر:', 'sukna'); ?></strong></td>
					<td><?php echo esc_html($inv->investor_name); ?></td>
					<td><strong><?php _e('رقم المرجع:', 'sukna'); ?></strong></td>
					<td>#INV-<?php echo $inv->id; ?></td>
				</tr>
				<tr>
					<td><strong><?php _e('اسم العقار:', 'sukna'); ?></strong></td>
					<td><?php echo esc_html($inv->property_name); ?></td>
					<td><strong><?php _e('التاريخ:', 'sukna'); ?></strong></td>
					<td><?php echo date('Y-m-d', strtotime($inv->investment_date)); ?></td>
				</tr>
			</table>

			<div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
				<h3 style="margin-top: 0;"><?php _e('تفاصيل المساهمة', 'sukna'); ?></h3>
				<table style="width: 100%;">
					<tr style="font-size: 1.2rem;">
						<td style="padding: 10px 0;"><?php _e('إجمالي مبلغ المساهمة:', 'sukna'); ?></td>
						<td style="padding: 10px 0; text-align: left; font-weight: 800; color: #D4AF37;"><?php echo number_format($inv->amount); ?> AED</td>
					</tr>
					<tr>
						<td style="padding: 5px 0; color: #64748b;"><?php _e('نسبة الملكية المترتبة:', 'sukna'); ?></td>
						<td style="padding: 5px 0; text-align: left; font-weight: 700;"><?php echo round($share_pct, 2); ?> %</td>
					</tr>
				</table>
			</div>

			<div style="font-size: 0.8rem; color: #64748b; border-top: 1px dashed #eee; padding-top: 20px; text-align: center;">
				<?php _e('تم إنشاء هذه الوثيقة تلقائياً بواسطة نظام سكنى الإداري.', 'sukna'); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		wp_send_json_success($html);
	}

	public function get_report_html() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_logged_in() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );

		// Authorization check: Admin, Owner of property, or Investor in property
		if ( ! Sukna_Auth::is_admin() ) {
			$user_id = Sukna_Auth::current_user()->id;
			$p = Sukna_Properties::get_property($id);
			$is_investor = $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("SELECT id FROM {$GLOBALS['wpdb']->prefix}sukna_investments WHERE property_id = %d AND investor_id = %d", $id, $user_id));

			if ( $p->owner_id != $user_id && !$is_investor ) {
				wp_send_json_error( 'Access Denied' );
			}
		}

		require_once SUKNA_PATH . 'templates/report-pdf-template.php';
		$html = sukna_get_property_report_html($id);
		wp_send_json_success($html);
	}

	public function export_data() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$type = sanitize_text_field( $_POST['type'] ?? 'users' );
		global $wpdb;
		$data = array();
		$filename = "sukna_{$type}_export_" . date('Y-m-d') . ".csv";

		if ( $type === 'users' ) {
			$data = $wpdb->get_results( "SELECT username, phone, name, email, role, is_restricted FROM {$wpdb->prefix}sukna_staff", ARRAY_A );
		} elseif ( $type === 'properties' ) {
			$data = $wpdb->get_results( "SELECT name, address, country, city, state_emirate, property_type, property_subtype, apartment_number, floor_number, total_rooms, contract_start_date, investment_start_date, contract_duration, installments_per_year, base_value, gov_fees FROM {$wpdb->prefix}sukna_properties", ARRAY_A );
		}

		if ( empty($data) ) wp_send_json_error( 'No data found' );

		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array_keys(reset($data)));
		foreach ($data as $row) {
			fputcsv($df, $row);
		}
		fclose($df);
		$csv = ob_get_clean();

		wp_send_json_success( array(
			'csv' => $csv,
			'filename' => $filename
		) );
	}

	public function import_data() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$type = sanitize_text_field( $_POST['type'] ?? 'users' );
		$csv_data = $_POST['csv_data'] ?? '';

		if ( empty($csv_data) ) wp_send_json_error( 'Empty data' );

		$lines = explode( "\n", str_replace( "\r", "", $csv_data ) );
		$header = str_getcsv( array_shift( $lines ) );

		global $wpdb;
		$count = 0;

		foreach ( $lines as $line ) {
			if ( empty($line) ) continue;
			$row = array_combine( $header, str_getcsv( $line ) );
			if ( ! $row ) continue;

			if ( $type === 'users' ) {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sukna_staff WHERE phone = %s", $row['phone'] ) );
				if ( ! $exists ) {
					$wpdb->insert( "{$wpdb->prefix}sukna_staff", array(
						'username' => $row['username'],
						'phone'    => $row['phone'],
						'name'     => $row['name'],
						'email'    => $row['email'],
						'role'     => $row['role'],
						'is_restricted' => $row['is_restricted'],
						'password' => password_hash('12345678', PASSWORD_DEFAULT)
					) );
					$count++;
				}
			} elseif ( $type === 'properties' ) {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sukna_properties WHERE name = %s AND city = %s", $row['name'], $row['city'] ) );
				if ( ! $exists ) {
					// Use class method to ensure room generation
					Sukna_Properties::add_property( $row );
					$count++;
				}
			}
		}

		Sukna_Audit::log('import_data', sprintf(__('استيراد %d سجل من نوع %s', 'sukna'), $count, $type));
		wp_send_json_success( sprintf(__('تم استيراد %d سجل بنجاح.', 'sukna'), $count) );
	}

	public function get_installments() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		$room_id = intval( $_POST['room_id'] );

		global $wpdb;
		$installments = $wpdb->get_results( $wpdb->prepare( "
			SELECT p.* FROM {$wpdb->prefix}sukna_payments p
			JOIN {$wpdb->prefix}sukna_contracts c ON p.contract_id = c.id
			WHERE c.room_id = %d AND c.status = 'active'
			ORDER BY p.due_date ASC
		", $room_id ) );

		wp_send_json_success( $installments );
	}

	public function record_payment() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_owner() && ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;

		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sukna_payments WHERE id = %d", $id ) );
		if ( !$payment ) wp_send_json_error( 'Payment not found' );

		$wpdb->update( "{$wpdb->prefix}sukna_payments", array(
			'status' => 'paid',
			'payment_date' => current_time('mysql')
		), array( 'id' => $id ) );

		// Proportional Revenue Capture
		$room = $wpdb->get_row($wpdb->prepare("
			SELECT r.property_id, r.room_number
			FROM {$wpdb->prefix}sukna_rooms r
			JOIN {$wpdb->prefix}sukna_contracts c ON r.id = c.room_id
			JOIN {$wpdb->prefix}sukna_payments p ON c.id = p.contract_id
			WHERE p.id = %d
		", $id));

		Sukna_Investments::distribute_proportional_amount(
			$room->property_id,
			$payment->amount,
			'room_revenue',
			sprintf(__('تحصيل دفعة إيجار - وحدة #%s', 'sukna'), $room->room_number)
		);

		Sukna_Audit::log('record_payment', sprintf(__('تحصيل دفعة إيجار بقيمة %s AED للوحدة %s', 'sukna'), $payment->amount, $room->room_number));

		wp_send_json_success();
	}

	public function undo_activity() {
		check_ajax_referer( 'sukna_nonce', 'nonce' );
		if ( ! Sukna_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$log_id = intval( $_POST['log_id'] );
		$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sukna_activity_logs WHERE id = %d", $log_id));

		if ( ! $log || ! $log->meta_data ) wp_send_json_error( 'No undo data' );

		$data = json_decode( $log->meta_data, true );
		unset($data['id']);

		if ( $log->action_type === 'delete_user' ) {
			$wpdb->insert( "{$wpdb->prefix}sukna_staff", $data );
			$wpdb->delete( "{$wpdb->prefix}sukna_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		if ( $log->action_type === 'delete_property' ) {
			$wpdb->insert( "{$wpdb->prefix}sukna_properties", $data );
			$wpdb->delete( "{$wpdb->prefix}sukna_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		wp_send_json_error( 'Cannot undo this action' );
	}
}

new Sukna_Ajax();
