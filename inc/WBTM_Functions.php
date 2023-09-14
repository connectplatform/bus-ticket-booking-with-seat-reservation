<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('WBTM_Functions')) {
		class WBTM_Functions {
			public static function template_path($file_name): string {
				$template_path = get_stylesheet_directory() . '/templates/';
				$default_dir = WBTM_PLUGIN_DIR . '/templates/';
				$dir = is_dir($template_path) ? $template_path : $default_dir;
				$file_path = $dir . $file_name;
				return locate_template(array('templates/' . $file_name)) ? $file_path : $default_dir . $file_name;
			}
			//==========================//
			public static function get_bus_route($start_route = '', $post_id = 0) {
				$all_routes = [];
				if ($post_id > 0) {
					$route_key = !$start_route ? 'wbtm_bus_bp_stops' : 'wbtm_bus_next_stops';
					$route_name = !$start_route ? 'wbtm_bus_bp_stops_name' : 'wbtm_bus_next_stops_name';
					$routes = MP_Global_Function::get_post_info($post_id, $route_key, []);
					if (sizeof($routes) > 0) {
						foreach ($routes as $route) {
							if ($route[$route_name]) {
								$all_routes[] = $route[$route_name];
							}
						}
					}
				}
				else {
					if (!$start_route) {
						$routes = MP_Global_Function::get_taxonomy('wbtm_bus_stops');
						if (sizeof($routes) > 0) {
							foreach ($routes as $route) {
								$get_term = get_term_by('name', $route->name, 'wbtm_bus_stops');
								$is_hide_on_boarding = get_term_meta($get_term->term_id, 'wbtm_is_hide_global_boarding', true);
								if ($is_hide_on_boarding !== 'yes') {
									$all_routes[] = $route->name;
								}
							}
						}
					}
					else {
						$category = get_term_by('name', $start_route, 'wbtm_bus_stops');
						$dropping_points = get_term_meta($category->term_id, 'wbtm_bus_routes_name_list', true);
						$dropping_points = $dropping_points ? MP_Global_Function::data_sanitize($dropping_points) : array();
						if (sizeof($dropping_points) > 0) {
							foreach ($dropping_points as $dropping_point) {
								$all_routes[] = $dropping_point['wbtm_bus_routes_name'];
							}
						}
						else {
							$routes = MP_Global_Function::get_taxonomy('wbtm_bus_stops');
							if (sizeof($routes) > 0) {
								foreach ($routes as $route) {
									$all_routes[] = $route->name;
								}
							}
						}
					}
				}
				return $all_routes;
			}
			public static function get_bus_type($post_id) {
				$term = get_the_terms($post_id, 'wbtm_bus_cat');
				return $term ? MP_Global_Function::data_sanitize($term[0]->name) : '';
			}
			//==========================//
			public static function get_dp_info($post_id, $date, $all_dates = []) {
				$dp_info = [];
				$all_dates = sizeof($all_dates) > 0 ? $all_dates : self::get_post_date($post_id);
				if ($post_id > 0 && $date && sizeof($all_dates) > 0 && in_array($date, $all_dates)) {
					$start_routes = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_bp_stops', []);
					$end_routes = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_next_stops', []);
					$route_summaries = MP_Global_Function::get_post_info($post_id, 'wbtm_route_summary', []);
					if (sizeof($start_routes) > 0 && sizeof($end_routes) > 0 && sizeof($route_summaries) > 0) {
						$bp = $start_routes[0]['wbtm_bus_bp_stops_name'];
						$bp_date = $date . ' ' . $start_routes[0]['wbtm_bus_bp_start_time'];
						$bp_date = date('Y-m-d H:i', strtotime($bp_date));
						foreach ($end_routes as $end_route) {
							$dp = $end_route['wbtm_bus_next_stops_name'];
							$dp_date = $date . ' ' . $end_route['wbtm_bus_next_end_time'];
							$dp_date = date('Y-m-d H:i', strtotime($dp_date));
							$dp_info[] = [
								'dp' => $dp,
								'dp_time' => self::get_date_from_route_summary($bp, $dp, $bp_date, $dp_date, $route_summaries),
							];
						}
					}
				}
				return $dp_info;
			}
			public static function get_bp_info($post_id, $all_dates = []) {
				$all_dates = sizeof($all_dates) > 0 ? $all_dates : self::get_post_date($post_id);
				$all_infos = [];
				$start_routes = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_bp_stops', []);
				$route_summaries = MP_Global_Function::get_post_info($post_id, 'wbtm_route_summary', []);
				if (sizeof($all_dates) > 0 && sizeof($start_routes) > 0 && sizeof($route_summaries) > 0) {
					foreach ($all_dates as $date) {
						$count = 0;
						$prev_date = '';
						$prev_bp = '';
						foreach ($start_routes as $start_route) {
							$bp = $start_route['wbtm_bus_bp_stops_name'];
							$bp_date = $date . ' ' . $start_route['wbtm_bus_bp_start_time'];
							$bp_date = date('Y-m-d H:i', strtotime($bp_date));
							$bp_date = $count > 0 ? self::get_date_from_route_summary($prev_bp, $bp, $bp_date, $prev_date, $route_summaries) : $bp_date;
							$all_infos[$date][] = [
								'bp' => $bp,
								'bp_time' => $bp_date,
							];
							$prev_date = $bp_date;
							$prev_bp = $bp;
							$count++;
						}
					}
				}
				return $all_infos;
			}
			public static function get_post_date($post_id) {
				$all_dates = [];
				if ($post_id > 0) {
					$show_on_dates = MP_Global_Function::get_post_info($post_id, 'show_operational_on_day', 'no');
					$on_dates_text = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_on_dates', array());
					$now = current_time('Y-m-d');
					$now_full = current_time('Y-m-d H:i');
					$year = current_time('Y');
					if ($show_on_dates != 'no' && $on_dates_text) {
						$on_dates = explode(', ', $on_dates_text);
						foreach ($on_dates as $on_date) {
							$date_item = date('Y-m-d', strtotime($year . '-' . $on_date));
							if (strtotime($date_item) < strtotime($now)) {
								$date_item = date('Y-m-d', strtotime($year + 1 . '-' . $on_date));
							}
							if (strtotime($date_item) > strtotime($now_full)) {
								$all_dates[] = $date_item;
							}
						}
					}
					else {
						$sale_end_date = self::get_settings('wbtm_ticket_sale_close_date', '');
						$sale_end_date = $sale_end_date ? date('Y-m-d', strtotime($sale_end_date)) : '';
						$active_days = self::get_settings('wbtm_ticket_sale_max_date', 30);
						$start_date = $now;
						$end_date = date('Y-m-d', strtotime($start_date . ' +' . $active_days . ' day'));
						if ($sale_end_date && strtotime($sale_end_date) < strtotime($end_date)) {
							$end_date = $sale_end_date;
						}
						if (strtotime($start_date) < strtotime($end_date)) {
							$all_off_dates = MP_Global_Function::get_post_info($post_id, 'wbtm_offday_schedule', array());
							$off_dates = [];
							foreach ($all_off_dates as $off_date) {
								if ($off_date['from_date'] && $off_date['to_date']) {
									$from_date = date('Y-m-d', strtotime($year . '-' . $off_date['from_date']));
									$to_date = date('Y-m-d', strtotime($year . '-' . $off_date['to_date']));
									$off_date_lists = MP_Global_Function::date_separate_period($from_date, $to_date);
									foreach ($off_date_lists as $off_date_list) {
										$off_dates[] = $off_date_list->format('Y-m-d');
									}
								}
							}
							$off_dates = array_unique($off_dates);
							$off_days = MP_Global_Function::get_post_info($post_id, 'weekly_offday', array());
							$show_off_day = MP_Global_Function::get_post_info($post_id, 'show_off_day');
							$dates = MP_Global_Function::date_separate_period($start_date, $end_date);
							foreach ($dates as $date) {
								$date = $date->format('Y-m-d');
								if (strtotime($date) > strtotime($now_full)) {
									$day = strtolower(date('w', strtotime($date)));
									if ($show_off_day = 'yes') {
										if (!in_array($date, $off_dates) && !in_array($day, $off_days)) {
											$all_dates[] = $date;
										}
									}
									else {
										$all_dates[] = $date;
									}
								}
							}
						}
					}
				}
				return $all_dates;
			}
			public static function get_date_from_route_summary($start, $end, $date, $prev_date, $route_summaries = []) {
				if (sizeof($route_summaries) > 0) {
					foreach ($route_summaries as $route_summary) {
						if ($route_summary['boarding'] == $start && $route_summary['dropping'] == $end) {
							$travel_day = $route_summary['travel_day'] - 1;
							if ($travel_day < 1) {
								if (strtotime($date) > strtotime($prev_date)) {
									$date = date('Y-m-d H:i', strtotime($date . ' +1 day'));
								}
							}
							else {
								$date = date('Y-m-d H:i', strtotime($date . ' +' . $travel_day . 'day'));
							}
						}
					}
				}
				return $date;
			}
			public static function get_date($post_id, $start_route = '') {
				$now = current_time('Y-m-d');
				$now_full = current_time('Y-m-d H:i');
				$year = current_time('Y');
				$start_stops = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_bp_stops', []);
				$all_dates = [];
				if ($post_id > 0) {
					$show_on_dates = MP_Global_Function::get_post_info($post_id, 'show_operational_on_day', 'no');
					$on_dates_text = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_on_dates', array());
					if ($show_on_dates != 'no' && $on_dates_text) {
						$on_dates = explode(', ', $on_dates_text);
						foreach ($on_dates as $on_date) {
							$date_item = date('Y-m-d', strtotime($year . '-' . $on_date));
							if (strtotime($date_item) < strtotime($now)) {
								$date_item = date('Y-m-d', strtotime($year + 1 . '-' . $on_date));
							}
							$date_full = self::reduce_buffer_time($post_id, $date_item, $start_stops, $start_route);
							if (strtotime($date_full) > strtotime($now_full)) {
								$all_dates[] = $date_item;
							}
						}
					}
					else {
						$sale_end_date = self::get_settings('wbtm_ticket_sale_close_date', '');
						$sale_end_date = $sale_end_date ? date('Y-m-d', strtotime($sale_end_date)) : '';
						$active_days = self::get_settings('wbtm_ticket_sale_max_date', 30);
						$start_date = $now;
						$end_date = date('Y-m-d', strtotime($start_date . ' +' . $active_days . ' day'));
						if ($sale_end_date && strtotime($sale_end_date) < strtotime($end_date)) {
							$end_date = $sale_end_date;
						}
						if (strtotime($start_date) < strtotime($end_date)) {
							$all_off_dates = MP_Global_Function::get_post_info($post_id, 'wbtm_offday_schedule', array());
							$off_dates = [];
							foreach ($all_off_dates as $off_date) {
								if ($off_date['from_date'] && $off_date['to_date']) {
									$from_date = date('Y-m-d', strtotime($year . '-' . $off_date['from_date']));
									$to_date = date('Y-m-d', strtotime($year . '-' . $off_date['to_date']));
									$off_date_lists = MP_Global_Function::date_separate_period($from_date, $to_date);
									foreach ($off_date_lists as $off_date_list) {
										$off_dates[] = $off_date_list->format('Y-m-d');
									}
								}
							}
							$off_dates = array_unique($off_dates);
							$off_days = MP_Global_Function::get_post_info($post_id, 'weekly_offday', array());
							$show_off_day = MP_Global_Function::get_post_info($post_id, 'show_off_day');
							$dates = MP_Global_Function::date_separate_period($start_date, $end_date);
							foreach ($dates as $date) {
								$date = $date->format('Y-m-d');
								$date_full = self::reduce_buffer_time($post_id, $date, $start_stops, $start_route);
								if (strtotime($date_full) > strtotime($now_full)) {
									$day = strtolower(date('w', strtotime($date)));
									if ($show_off_day = 'yes') {
										if (!in_array($date, $off_dates) && !in_array($day, $off_days)) {
											$all_dates[] = $date;
										}
									}
									else {
										$all_dates[] = $date;
									}
								}
							}
						}
					}
				}
				return $all_dates;
			}
			public static function get_all_dates($post_id = 0, $start_route = '') {
				$all_dates = [];
				if ($post_id > 0) {
					$all_dates = self::get_date($post_id, $start_route);
				}
				else {
					$all_post_ids = MP_Global_Function::get_all_post_id('wbtm_bus');
					if (sizeof($all_post_ids) > 0) {
						foreach ($all_post_ids as $all_post_id) {
							$dates = self::get_date($all_post_id);
							$all_dates = array_merge($all_dates, $dates);
						}
					}
				}
				$all_dates = array_unique($all_dates);
				usort($all_dates, "MP_Global_Function::sort_date");
				return $all_dates;
			}
			public static function reduce_buffer_time($post_id, $date, $start_stops, $start_route = '') {
				$full_date = $date;
				if ($post_id > 0 && $date) {
					$start_stops = $start_stops ?: MP_Global_Function::get_post_info($post_id, 'wbtm_bus_bp_stops', []);
					if ($start_stops && sizeof($start_stops) > 0) {
						if ($start_route) {
							$count = 0;
							$start_full_date = '';
							foreach ($start_stops as $start_stop) {
								if ($count < 1) {
									if ($start_stop['wbtm_bus_bp_stops_name'] == $start_route) {
										$full_date = $date . ' ' . $start_stop['wbtm_bus_bp_start_time'];
										break;
									}
									else {
										$start_full_date = $date . ' ' . $start_stop['wbtm_bus_bp_start_time'];
									}
									$count++;
								}
								if ($start_stop['wbtm_bus_bp_stops_name'] == $start_route) {
									$full_date = $date . ' ' . $start_stop['wbtm_bus_bp_start_time'];
									break;
								}
							}
							if ($start_full_date && strtotime($start_full_date) > strtotime($full_date)) {
								$full_date = date('Y-m-d H:i', strtotime($full_date . ' +1 day'));
							}
						}
						else {
							$full_date = $date . ' ' . end($start_stops)['wbtm_bus_bp_start_time'];
						}
					}
					$buffer_time = self::get_settings('bus_buffer_time', 0) * 60;
					if ($buffer_time > 0) {
						$full_date = date('Y-m-d H:i', strtotime($full_date) - $buffer_time);
					}
					else {
						$full_date = date('Y-m-d H:i', strtotime($full_date));
					}
				}
				return $full_date;
			}
			public static function get_time($post_id, $place, $drop = false) {
				$route_key = !$drop ? 'wbtm_bus_bp_stops' : 'wbtm_bus_next_stops';
				$route_name = !$drop ? 'wbtm_bus_bp_stops_name' : 'wbtm_bus_next_stops_name';
				$route_time_key = $drop ? 'wbtm_bus_next_end_time' : 'wbtm_bus_bp_start_time';
				$routes = MP_Global_Function::get_post_info($post_id, $route_key, []);
				if (sizeof($routes) > 0) {
					foreach ($routes as $route) {
						if ($route[$route_name] == $place) {
							return $route[$route_time_key];
						}
					}
				}
				return false;
			}
			public static function get_bus_all_info($bus_id,$date, $start_route,$end_route, $bp_infos = [],$all_dates=[] ) {
				$all_dates = sizeof($all_dates) > 0 ? $all_dates : WBTM_Functions::get_post_date($bus_id);
				$bp_infos = sizeof($bp_infos) > 0 ? $bp_infos : WBTM_Functions::get_bp_info($bus_id,$all_dates);
				if (sizeof($bp_infos) > 0) {
					$now_full = current_time('Y-m-d H:i');
					foreach ($bp_infos as $bp_info) {
						if (sizeof($bp_info) > 0) {
							foreach ($bp_info as $info) {
								if ($start_route == $info['bp'] && strtotime($date) == strtotime(date('Y-m-d', strtotime($info['bp_time'])))) {
									$bp_date = $info['bp_time'];
									$slice_time = self::slice_buffer_time($bp_date);
									if (strtotime($now_full) < strtotime($slice_time)) {
										$dp_date='';
										$dp_infos=self::get_dp_info($bus_id ,$date,$all_dates);
										if(sizeof($dp_infos)>0){
											foreach ($dp_infos as $dp_info){
												if($end_route==$dp_info['dp']){
													$dp_date=$dp_info['dp_time'];
													break;
												}
											}
										}
										$total_seat=WBTM_Functions::get_total_seat($bus_id);
										$sold_seat=WBTM_Query::query_total_booked($bus_id, $start_route, $end_route, $date);
										$available_seat = $total_seat - $sold_seat;
										return [
											'start_point' => $bp_info[0]['bp'],
											'start_time' => $bp_info[0]['bp_time'],
											'bp' => $start_route,
											'bp_time' => $bp_date,
											'dp' => $end_route,
											'dp_time' => $dp_date,
											'price' => WBTM_Functions::get_seat_price($bus_id, $start_route, $end_route),
											'total_seat'=>$total_seat,
											'sold_seat'=>$sold_seat,
											'available_seat'=>max(0,$available_seat)
										];
									}
								}
							}
						}
					}
				}
				return [];
			}
			public static function slice_buffer_time($date) {
				$buffer_time = self::get_settings('bus_buffer_time', 0) * 60;
				if ($buffer_time > 0) {
					$date = date('Y-m-d H:i', strtotime($date) - $buffer_time);
				}
				return $date;
			}
			//==========================//
			public static function get_total_seat($post_id) {
				$seat_type = MP_Global_Function::get_post_info($post_id, 'wbtm_seat_type_conf');
				$total_seat = 0;
				if ($seat_type == 'wbtm_seat_plan') {
					$seats_rows = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_seats_info');
					$seat_col = MP_Global_Function::get_post_info($post_id, 'wbtm_seat_cols');
					if ($seats_rows && $seat_col) {
						foreach ($seats_rows as $seat) {
							for ($i = 1; $i <= (int)$seat_col; $i++) {
								$seat_name = strtolower($seat["seat" . $i]);
								if ($seat_name != 'door' && $seat_name != 'wc' && $seat_name != '') {
									$total_seat++;
								}
							}
						}
						$seats_dd = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_seats_info_dd');
						$seat_col_dd = MP_Global_Function::get_post_info($post_id, 'wbtm_seat_cols_dd');
						if (is_array($seats_dd) && sizeof($seats_dd) > 0) {
							foreach ($seats_dd as $seat) {
								for ($i = 1; $i <= $seat_col_dd; $i++) {
									$seat_name = $seat["dd_seat" . $i] ?? '';
									if ($seat_name != 'door' && $seat_name != 'wc' && $seat_name != '') {
										$total_seat++;
									}
								}
							}
						}
					}
				}
				else {
					$total_seat = MP_Global_Function::get_post_info($post_id, 'wbtm_total_seat');
				}
				return $total_seat;
			}
			//==========================//
			public static function get_seat_price($post_id, $start_route, $end_route, $seat_type = 0, $dd = false) {
				if ($post_id && $start_route && $end_route) {
					$start_route = strtolower($start_route);
					$end_route = strtolower($end_route);
					$price_infos = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_prices', []);
					if (sizeof($price_infos) > 0) {
						foreach ($price_infos as $price_info) {
							if (strtolower($price_info['wbtm_bus_bp_price_stop']) == $start_route && strtolower($price_info['wbtm_bus_dp_price_stop']) == $end_route) {
								$seat_type_price_key = 'wbtm_bus_price';
								$seat_type_price_key = $seat_type == 1 ? 'wbtm_bus_child_price' : $seat_type_price_key;
								$seat_type_price_key = $seat_type == 2 ? 'wbtm_bus_infant_price' : $seat_type_price_key;
								$price = $price_info[$seat_type_price_key];
								$seat_plan_type = MP_Global_Function::get_post_info($post_id, 'wbtm_seat_type_conf');
								if ($seat_plan_type == 'wbtm_seat_plan' && $dd) {
									$seat_dd_increase = (int)MP_Global_Function::get_post_info($post_id, 'wbtm_seat_dd_price_parcent', 0);
									$price = $price + ($price * $seat_dd_increase / 100);
								}
								return max(0, $price);
							}
						}
					}
				}
				return false;
			}
			public static function get_ex_service_price($post_id, $service_name) {
				$show_extra_service = MP_Global_Function::get_post_info($post_id, 'show_extra_service', 'no');
				if ($show_extra_service == 'yes') {
					$ex_services = MP_Global_Function::get_post_info($post_id, 'mep_events_extra_prices', []);
					if (sizeof($ex_services) > 0) {
						foreach ($ex_services as $ex_service) {
							if ($ex_service['option_name'] == $service_name) {
								return max(0, $ex_service['option_price']);
							}
						}
					}
				}
				return false;
			}
			public static function get_ticket_info($post_id, $start_route, $end_route) {
				$ticket_infos = [];
				if ($post_id && $start_route && $end_route) {
					$start_route = strtolower($start_route);
					$end_route = strtolower($end_route);
					$price_infos = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_prices', []);
					if (sizeof($price_infos) > 0) {
						foreach ($price_infos as $price_info) {
							if (strtolower($price_info['wbtm_bus_bp_price_stop']) == $start_route && strtolower($price_info['wbtm_bus_dp_price_stop']) == $end_route) {
								$adult_price = $price_info['wbtm_bus_price'];
								$child_price = $price_info['wbtm_bus_child_price'];
								$infant_price = $price_info['wbtm_bus_infant_price'];
								if ($adult_price && (float)$adult_price >= 0) {
									$ticket_infos[] = [
										'name' => WBTM_Functions::get_settings('wbtm_seat_type_adult_label', esc_html__('Adult', 'bus-ticket-booking-with-seat-reservation')),
										'price' => (float)$adult_price,
										'type' => 0
									];
								}
								if ($child_price && (float)$child_price >= 0) {
									$ticket_infos[] = [
										'name' => WBTM_Functions::get_settings('wbtm_seat_type_child_label', esc_html__('Child', 'bus-ticket-booking-with-seat-reservation')),
										'price' => (float)$child_price,
										'type' => 1
									];
								}
								if ($infant_price && (float)$infant_price >= 0) {
									$ticket_infos[] = [
										'name' => WBTM_Functions::get_settings('wbtm_seat_type_infant_label', esc_html__('Infant', 'bus-ticket-booking-with-seat-reservation')),
										'price' => (float)$infant_price,
										'type' => 2
									];
								}
							}
						}
					}
				}
				return $ticket_infos;
			}
			//==========================//
			public static function get_cpt(): string {
				return 'wbtm_bus';
			}
			public static function get_settings($key, $default = '') {
				if (isset($GLOBALS['wbtm_bus_settings'][$key]) && $GLOBALS['wbtm_bus_settings'][$key]) {
					$default = $GLOBALS['wbtm_bus_settings'][$key];
				}
				return $default;
			}
			public static function get_name() {
				return self::get_settings('bus_menu_label', esc_html__('Bus', 'bus-ticket-booking-with-seat-reservation'));
			}
			public static function get_slug() {
				return self::get_settings('bus_menu_slug', 'bus');
			}
			public static function get_icon() {
				return self::get_settings('bus_menu_icon', 'dashicons-car');
			}
		}
	}