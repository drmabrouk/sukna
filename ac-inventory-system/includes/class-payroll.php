<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AC_IS_Payroll {

	public static function get_staff_payroll( $month = null ) {
		global $wpdb;
		if ( ! $month ) $month = date('Y-m');

		$table_staff = $wpdb->prefix . 'ac_is_staff';
		$table_attendance = $wpdb->prefix . 'ac_is_attendance';

		// Hide system admin from payroll
		$staff_list = $wpdb->get_results( "SELECT * FROM $table_staff WHERE username != 'admin' ORDER BY name ASC" );
		$payroll = array();

		$work_start = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = 'default_start'") ?: "09:00:00";
		$work_end   = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = 'default_end'") ?: "22:00:00";

		foreach ( $staff_list as $staff ) {
			$attendance = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $table_attendance WHERE staff_id = %d AND work_date LIKE %s",
				$staff->id, $month . '%'
			) );

			$days_present = 0;
			$total_hours  = 0;
			$pending_shifts = 0;
			$approved_shifts = 0;

			foreach ( $attendance as $a ) {
				if ( $a->status === 'present' ) {
					$days_present++;
					if ( $a->check_in && $a->check_out ) {
						$diff = strtotime($a->check_out) - strtotime($a->check_in);
						$total_hours += ($diff / 3600);
					}
				} elseif ( $a->status === 'shift_request' ) {
					$pending_shifts++;
				} elseif ( $a->status === 'shift_approved' ) {
					$approved_shifts++;
					$days_present++; // Count as present for salary
				}
			}

			$daily_rate = $staff->base_salary / ($staff->working_days ?: 26);
			$hourly_rate = $daily_rate / ($staff->working_hours ?: 8);

			// Simple deduction: base salary - (missed days * daily rate)
			$missed_days = max(0, $staff->working_days - $days_present);
			$deductions = $missed_days * $daily_rate;

			// Delay and Early exit deductions
			$delay_deduction = 0;
			foreach ( $attendance as $a ) {
				if ( $a->status === 'present' && $a->check_in && $a->check_out ) {
					// Check delay
					if ( strtotime($a->check_in) > strtotime($work_start) ) {
						$delay_sec = strtotime($a->check_in) - strtotime($work_start);
						$delay_deduction += ($delay_sec / 3600) * $hourly_rate;
					}
					// Check early exit
					if ( strtotime($a->check_out) < strtotime($work_end) ) {
						$early_sec = strtotime($work_end) - strtotime($a->check_out);
						$delay_deduction += ($early_sec / 3600) * $hourly_rate;
					}
				}
			}

			$final_salary = $staff->base_salary - $deductions - $delay_deduction;

			$payroll[] = (object) array(
				'staff_id'       => $staff->id,
				'name'           => $staff->name,
				'base_salary'    => $staff->base_salary,
				'days_present'   => $days_present,
				'total_hours'    => round($total_hours, 2),
				'pending_shifts' => $pending_shifts,
				'deductions'     => round($deductions + $delay_deduction, 2),
				'net_salary'     => round(max(0, $final_salary), 2),
				'working_hours'  => $staff->working_hours
			);
		}

		return $payroll;
	}

	public static function record_attendance( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_attendance';

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE staff_id = %d AND work_date = %s",
			$data['staff_id'], $data['work_date']
		) );

		if ( $exists ) {
			return $wpdb->update( $table, $data, array( 'id' => $exists ) );
		} else {
			return $wpdb->insert( $table, $data );
		}
	}

	public static function get_staff_attendance_logs( $staff_id, $month ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ac_is_attendance';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table WHERE staff_id = %d AND work_date LIKE %s ORDER BY work_date ASC",
			$staff_id, $month . '%'
		) );
	}
}
