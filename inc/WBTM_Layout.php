<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('WBTM_Layout')) {
		class WBTM_Layout {
			public function __construct() {
				add_action('wbtm_search_result', [$this, 'search_result'], 10, 4);
				/*********************/
				add_action('wp_ajax_get_wbtm_dropping_point', [$this, 'get_wbtm_dropping_point']);
				add_action('wp_ajax_nopriv_get_wbtm_dropping_point', [$this, 'get_wbtm_dropping_point']);
				/**************************/
				add_action('wp_ajax_get_wbtm_bus_details', [$this, 'get_wbtm_bus_details']);
				add_action('wp_ajax_nopriv_get_wbtm_bus_details', [$this, 'get_wbtm_bus_details']);
				/**************************/
			}
			public function search_result($start_route, $end_route, $date) {
				require WBTM_Functions::template_path('layout/search_result.php');
			}
			public function get_wbtm_dropping_point() {
				$post_id = MP_Global_Function::data_sanitize($_POST['post_id']);
				$start_route = MP_Global_Function::data_sanitize($_POST['start_route']);
				self::route_list($start_route, $post_id);
				die();
			}
			public function get_wbtm_bus_details() {
				$post_id = MP_Global_Function::data_sanitize($_POST['post_id']);
				$start_route = MP_Global_Function::data_sanitize($_POST['start_route']);
				$end_route = MP_Global_Function::data_sanitize($_POST['end_route']);
				$date = $_POST['date'] ?? '';
				$seat_type = MP_Global_Function::get_post_info($post_id, 'wbtm_seat_type_conf');
				if ($seat_type == 'wbtm_seat_plan') {
					echo $seat_type;
				}
				else {
					require WBTM_Functions::template_path('layout/registration_without_seat_plan.php');
				}
				die();
			}
			public static function route_list($start_route = '', $post_id = 0) {
				$all_routes = WBTM_Functions::get_bus_route($start_route, $post_id);
				if (sizeof($all_routes) > 0) {
					?>
					<ul class="mp_input_select_list">
						<?php foreach ($all_routes as $route) { ?>
							<li data-value="<?php echo esc_attr($route); ?>">
								<span class="fas fa-map-marker"></span><?php echo esc_html($route); ?>
							</li>
						<?php } ?>
					</ul>
					<?php
				}
			}
			public static function next_date_suggestion($all_dates, $return = false, $post_id = 0) {
				if (sizeof($all_dates) > 0) {
					$count = 1;
					$target_page = MP_Global_Function::get_settings('wbtm_bus_settings', 'search_target_page');
					$target_page = $target_page ? get_post_field('post_name', $target_page) : 'bus-search-list';
					$start_route = isset($_POST['bus_start_route']) ? MP_Global_Function::data_sanitize($_POST['bus_start_route']) : '';
					$end_route = isset($_POST['bus_end_route']) ? MP_Global_Function::data_sanitize($_POST['bus_end_route']) : '';
					$j_date = $_POST['j_date'] ?? '';
					$r_date = $_POST['r_date'] ?? '';
					$active_date = $return ? $r_date : $j_date;
					$form_url = $post_id > 0 || is_admin() ? '' : get_site_url() . '/' . $target_page;
					if ($start_route && $end_route && $j_date) {
						?>
						<div class="buttonGroup _equalChild_fullWidth">
							<?php
								foreach ($all_dates as $date) {
									if ($count <= 6 && (strtotime($date) >= strtotime($active_date) || sizeof($all_dates) < 6)) {
										$btn_class = strtotime($date) == strtotime($active_date) ? '_themeButton_textWhite' : '_mpBtn_bgLight_textTheme';
										$url_j_date = $return ? $j_date : $date;
										$url_r_date = $return ? $date : $r_date;
										$url = $form_url . '?bus_start_route=' . $start_route . '&bus_end_route=' . $end_route . '&j_date=' . $url_j_date . '&r_date=' . $url_r_date;
										?>
										<button type="button" class="wbtm_next_date <?php echo esc_attr($btn_class); ?>" data-date="<?php echo esc_attr($date); ?>">
											<?php echo MP_Global_Function::date_format($date); ?>
										</button>
										<?php
										$count++;
									}
									?>
								<?php } ?>
						</div>
						<?php
					}
				}
			}
			public static function route_title($return = false) {
				$start_route = isset($_POST['bus_start_route']) ? MP_Global_Function::data_sanitize($_POST['bus_start_route']) : '';
				$end_route = isset($_POST['bus_end_route']) ? MP_Global_Function::data_sanitize($_POST['bus_end_route']) : '';
				$start = $return ? $end_route : $start_route;
				$end = $return ? $start_route : $end_route;
				$j_date = $_POST['j_date'] ?? '';
				$r_date = $_POST['r_date'] ?? '';
				$date = $return ? $r_date : $j_date;
				if ($date) {
					?>
					<div class="buttonGroup _mT_xs_equalChild_fullWidth">
						<button type="button" class="_mpBtn_h4">
							<?php echo esc_html($start); ?>
							<span class="fas fa-long-arrow-alt-right _mLR_xs"></span>
							<?php echo esc_html($end); ?>
						</button>
						<button type="button" class="_mpBtn_h4">
							<?php echo MP_Global_Function::date_format($date); ?>
						</button>
					</div>
					<?php
				}
			}
		}
		new WBTM_Layout();
	}