<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('WBTM_Query')) {
		class WBTM_Query {
			public function __construct() {}
			public static function get_bus_id($start, $end) {
				$bus_ids = [];
				$args = array(
					'post_type' => array('wbtm_bus'),
					'posts_per_page' => -1,
					'order' => 'ASC',
					'orderby' => 'meta_value',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => 'wbtm_bus_bp_stops',
							'value' => $start,
							'compare' => 'LIKE',
						),
						array(
							'key' => 'wbtm_bus_next_stops',
							'value' => $end,
							'compare' => 'LIKE',
						)
					)
				);
				$bus_query = new WP_Query($args);
				while ($bus_query->have_posts()) {
					$bus_query->the_post();
					$bus_ids[] = get_the_id();
				}
				wp_reset_query();
				return $bus_ids;
			}
			public static function query_total_booked($post_id, $start, $end, $date) {
				$total_booked=0;
				if ($post_id && $start && $end && $date) {
					$seat_booked_status = WBTM_Functions::get_settings('bus_seat_booked_on_order_status', array(1, 2));
					$bps = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_bp_stops', []);
					$dps = MP_Global_Function::get_post_info($post_id, 'wbtm_bus_next_stops', []);
					$bus_stops = array_column($bps, 'wbtm_bus_bp_stops_name'); // remove time
					$bus_ends = array_column($dps, 'wbtm_bus_next_stops_name');
					$bus_stops_merge = array_merge($bus_stops, $bus_ends);
					$routes = array_values(array_unique($bus_stops_merge));
					if(sizeof($routes)>0) {
						$sp = array_search($start, $routes);
						$ep = array_search($end, $routes);
						$args = array(
							'post_type' => 'wbtm_bus_booking',
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
									'relation' => 'AND',
									array(
										'key' => 'wbtm_seat',
										'value' => '',
										'compare' => '!='
									),
									array(
										'key' => 'wbtm_boarding_point',
										'value' => array_slice($routes, 0, $ep),
										'compare' => 'IN'
									),
									array(
										'key' => 'wbtm_droping_point',
										'value' => array_slice($routes, $sp + 1),
										'compare' => 'IN'
									),
									array(
										'key' => 'wbtm_journey_date',
										'value' => $date,
										'compare' => 'IN'
									),
									array(
										'key' => 'wbtm_bus_id',
										'value' => $post_id,
										'compare' => '='
									),
									array(
										'key' => 'wbtm_status',
										'value' => $seat_booked_status,
										'compare' => 'IN'
									),
								)
							),
						);
						$q = new WP_Query($args);
						$total_booked = $q->found_posts;
						wp_reset_postdata();
					}
				}
				return $total_booked;
			}
			public static function query_ex_service_sold($post_id,$date,$ex_name) {
				$total_booked = 0;
				if ($post_id && $date && $ex_name) {
					$args = array(
						'post_type' => 'wbtm_bus_booking',
						'posts_per_page' => -1,
						'meta_query' => array(
							array(
								'relation' => 'AND',
								array(
									'key' => 'wbtm_bus_id',
									'compare' => '=',
									'value' => $post_id,
								),
								array(
									'key' => 'wbtm_journey_date',
									'compare' => '=',
									'value' => $date,
								),
								array(
									'key' => 'wbtm_status',
									'compare' => 'IN',
									'value' => array(1, 2),
								),
							),
						)
					);
					$query = new WP_Query($args);
					if($query->found_posts>0){
						while($query->have_posts()){
							$query->the_post();
							$id=get_the_id();
							$ex_infos=MP_Global_Function::get_post_info($id,'wbtm_extra_services',[]);
							if(sizeof($ex_infos)>0){
								foreach($ex_infos as $ex_info){
									if($ex_info['name']==$ex_name){
										$total_booked+=max($ex_info['qty'], 0);
									}
									
								}
							}
						}
					}
					wp_reset_postdata();
				}
				return $total_booked;
			}
		}
		new WBTM_Query();
	}