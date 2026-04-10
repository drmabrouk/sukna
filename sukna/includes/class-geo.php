<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sukna_Geo {
	public static function get_data() {
		return array(
			'EG' => array(
				'name' => 'مصر',
				'states' => array(
					'Cairo' => array('name' => 'القاهرة', 'cities' => array('القاهرة', 'حلوان', 'بدر')),
					'Giza' => array('name' => 'الجيزة', 'cities' => array('الجيزة', '6 أكتوبر', 'الشيخ زايد')),
					'Alexandria' => array('name' => 'الإسكندرية', 'cities' => array('الإسكندرية', 'برج العرب')),
					// Add more as needed
				)
			),
			'AE' => array(
				'name' => 'الإمارات العربية المتحدة',
				'states' => array(
					'Abu Dhabi' => array('name' => 'أبو ظبي', 'cities' => array('أبو ظبي', 'العين', 'الظفرة')),
					'Dubai' => array('name' => 'دبي', 'cities' => array('دبي')),
					'Sharjah' => array('name' => 'الشارقة', 'cities' => array('الشارقة', 'خورفكان', 'كلباء')),
					'Ajman' => array('name' => 'عجمان', 'cities' => array('عجمان')),
					'Umm Al Quwain' => array('name' => 'أم القيوين', 'cities' => array('أم القيوين')),
					'Ras Al Khaimah' => array('name' => 'رأس الخيمة', 'cities' => array('رأس الخيمة')),
					'Fujairah' => array('name' => 'الفجيرة', 'cities' => array('الفجيرة')),
				)
			),
			'SA' => array(
				'name' => 'المملكة العربية السعودية',
				'states' => array(
					'Riyadh' => array('name' => 'الرياض', 'cities' => array('الرياض', 'الخرج', 'الدرعية')),
					'Makkah' => array('name' => 'مكة المكرمة', 'cities' => array('مكة المكرمة', 'جدة', 'الطائف')),
					'Madinah' => array('name' => 'المدينة المنورة', 'cities' => array('المدينة المنورة', 'ينبع')),
					'Eastern' => array('name' => 'المنطقة الشرقية', 'cities' => array('الدمام', 'الخبر', 'الظهران', 'الأحساء')),
				)
			),
			'KW' => array(
				'name' => 'الكويت',
				'states' => array(
					'Kuwait' => array('name' => 'العاصمة', 'cities' => array('مدينة الكويت')),
					'Hawalli' => array('name' => 'حولي', 'cities' => array('حولي', 'السالمية')),
				)
			),
			'QA' => array(
				'name' => 'قطر',
				'states' => array(
					'Doha' => array('name' => 'الدوحة', 'cities' => array('الدوحة')),
					'Al Rayyan' => array('name' => 'الريان', 'cities' => array('الريان')),
				)
			),
			'BH' => array(
				'name' => 'البحرين',
				'states' => array(
					'Capital' => array('name' => 'العاصمة', 'cities' => array('المنامة')),
					'Muharraq' => array('name' => 'المحرق', 'cities' => array('المحرق')),
				)
			),
			'OM' => array(
				'name' => 'عمان',
				'states' => array(
					'Muscat' => array('name' => 'مسقط', 'cities' => array('مسقط', 'السيب')),
					'Dhofar' => array('name' => 'ظفار', 'cities' => array('صلالة')),
				)
			),
		);
	}
}
