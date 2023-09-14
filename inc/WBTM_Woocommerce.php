<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('WBTM_Woocommerce')) {
		class WBTM_Woocommerce {
			public function __construct() {
				add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 90, 3);
				add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 90, 1);
				add_filter('woocommerce_cart_item_thumbnail', array($this, 'cart_item_thumbnail'), 90, 3);
				add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 20, 2);
				/**********************************************/
				
				add_action('woocommerce_after_order_notes', array($this, 'after_order_notes'));
				add_action('woocommerce_checkout_update_order_meta', array($this, 'checkout_update_order_meta'));
				add_action('woocommerce_after_checkout_validation', array($this, 'after_checkout_validation'));
				add_action('woocommerce_checkout_create_order_line_item', array($this, 'checkout_create_order_line_item'), 10, 4);
				add_action('woocommerce_after_order_itemmeta', [$this, 'after_order_itemmeta'], 10, 3);
				add_action('woocommerce_order_item_display_meta_value', [$this, 'order_item_display_meta_value'], 10, 3);
				add_action('woocommerce_checkout_order_processed', array($this, 'bus_order_processed'), 10);
				add_action('woocommerce_order_status_changed', array($this, 'wbtm_bus_ticket_seat_management'), 10, 4);
			}
			public function add_cart_item_data($cart_item_data, $product_id) {
				$linked_id = MP_Global_Function::get_post_info($product_id, 'link_mptbm_id', $product_id);
				$post_id = is_string(get_post_status($linked_id)) ? $linked_id : $product_id;
				if (get_post_type($post_id) == WBTM_Functions::get_cpt()) {
					$start_place = MP_Global_Function::get_submit_info('wbtm_start_stops');
					$end_place = MP_Global_Function::get_submit_info('wbtm_end_stops');
					$date = MP_Global_Function::get_submit_info('wbtm_journey_date');
					$seat_price = self::get_cart_seat_price($post_id);
					$ex_service_price = self::get_cart_ex_service_price($post_id);
					$total_price = $seat_price + $ex_service_price;
					$cart_item_data['wbtm_bus_id'] = $post_id;
					$cart_item_data['wbtm_journey_date'] = $date;
					$cart_item_data['wbtm_journey_time'] = WBTM_Functions::get_time($post_id, $start_place);
					$cart_item_data['wbtm_start_stops'] = $start_place;
					$cart_item_data['wbtm_end_stops'] = $end_place;
					$cart_item_data['wbtm_seats'] = self::cart_ticket_info($post_id);
					$cart_item_data['wbtm_seat_original_fare'] = $seat_price;
					$cart_item_data['extra_services'] = self::cart_extra_service_info($post_id);
					$cart_item_data['wbtm_pickpoint'] = MP_Global_Function::get_submit_info('wbtm_pickpoint');
					$cart_item_data['wbtm_passenger_info'] = apply_filters('wbtm_user_info_data', array(), $post_id);
					$cart_item_data['wbtm_tp'] = $total_price;
					$cart_item_data['line_total'] = $total_price;
					$cart_item_data['line_subtotal'] = $total_price;
					$cart_item_data = apply_filters('wbtm_add_cart_item', $cart_item_data, $post_id);
				}
				echo '<pre>';print_r($cart_item_data);echo '</pre>';die();
				return $cart_item_data;
			}
			public function before_calculate_totals($cart_object) {
				foreach ($cart_object->cart_contents as $value) {
					$post_id= array_key_exists('wbtm_bus_id', $value) ? $value['wbtm_bus_id'] : 0;
					if (get_post_type($post_id) == WBTM_Functions::get_cpt()) {
						$total_price = $value['wbtm_tp'];
						$value['data']->set_price($total_price);
						$value['data']->set_regular_price($total_price);
						$value['data']->set_sale_price($total_price);
						$value['data']->set_sold_individually('yes');
						$value['data']->get_price();
					}
				}
			}
			public function cart_item_thumbnail($thumbnail, $cart_item) {
				$post_id = array_key_exists('wbtm_bus_id', $cart_item) ? $cart_item['wbtm_bus_id'] : 0;
				if (get_post_type($post_id) == WBTM_Functions::get_cpt()) {
					$thumbnail = '<div class="bg_image_area" data-href="' . get_the_permalink($post_id) . '"><div data-bg-image="' . MP_Global_Function::get_image_url($post_id) . '"></div></div>';
				}
				return $thumbnail;
			}
			public function get_item_data($item_data, $cart_item) {
				ob_start();
				$post_id = array_key_exists('wbtm_bus_id', $cart_item) ? $cart_item['wbtm_bus_id'] : 0;
				if (get_post_type($post_id) == WBTM_Functions::get_cpt()) {
					$this->show_cart_item($cart_item, $post_id);
					do_action('wbtm_show_cart_item', $cart_item, $post_id);
				}
				$item_data[] = array('key' => esc_html__('Booking Details ', 'bus-ticket-booking-with-seat-reservation'),'value'=>ob_get_clean());
				return $item_data;
			}
			public function show_cart_item($cart_item, $post_id) {
				$ticket_type = $cart_item['wbtm_seats'] ?: [];
				$extra_service = $cart_item['extra_services'] ?: [];
				$date = $cart_item['wbtm_journey_date'];
				$data_format = MP_Global_Function::check_time_exit_date($date) ? 'date-time-text' : 'date-text';
				$date = TTBM_Function::datetime_format($date, $data_format);
				?>
				<div class="mpStyle">
					<?php do_action('ttbm_before_cart_item_display', $cart_item, $ttbm_id); ?>
					<div class="dLayout_xs bgTransparent marXsT">
						<ul class="cart_list">
							<?php if (!empty($location) && MP_Global_Function::get_post_info( $ttbm_id, 'ttbm_display_location', 'on' ) != 'off' ) { ?>
								<li>
									<span class="fas fa-map-marker-alt"></span>&nbsp;
									<h6><?php echo esc_html($tour_name . ' ' . esc_html__('Location', 'tour-booking-manager')); ?> :&nbsp;</h6>
									<span><?php echo esc_html($location); ?></span>
								</li>
							<?php } ?>
							<?php if (sizeof($hotel_info) > 0) { ?>
								<li>
									<span class="fas fa-hotel"></span>&nbsp;
									<h6><?php esc_html_e('Hotel Name', 'tour-booking-manager'); ?> :&nbsp;</h6>
									<span><?php echo get_the_title($hotel_info['hotel_id']); ?></span>
								</li>
								<li>
									<span class="far fa-calendar-check"></span>&nbsp;
									<h6><?php esc_html_e('Checkin Date : ', 'tour-booking-manager'); ?>&nbsp;</h6>
									<span><?php echo esc_html($hotel_info['ttbm_checkin_date']); ?></span>
								</li>
								<li>
									<span class="fas fa-calendar-times"></span>&nbsp;
									<h6><?php esc_html_e('Checkout Date : ', 'tour-booking-manager'); ?>&nbsp;</h6>
									<span><?php echo esc_html($hotel_info['ttbm_checkout_date']); ?></span>
								</li>
								<li>
									<span class="fas fa-stopwatch"></span>&nbsp;
									<h6><?php esc_html_e('Duration : ', 'tour-booking-manager'); ?>&nbsp;</h6>
									<span><?php echo esc_html($hotel_info['ttbm_hotel_num_of_day']); ?>&nbsp;<?php echo esc_html__('Days', 'tour-booking-manager'); ?></span>
								</li>
							<?php } else { ?>
								<li>
									<span class="far fa-calendar-alt"></span>&nbsp;&nbsp;
									<h6><?php echo esc_html($tour_name . ' ' . esc_html__('Date', 'tour-booking-manager')); ?> :&nbsp;</h6>
									<span><?php echo esc_html($date); ?></span>
								</li>
							<?php } ?>
						</ul>
					</div>
					<?php if (sizeof($ticket_type) > 0) { ?>
						<h5 class="mb_xs">
							<?php if (sizeof($hotel_info) > 0) { ?>
								<?php esc_html_e('Room List ', 'tour-booking-manager'); ?>
							<?php } else { ?>
								<?php esc_html_e('Ticket List ', 'tour-booking-manager'); ?>
							<?php } ?>
						</h5>
						<?php foreach ($ticket_type as $ticket) { ?>
							<div class="dLayout_xs">
								<ul class="cart_list">
									<?php if (sizeof($hotel_info) > 0) { ?>
										<li>
											<h6><?php esc_html_e('Room Name', 'tour-booking-manager'); ?> :&nbsp;</h6>
											<span>&nbsp; <?php echo esc_html($ticket['ticket_name']); ?></span>
										</li>
										<li>
											<h6><?php echo esc_html(TTBM_Function::ticket_qty_text()); ?> :&nbsp;</h6>
											<span><?php echo esc_html($ticket['ticket_qty']); ?></span>
										</li>
										<li>
											<h6><?php echo esc_html(TTBM_Function::ticket_price_text()); ?> :&nbsp;</h6>
											<span><?php echo ' ( ' . MP_Global_Function::wc_price($ttbm_id, $ticket['ticket_price']) . ' x ' . $ticket['ticket_qty'] . ' x ' . $hotel_info['ttbm_hotel_num_of_day'] . ') = ' . MP_Global_Function::wc_price($ttbm_id, ($ticket['ticket_price'] * $ticket['ticket_qty'] * $hotel_info['ttbm_hotel_num_of_day'])); ?></span>
										</li>
									<?php } else { ?>
										<li>
											<h6><?php echo esc_html(TTBM_Function::ticket_name_text()); ?> :&nbsp;</h6>
											<span><?php echo esc_html($ticket['ticket_name']); ?></span>
										</li>
										<li>
											<h6><?php echo esc_html(TTBM_Function::ticket_qty_text()); ?> :&nbsp;</h6>
											<span><?php echo esc_html($ticket['ticket_qty']); ?></span>
										</li>
										<li>
											<h6><?php echo esc_html(TTBM_Function::ticket_price_text()); ?> :&nbsp;</h6>
											<span><?php echo ' ( ' . MP_Global_Function::wc_price($ttbm_id, $ticket['ticket_price']) . ' x ' . $ticket['ticket_qty'] . ') = ' . MP_Global_Function::wc_price($ttbm_id, ($ticket['ticket_price'] * $ticket['ticket_qty'])); ?></span>
										</li>
									<?php } ?>
								</ul>
							</div>
						<?php } ?>
					<?php } ?>
					
					<?php if (sizeof($extra_service) > 0) { ?>
						<h5 class="mb_xs"><?php esc_html_e('Extra Services', 'tour-booking-manager'); ?></h5>
						<?php foreach ($extra_service as $service) { ?>
							<div class="dLayout_xs">
								<ul class="cart_list">
									<li>
										<h6><?php echo esc_html(TTBM_Function::service_name_text()); ?> :&nbsp;</h6>
										<span><?php echo esc_html($service['service_name']); ?></span>
									</li>
									<li>
										<h6><?php echo esc_html(TTBM_Function::service_qty_text()); ?> :&nbsp;</h6>
										<span><?php echo esc_html($service['service_qty']); ?></span>
									</li>
									<li>
										<h6><?php echo esc_html(TTBM_Function::service_price_text()); ?> :&nbsp;</h6>
										<span><?php echo ' ( ' . MP_Global_Function::wc_price($ttbm_id, $service['service_price']) . ' x ' . $service['service_qty'] . ') = ' . MP_Global_Function::wc_price($ttbm_id, ($service['service_price'] * $service['service_qty'])); ?></span>
									</li>
								</ul>
							</div>
						<?php } ?>
					<?php } ?>
					<?php do_action('ttbm_after_cart_item_display', $cart_item, $ttbm_id); ?>
				</div>
				<?php
			}
			/*********************/
			public static function get_cart_seat_price($post_id) {
				$start_place = MP_Global_Function::get_submit_info('wbtm_start_stops');
				$end_place = MP_Global_Function::get_submit_info('wbtm_end_stops');
				$seat_qty = MP_Global_Function::get_submit_info('wbtm_seat_qty', []);
				$passenger_type = MP_Global_Function::get_submit_info('passenger_type', []);
				$total_price = 0;
				$count = count($passenger_type);
				if ($count > 0) {
					for ($i = 0; $i < $count; $i++) {
						if ($seat_qty[$i] > 0) {
							$price = WBTM_Functions::get_seat_price($post_id, $start_place, $end_place, $passenger_type[$i]) * $seat_qty[$i];
							$total_price = $total_price + $price;
						}
					}
				}
				return $total_price;
			}
			public function get_cart_ex_service_price($post_id) {
				$total_price = 0;
				$service_name = MP_Global_Function::get_submit_info('extra_service_name', array());
				$service_qty = MP_Global_Function::get_submit_info('extra_service_qty', array());
				if (sizeof($service_name) > 0) {
					for ($i = 0; $i < count($service_name); $i++) {
						if ($service_qty[$i] > 0) {
							$name = $service_name[$i] ?? '';
							$price = WBTM_Functions::get_ex_service_price($post_id, $name) * $service_qty[$i];
							$total_price = $total_price + $price;
						}
					}
				}
				return $total_price;
			}
			public static function cart_ticket_info($post_id) {
				$start_place = MP_Global_Function::get_submit_info('wbtm_start_stops');
				$end_place = MP_Global_Function::get_submit_info('wbtm_end_stops');
				$start_date = MP_Global_Function::get_submit_info('wbtm_journey_date');
				$qty = MP_Global_Function::get_submit_info('wbtm_seat_qty', array());
				$passenger_type = MP_Global_Function::get_submit_info('passenger_type', []);
				$count = count($passenger_type);
				$ticket_info = [];
				if ($count > 0) {
					for ($i = 0; $i < count($passenger_type); $i++) {
						if ($qty[$i] > 0) {
							$name = $passenger_type[$i] ?? '';
							$ticket_info[$i]['ticket_name'] = $name;
							$ticket_info[$i]['ticket_price'] = WBTM_Functions::get_seat_price($post_id, $start_place, $end_place, $passenger_type[$i]);
							$ticket_info[$i]['ticket_qty'] = $qty[$i];
							$ticket_info[$i]['date'] = $start_date ?? '';
						}
					}
				}
				return apply_filters('wbtm_cart_ticket_info_data_prepare', $ticket_info, $post_id);
			}
			public static function cart_extra_service_info($post_id): array {
				$start_date = MP_Global_Function::get_submit_info('wbtm_journey_date');
				$service_name = MP_Global_Function::get_submit_info('extra_service_name', array());
				$service_qty = MP_Global_Function::get_submit_info('extra_service_qty', array());
				$extra_service = array();
				if (sizeof($service_name) > 0) {
					for ($i = 0; $i < count($service_name); $i++) {
						if ($service_qty[$i] > 0) {
							$name = $service_name[$i] ?? '';
							$extra_service[$i]['name'] = $name;
							$extra_service[$i]['price'] = WBTM_Functions::get_ex_service_price($post_id, $name);
							$extra_service[$i]['qty'] = $service_qty[$i];
							$extra_service[$i]['date'] = $start_date ?? '';
						}
					}
				}
				return $extra_service;
			}
			/*******************************************/
			public function get_item_data_($item_data, $cart_item) {
				if (!is_admin()) {
					global $wbtmmain;
					if (get_post_type($cart_item['bus_id']) === 'wbtm_bus') {
						$wbtm_seats = $cart_item['wbtm_seats'];
						$extra_bag_quantity = isset($cart_item['extra_bag_quantity']) ? $cart_item['extra_bag_quantity'] : 0;
						$passenger_info = $cart_item['wbtm_passenger_info'];
						$basic_passenger_info = $cart_item['wbtm_basic_passenger_info'];
						$wbtm_bus_seat_type = $cart_item['wbtm_bus_seat_type'];
						$extra_bag_price = get_post_meta($cart_item['bus_id'], 'wbtm_extra_bag_price', true);
						if (is_array($passenger_info) && sizeof($passenger_info) > 0) { // With Form builder
							$i = 0;
							foreach ($passenger_info as $_passenger) {
								?>
								<ul class=event-custom-price>
									<?php
										if (isset($_passenger['wbtm_user_name']) && $_passenger['wbtm_user_name'] != '') {
											?>
											<li>
												<strong><?php mage_bus_label('wbtm_cart_name_text', __('Name', 'bus-ticket-booking-with-seat-reservation')); ?> :</strong>
												<?php echo $_passenger['wbtm_user_name']; ?>
											</li>
											<?php
										}
										if (isset($_passenger['wbtm_user_email']) && $_passenger['wbtm_user_email'] != '') {
											?>
											<li>
												<strong><?php mage_bus_label('wbtm_cart_email_text', __('Email:', 'bus-ticket-booking-with-seat-reservation')); ?></strong>
												<?php echo $_passenger['wbtm_user_email']; ?>
											</li>
											<?php
										}
										if (isset($_passenger['wbtm_user_phone']) && $_passenger['wbtm_user_phone'] != '') {
											?>
											<li>
												<strong><?php mage_bus_label('wbtm_cart_phone_text', __('Phone:', 'bus-ticket-booking-with-seat-reservation')); ?></strong>
												<?php echo $_passenger['wbtm_user_phone']; ?>
											</li>
											<?php
										}
										if (isset($_passenger['wbtm_user_gender']) && $_passenger['wbtm_user_gender'] != '') {
											?>
											<li>
												<strong><?php mage_bus_label('wbtm_cart_gender_text', __('Gender:', 'bus-ticket-booking-with-seat-reservation')); ?></strong>
												<?php echo $_passenger['wbtm_user_gender']; ?>
											</li>
											<?php
										}
										if (isset($_passenger['wbtm_user_address'])) {
											?>
											<li>
												<strong><?php mage_bus_label('wbtm_cart_address_text', __('Address:', 'bus-ticket-booking-with-seat-reservation')); ?></strong>
												<?php echo $_passenger['wbtm_user_address']; ?>
											</li>
											<?php
										}
									?>
									
									<?php if (isset($wbtm_seats[$i]['wbtm_seat_name'])) : ?>
										<li>
											<strong><?php mage_bus_label('wbtm_seat_no_text', __('Seat No', 'bus-ticket-booking-with-seat-reservation')); ?>
												:
											</strong>
											<?php echo $wbtm_seats[$i]['wbtm_seat_name']; ?>
										</li>
									<?php endif; ?>
									<?php if (isset($basic_passenger_info[$i]['wbtm_passenger_type'])) { ?>
										<?php if ($basic_passenger_info[$i]['wbtm_passenger_type'] != '') { ?>
											<li>
												<strong><?php _e('Passenger Type', 'bus-ticket-booking-with-seat-reservation'); ?>
													:
												</strong>
												<?php echo wbtm_get_seat_type_label(strtolower($basic_passenger_info[$i]['wbtm_passenger_type']), $basic_passenger_info[$i]['wbtm_passenger_type']); ?>
											</li>
										<?php } ?>
									<?php } ?>
									<?php
										if ($cart_item['wbtm_billing_type'] != '') {
											$valid_till = mtsa_calculate_valid_date(mage_wp_date($cart_item['wbtm_journey_date'], 'Y-m-d'), $cart_item['wbtm_billing_type']);
											?>
											<li>
												<strong><?php _e(__('Start Date', 'bus-ticket-booking-with-seat-reservation')); ?>
													:
												</strong>
												<?php echo mage_wp_date($cart_item['wbtm_journey_date']); ?>
											</li>
											<li>
												<strong><?php _e('Valid Till', 'bus-ticket-booking-with-seat-reservation'); ?>
													:
												</strong>
												<?php echo mage_wp_date($valid_till); ?>
											</li>
											<li>
												<strong><?php _e('Billing Type', 'bus-ticket-booking-with-seat-reservation'); ?>
													:
												</strong>
												<?php echo ucwords($cart_item['wbtm_billing_type']); ?>
											</li>
											<?php
											if ($cart_item['wbtm_city_zone'] != '') {
												$term = get_term($cart_item['wbtm_city_zone'], 'mtsa_city_zone');
												?>
												<li>
													<strong><?php _e('Zone', 'bus-ticket-booking-with-seat-reservation'); ?>
														:
													</strong>
													<?php echo $term->name; ?>
												</li>
											<?php } else { ?>
												<li>
													<strong><?php mage_bus_label('wbtm_boarding_points_text', __('Boarding Point', 'bus-ticket-booking-with-seat-reservation')); ?>
														:
													</strong>
													<?php echo $cart_item['wbtm_start_stops']; ?>
												</li>
												<li>
													<strong><?php mage_bus_label('wbtm_dropping_points_text', __('Dropping Point', 'bus-ticket-booking-with-seat-reservation')); ?>
														:
													</strong>
													<?php echo $cart_item['wbtm_end_stops']; ?>
												</li>
											<?php }
										}
										else { ?>
											<li>
												<strong><?php mage_bus_label('wbtm_cart_journey_date_text', __('Journey Date', 'bus-ticket-booking-with-seat-reservation')); ?>
													:
												</strong>
												<?php echo mage_wp_date($cart_item['wbtm_journey_date']); ?>
											</li>
											<?php if ($cart_item['wbtm_journey_time']) : ?>
												<li>
													<strong><?php mage_bus_label('wbtm_start_time_text', __('Start Time', 'bus-ticket-booking-with-seat-reservation')); ?>
														:
													</strong>
													<?php echo mage_wp_time($cart_item['wbtm_journey_time']); ?>
												</li>
											<?php endif; ?>
											<li>
												<strong><?php mage_bus_label('wbtm_boarding_points_text', __('Boarding Point', 'bus-ticket-booking-with-seat-reservation')); ?>
													:
												</strong>
												<?php echo $cart_item['wbtm_start_stops']; ?>
											</li>
											<li>
												<strong><?php mage_bus_label('wbtm_dropping_points_text', __('Dropping Point', 'bus-ticket-booking-with-seat-reservation')); ?>
													:
												</strong>
												<?php echo $cart_item['wbtm_end_stops']; ?>
											</li>
										<?php }
									?>
									<?php if ($cart_item['wbtm_pickpoint']) : ?>
										<li>
											<strong><?php _e('Pickup Point', 'bus-ticket-booking-with-seat-reservation'); ?>:</strong>
											<?php echo $cart_item['wbtm_pickpoint']; ?>
										</li>
									<?php endif; ?>
									<?php if (isset($basic_passenger_info[$i]['wbtm_seat_fare'])) : ?>
										<li>
											<strong><?php mage_bus_label('wbtm_fare_text', __('Fare', 'bus-ticket-booking-with-seat-reservation')); ?>
												:
											</strong>
											<?php echo wc_price($basic_passenger_info[$i]['wbtm_seat_fare']); ?>
										</li>
									<?php endif; ?>
									<?php
										if (isset($_passenger['wbtm_extra_bag_qty'])) {
											if ($_passenger['wbtm_extra_bag_qty'] > 0) {
												?>
												<li>
													<strong><?php mage_bus_label('wbtm_extra_bag_text', __('Extra Bag', 'bus-ticket-booking-with-seat-reservation')); ?>:</strong>
													<?php echo $_passenger['wbtm_extra_bag_qty']; ?>
												</li>
												<li>
													<strong><?php mage_bus_label('wbtm_extra_bag_price_text', __('Extra Bag Price', 'bus-ticket-booking-with-seat-reservation')); ?>:</strong>
													<?php echo wc_price($_passenger['wbtm_extra_bag_price']); ?>
												</li>
												<li>
													<strong><?php mage_bus_label('wbtm_total_text', __('Total', 'bus-ticket-booking-with-seat-reservation')); ?>:</strong>
													<?php echo wc_price($basic_passenger_info[$i]['wbtm_seat_fare']) . ' + ' . wc_price($_passenger['wbtm_extra_bag_price']) . ' = ' . wc_price($basic_passenger_info[$i]['wbtm_seat_fare'] + $_passenger['wbtm_extra_bag_price']); ?>
												</li>
												<?php
											}
										}
									?>
								</ul>
								<?php
								if (($cart_item['is_return'] == 1) && ($cart_item['wbtm_seat_original_fare'] > $cart_item['wbtm_seat_return_fare'])) {
									$percent = ($cart_item['wbtm_seat_return_fare'] * 100) / $cart_item['wbtm_seat_original_fare'];
									$percent = 100 - $percent;
									echo '<p style="color:#af7a2d;font-size: 14px;line-height: 1em;"><strong>' . __('Congratulation!', 'bus-ticket-booking-with-seat-reservation') . '</strong> <span> ' . __('For a round trip, you got', 'bus-ticket-booking-with-seat-reservation') . ' <span style="font-weight:600">' . number_format($percent, 2) . '%</span> ' . __('discount on this trip', 'bus-ticket-booking-with-seat-reservation') . '</span></p>';
								}
								$i++;
							}
						}
						else {
							?>
							<ul class='event-custom-price'>
								<?php
									if ($basic_passenger_info && $wbtm_seats) : ?>
										<li>
											<?php echo $wbtmmain->bus_get_option('wbtm_seat_list_text', 'label_setting_sec') ? $wbtmmain->bus_get_option('wbtm_seat_list_text', 'label_setting_sec') . ': ' : __('Seat List:', 'bus-ticket-booking-with-seat-reservation');
												$seat_lists = array_column($basic_passenger_info, 'wbtm_passenger_type');
												if ($wbtm_bus_seat_type === 'wbtm_without_seat_plan') {
													if ($seat_lists) {
														$counted_seats_arr = array_count_values($seat_lists);
														if ($counted_seats_arr) {
															foreach ($counted_seats_arr as $seat_name => $count) {
																printf("%s (%d) ", $seat_name, $count);
															}
														}
													}
												}
												else { // Seat plan
													if ($wbtm_seats) {
														$seat_plan_loop_index = 0;
														foreach ($wbtm_seats as $seat_plan_seat) {
															if ($basic_passenger_info[$seat_plan_loop_index]['wbtm_passenger_type'] != '') {
																$separator = (count($wbtm_seats) - 1 == $seat_plan_loop_index) ? '' : ', ';
																printf("%s(%s)%s", $seat_plan_seat['wbtm_seat_name'], $basic_passenger_info[$seat_plan_loop_index]['wbtm_passenger_type'], $separator);
															}
															$seat_plan_loop_index++;
														}
													}
												}
											?>
										</li>
									<?php
									endif;
									if ($cart_item['wbtm_billing_type'] != '') :
										$valid_till = mtsa_calculate_valid_date(get_wbtm_datetime($cart_item['wbtm_journey_date'], 'date-text'), $cart_item['wbtm_billing_type']);
										?>
										<li><?php _e('Start Date: ', 'bus-ticket-booking-with-seat-reservation');
											?><?php echo $cart_item['wbtm_journey_date']; ?></li>
										<li><?php _e('Valid Till: ', 'bus-ticket-booking-with-seat-reservation');
											?><?php echo $valid_till; ?></li>
										<li><?php _e('Billing Type: ', 'bus-ticket-booking-with-seat-reservation');
											?><?php echo $cart_item['wbtm_billing_type']; ?></li>
										<?php if ($cart_item['wbtm_city_zone'] != '') :
										$term = get_term($cart_item['wbtm_city_zone'], 'mtsa_city_zone'); ?>
										<li><?php _e('Zone: ', 'bus-ticket-booking-with-seat-reservation');
											?><?php echo $term->name; ?></li>
									<?php else : ?>
										<li>hh<?php mage_bus_label('wbtm_boarding_points_text', __('Boarding Point', 'bus-ticket-booking-with-seat-reservation'));
											?><?php echo $cart_item['wbtm_start_stops']; ?></li>
										<li>hh<?php mage_bus_label('wbtm_dropping_points_text', __('Dropping Point', 'bus-ticket-booking-with-seat-reservation'));
											?><?php echo $cart_item['wbtm_end_stops']; ?></li>
									<?php endif; ?>
									<?php else : ?>
										<li><?php echo $wbtmmain->bus_get_option('wbtm_select_journey_date_text', 'label_setting_sec') ? $wbtmmain->bus_get_option('wbtm_select_journey_date_text', 'label_setting_sec') . ': ' : __('Journey Date:', 'bus-ticket-booking-with-seat-reservation');
											?><?php echo mage_wp_date($cart_item['wbtm_journey_date']); ?></li>
										<?php if ($cart_item['wbtm_journey_time']) : ?>
											<li><?php echo $wbtmmain->bus_get_option('wbtm_starting_text', 'label_setting_sec') ? $wbtmmain->bus_get_option('wbtm_starting_text', 'label_setting_sec') . ': ' : __('Journey Time:', 'bus-ticket-booking-with-seat-reservation');
												?><?php echo mage_wp_time($cart_item['wbtm_journey_time']); ?></li>
										<?php endif; ?>
										<li><?php echo $wbtmmain->bus_get_option('wbtm_boarding_points_text', 'label_setting_sec') ? $wbtmmain->bus_get_option('wbtm_boarding_points_text', 'label_setting_sec') . ': ' : __('Boarding Point:', 'bus-ticket-booking-with-seat-reservation');
											?><?php echo $cart_item['wbtm_start_stops']; ?></li>
										<li><?php echo $wbtmmain->bus_get_option('wbtm_dropping_points_text', 'label_setting_sec') ? $wbtmmain->bus_get_option('wbtm_dropping_points_text', 'label_setting_sec') . ': ' : __('Dropping Point:', 'bus-ticket-booking-with-seat-reservation'); ?><?php echo $cart_item['wbtm_end_stops']; ?>
										</li>
									<?php endif; ?>
								
								<?php if ($cart_item['wbtm_bus_no']) : ?>
									<li><?php _e('Bus No', 'bus-ticket-booking-with-seat-reservation'); ?>: <?php echo $cart_item['wbtm_bus_name']; ?> - <?php echo $cart_item['wbtm_bus_no']; ?></li>
								<?php endif; ?>
								
								<?php if ($cart_item['wbtm_pickpoint']) : ?>
									<li><?php _e('Pickup Point', 'bus-ticket-booking-with-seat-reservation'); ?>: <?php echo $cart_item['wbtm_pickpoint']; ?></li>
								<?php endif; ?>
								<?php if ($basic_passenger_info) : ?>
									<li>
										<?php mage_bus_label('wbtm_fare_text', __('Fare', 'bus-ticket-booking-with-seat-reservation')); ?>
										:
										<?php
											$bpi_index = 0;
											$bpi_total_price = 0;
											foreach ($basic_passenger_info as $bpi) {
												if (isset($bpi['wbtm_seat_fare'])) {
													$bpi_total_price += $bpi['wbtm_seat_fare'];
												}
												$bpi_index++;
											}
											echo wc_price($bpi_total_price);
										?>
									</li>
								<?php endif; ?>
								
								
								
								<?php if ($extra_bag_quantity > 0) { ?>
									<li><?php _e('Extra Bag: ', 'bus-ticket-booking-with-seat-reservation');
											echo '(' . $cart_item['extra_bag_quantity'] . ' x ' . $extra_bag_price . ') = ' . wc_price($cart_item['extra_bag_quantity'] * $extra_bag_price); ?>
									</li>
								<?php } ?>
							</ul>
							<?php
							if (($cart_item['is_return'] == 1) && ($cart_item['wbtm_seat_original_fare'] > $cart_item['wbtm_seat_return_fare'])) {
								$percent = ($cart_item['wbtm_seat_return_fare'] * 100) / $cart_item['wbtm_seat_original_fare'];
								$percent = 100 - $percent;
								echo '<p style="color:#af7a2d;font-size: 14px;line-height: 1em;"><strong>' . __('Congratulation!', 'bus-ticket-booking-with-seat-reservation') . '</strong> <span> ' . __('For a round trip, you got', 'bus-ticket-booking-with-seat-reservation') . ' <span style="font-weight:600">' . number_format($percent, 2) . '%</span> ' . __('discount on this trip', 'bus-ticket-booking-with-seat-reservation') . '</span></p>';
							}
						}
						// }
						?>
						<?php
						if ($cart_item['extra_services']) : ?>
							<p style="margin:0">
								<strong><?php _e('Extra Services', 'bus-ticket-booking-with-seat-reservation') ?></strong>
							</p>
							<ul style="margin:0">
								<?php foreach ($cart_item['extra_services'] as $service) : ?>
									<li><?php echo __($service['name'], 'bus-ticket-booking-with-seat-reservation') . ' - ' . wc_price($service["price"]) . ' x ' . $service['qty'] . ' = ' . wc_price($service["price"] * $service['qty']);; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php
						endif;
					}
					return $item_data;
				}
			}
			public function after_checkout_validation($posted) {
				global $woocommerce, $wbtmmain;
				$items = $woocommerce->cart->get_cart();
				foreach ($items as $item => $values) {
					if (get_post_type($values['bus_id']) == 'wbtm_bus') {
						$wbtm_seats = $values['wbtm_seats'];
						$wbtm_start_stops = $values['wbtm_start_stops'];
						$wbtm_end_stops = $values['wbtm_end_stops'];
						$wbtm_journey_date = $values['wbtm_journey_date'];
						$wbtm_bus_id = $values['wbtm_bus_id'];
						$se = $wbtm_seats[0]['wbtm_seat_name'];
						$is_booked = mage_partial_seat_booked_count(false, $se, $wbtm_bus_id, $wbtm_start_stops, $wbtm_end_stops, $wbtm_journey_date);
						$bus_type = get_post_meta($wbtm_bus_id, 'wbtm_seat_type_conf', true);
						if ($bus_type == 'wbtm_seat_plan') {
							if ($is_booked['has_booked']) {
								WC()->cart->empty_cart();
								wc_add_notice(__("Sorry, Your Selected Seat Already Booked by another user", 'woocommerce'), 'error');
							}
						}
						if ($bus_type == 'wbtm_without_seat_plan') {
							$total_seat = get_post_meta($wbtm_bus_id, 'wbtm_total_seat', true);
							if ($total_seat <= $is_booked) {
								WC()->cart->empty_cart();
								wc_add_notice(__("Sorry, Your Selected Seat Already Booked by another user", 'woocommerce'), 'error');
							}
						}
					}
				}
			}
			public function checkout_create_order_line_item($item, $cart_item_key, $values, $order) {
				$eid = $values['bus_id'];
				if (get_post_type($eid) == 'wbtm_bus') {
					$wbtm_seats = $values['wbtm_seats'];
					$wbtm_passenger_info = $values['wbtm_passenger_info'];
					$wbtm_basic_passenger_info = $values['wbtm_basic_passenger_info'];
					$wbtm_billing_type = $values['wbtm_billing_type'];
					$wbtm_city_zone = $values['wbtm_city_zone'];
					$wbtm_pickpoint = $values['wbtm_pickpoint'];
					$wbtm_bus_no = get_the_title($eid) . (($values['wbtm_bus_no']) ? (' - ' . $values['wbtm_bus_no']) : '');
					$extra_services = $values['extra_services'];
					$wbtm_start_stops = $values['wbtm_start_stops'];
					$wbtm_end_stops = $values['wbtm_end_stops'];
					$wbtm_journey_date = $values['wbtm_journey_date'];
					$wbtm_journey_time = $values['wbtm_journey_time'];
					$wbtm_bus_id = $values['wbtm_bus_id'];
					$wbtm_is_return = $values['is_return'];
					$extra_bag_quantity = isset($values['extra_bag_quantity']) ? $values['extra_bag_quantity'] : null;
					$wbtm_tp = $values['wbtm_tp'];
					$seat = "";
					foreach ($wbtm_seats as $field) {
						// $item->add_meta_data( __( esc_attr($field['wbtm_seat_name'])));
						$seat .= $field['wbtm_seat_name'] . ",";
					}
					// Extra Services
					if ($extra_services) {
						$extra_service_html = '';
						$extra_service_i = 0;
						foreach ($extra_services as $extra_item) {
							if ($extra_item > 0) {
								$name = isset($extra_item['name']) ? $extra_item['name'] : '';
								$qty = isset($extra_item['qty']) ? $extra_item['qty'] : 0;
								$price = isset($extra_item['price']) ? $extra_item['price'] : 0;
								$extra_service_html .= '(' . ($extra_service_i + 1) . '). ' . $name . ' - ' . $qty . ' x ' . $price . '= ' . ($qty * $price) . '   ';
							}
							$extra_service_i++;
						}
					}
					else {
						$extra_service_html = '';
					}
					// .$seat =0;
					$item->add_meta_data('Seats', $seat);
					$item->add_meta_data('Start', $wbtm_start_stops);
					$item->add_meta_data('End', $wbtm_end_stops);
					$item->add_meta_data('Date', $wbtm_journey_date);
					$item->add_meta_data('Time', $wbtm_journey_time);
					$item->add_meta_data('Extra Services', $extra_service_html);
					$item->add_meta_data('_wbtm_tp', $wbtm_tp);
					$item->add_meta_data('_bus_id', $wbtm_bus_id);
					$item->add_meta_data('_wbtm_passenger_info', $wbtm_passenger_info);
					$item->add_meta_data('_wbtm_basic_passenger_info', $wbtm_basic_passenger_info);
					$item->add_meta_data('_wbtm_billing_type', $wbtm_billing_type);
					$item->add_meta_data('_wbtm_city_zone', $wbtm_city_zone);
					$item->add_meta_data('_wbtm_pickpoint', $wbtm_pickpoint);
					$item->add_meta_data('_wbtm_bus_no', $wbtm_bus_no);
					$item->add_meta_data('_extra_services', $extra_services);
					$item->add_meta_data('_wbtm_bus_id', $eid);
				}
			}
			public function after_order_notes($checkout) {
				$get_settings = get_option('wbtm_bus_settings');
				$get_val = isset($get_settings['custom_fields']) ? $get_settings['custom_fields'] : '';
				$output = $get_val ? $get_val : null;
				if ($output) {
					$get_custom_fields_arr = explode(',', $output);
					if ($get_custom_fields_arr) {
						echo '<div id="custom_checkout_field"><h2>' . __('Additional field') . '</h2>';
						foreach ($get_custom_fields_arr as $item) {
							$item = trim($item);
							$name = ucfirst(str_replace('_', ' ', $item));
							woocommerce_form_field('wbtm_custom_field_' . $item, array('type' => 'text', 'class' => array('my-field-class form-row-wide'), 'label' => __($name), 'placeholder' => __($name),), $checkout->get_value('wbtm_custom_field_' . $item));
						}
						echo '</div>';
					}
				}
			}
			public function checkout_update_order_meta($order_id) {
				$order = wc_get_order($order_id);
				foreach ($order->get_items() as $item_id => $item_obj) {
					$has_extra_service = wc_get_order_item_meta($item_id, '_extra_services');
					if ($has_extra_service) {
						update_post_meta($order_id, '_extra_services', $has_extra_service);
					}
				}
				$get_settings = get_option('wbtm_bus_settings');
				$get_val = isset($get_settings['custom_fields']) ? $get_settings['custom_fields'] : '';
				$output = $get_val ? $get_val : null;
				if ($output) {
					$get_custom_fields_arr = explode(',', $output);
					if ($get_custom_fields_arr) {
						foreach ($get_custom_fields_arr as $item) {
							$item = trim($item);
							if (!empty($_POST['wbtm_custom_field_' . $item])) {
								update_post_meta($order_id, 'wbtm_custom_field_' . $item, sanitize_text_field($_POST['wbtm_custom_field_' . $item]));
							}
						}
					}
				}
			}
			public function order_item_display_meta_value($value, $meta, $item) {
				if ('Date' === $meta->key) {
					$value = mage_wp_date($value);
				}
				return $value;
			}
			public function after_order_itemmeta($item_id, $item, $_product) {
				?>
				<style type="text/css">
					.th__title {
						text-transform: capitalize;
						display: inline-block;
						min-width: 140px;
						font-weight: 700
					}
					ul.mage_passenger_list {
						border: 1px solid #ddd;
						padding: 20px;
						margin-bottom: 20px;
						width: 100%;
						border-radius: 3px;
					}
					ul.mage_passenger_list li {
						border-bottom: 1px dashed #ddd;
						padding: 5px 0 10px;
						color: #888;
					}
					ul.mage_passenger_list li h3 {
						padding: 0;
						margin: 0;
						color: #555;
					}
				</style>
				<?php
				$passenger_data = wc_get_order_item_meta($item_id, '_wbtm_passenger_info', true);
				if ($passenger_data) {
					$counter = 0;
					if (!empty($passenger_data)) {
						foreach ($passenger_data as $key => $value) {
							echo '<ul class="mage_passenger_list">';
							echo "<li><h3>" . __('Passenger', 'bus-ticket-booking-with-seat-reservation') . ": " . ($counter + 1) . "</h3></li>";
							echo (isset($value['wbtm_user_name'])) ? "<li><span class='th__title'>" . __('Name', 'bus-ticket-booking-with-seat-reservation') . ":</span> $value[wbtm_user_name]</li>" : "";
							echo (isset($value['wbtm_user_email'])) ? "<li><span class='th__title'>" . __('Email', 'bus-ticket-booking-with-seat-reservation') . ":</span> $value[wbtm_user_email]</li>" : "";
							echo (isset($value['wbtm_user_phone'])) ? "<li><span class='th__title'>" . __('Phone', 'bus-ticket-booking-with-seat-reservation') . ":</span> $value[wbtm_user_phone]</li>" : "";
							echo (isset($value['wbtm_user_gender'])) ? "<li><span class='th__title'>" . __('Gender', 'bus-ticket-booking-with-seat-reservation') . ":</span> $value[wbtm_user_gender]</li>" : "";
							echo (isset($value['wbtm_extra_bag_qty'])) ? "<li><span class='th__title'>" . __('Extra Bag', 'bus-ticket-booking-with-seat-reservation') . ":</span> $value[wbtm_extra_bag_qty]</li>" : null;
							echo '</ul>';
							$counter++;
						}
					}
				}
				else {
					if (isset($_GET['post'])) {
						$order_meta = get_post_meta($_GET['post']);
						echo '<ul class="mage_passenger_list">';
						echo "<li><h3>" . __('Passenger', 'bus-ticket-booking-with-seat-reservation') . "</h3></li>";
						echo ($order_meta['_billing_first_name'][0]) ? "<li><span class='th__title'>" . __('Name', 'bus-ticket-booking-with-seat-reservation') . ":</span>" . $order_meta['_billing_first_name'][0] . ' ' . $order_meta['_billing_last_name'][0] . "</li>" : "";
						echo ($order_meta['_billing_email'][0]) ? "<li><span class='th__title'>" . __('Email', 'bus-ticket-booking-with-seat-reservation') . ":</span>" . $order_meta['_billing_email'][0] . "</li>" : "";
						echo ($order_meta['_billing_phone'][0]) ? "<li><span class='th__title'>" . __('Phone', 'bus-ticket-booking-with-seat-reservation') . ":</span>" . $order_meta['_billing_phone'][0] . "</li>" : "";
						echo '</ul>';
					}
				}
			}
			public function bus_order_processed($order_id) {
				$order = wc_get_order($order_id);
				$order_meta = get_post_meta($order_id);
				$order_status = $order->get_status();
				if ($order_status != 'failed') {
					foreach ($order->get_items() as $item_id => $item_values) {
						$wbtm_bus_id = MP_Global_Function::get_order_item_meta($item_id, '_wbtm_bus_id');
						if (get_post_type($wbtm_bus_id) == 'wbtm_bus') {
							$user_id = $order_meta['_customer_user'][0];
							$user_info_arr = MP_Global_Function::get_order_item_meta($item_id, '_wbtm_passenger_info');
							$user_basic_info_arr = maybe_unserialize(MP_Global_Function::get_order_item_meta($item_id, '_wbtm_basic_passenger_info'));
							$wbtm_billing_type = MP_Global_Function::get_order_item_meta($item_id, '_wbtm_billing_type');
							$wbtm_city_zone = MP_Global_Function::get_order_item_meta($item_id, '_wbtm_city_zone');
							$wbtm_pickpoint = MP_Global_Function::get_order_item_meta($item_id, '_wbtm_pickpoint');
							$extra_services = MP_Global_Function::get_order_item_meta($item_id, '_extra_services');
							$seat = MP_Global_Function::get_order_item_meta($item_id, 'Seats');
							$start = MP_Global_Function::get_order_item_meta($item_id, 'Start');
							$end = MP_Global_Function::get_order_item_meta($item_id, 'End');
							$j_date = MP_Global_Function::get_order_item_meta($item_id, 'Date');
							$j_time = MP_Global_Function::get_order_item_meta($item_id, 'Time');
							$bus_id = MP_Global_Function::get_order_item_meta($item_id, '_bus_id');
							$calculated_fare = MP_Global_Function::get_order_item_meta($item_id, '_wbtm_tp');
							$seats = ($seat) ? explode(",", $seat) : null;
							$usr_inf = unserialize($user_info_arr);
							$counter = 0;
							$next_stops = maybe_serialize(wbtm_get_all_stops_after_this($bus_id, $start, $end));
							$extra_bag_price = get_post_meta($bus_id, 'wbtm_extra_bag_price', true) ? get_post_meta($bus_id, 'wbtm_extra_bag_price', true) : 0;
							$add_datetime = date("Y-m-d h:i:s");
							if ($seats) {
								foreach ($seats as $_seats) {
									// $fare = $this->wbtm_get_bus_price($start, $end, $price_arr, $usr_inf[$counter]['wbtm_passenger_type_num']);
									if (!empty($_seats)) {
										$fare = $user_basic_info_arr[$counter]['wbtm_seat_fare'];
										// echo $user_name;
										// die;
										if (isset($usr_inf[$counter]['wbtm_extra_bag_qty'])) {
											$wbtm_extra_bag_qty = $usr_inf[$counter]['wbtm_extra_bag_qty'];
											$fare = $fare + ($extra_bag_price * $wbtm_extra_bag_qty);
										}
										else {
											$wbtm_extra_bag_qty = 0;
											$extra_bag_price = 0;
										}
										// Extra Service with seat
										$extra_services_arr = maybe_unserialize($extra_services);
										// if($extra_services_arr) {
										//     foreach($extra_services_arr as $service) {
										//         $fare += $service['price'] * $service['qty'];
										//     }
										// }
										create_bus_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, '', $j_time, $_seats, $fare, $j_date, $add_datetime, '', '', '', '', '', '', '', $wbtm_extra_bag_qty, $extra_bag_price, $usr_inf, $counter, 3, $order_meta, $wbtm_billing_type, $wbtm_city_zone, $wbtm_pickpoint, $extra_services_arr, '', $calculated_fare);
									}
									$counter++;
								}
							}
							else { // Only Extra Service
								$fare = 0;
								$extra_services_arr = maybe_unserialize($extra_services);
								if ($extra_services_arr) {
									foreach ($extra_services_arr as $service) {
										$fare += $service['price'] * $service['qty'];
									}
								}
								if (isset($usr_inf[$counter]['wbtm_extra_bag_qty'])) {
									$wbtm_extra_bag_qty = $usr_inf[$counter]['wbtm_extra_bag_qty'];
									$fare = $fare + ($extra_bag_price * $wbtm_extra_bag_qty);
								}
								else {
									$wbtm_extra_bag_qty = 0;
									$extra_bag_price = 0;
								}
								create_bus_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, '', $j_time, null, $fare, $j_date, $add_datetime, '', '', null, null, '', '', '', $wbtm_extra_bag_qty, $extra_bag_price, $usr_inf, $counter, 3, $order_meta, $wbtm_billing_type, $wbtm_city_zone, $wbtm_pickpoint, $extra_services_arr, $calculated_fare);
							}
						}
					}
				}
			}
			public function wbtm_bus_ticket_seat_management($order_id, $from_status, $to_status, $order) {
				global $wpdb;
				// Getting an instance of the order object
				$order = wc_get_order($order_id);
				$order_meta = get_post_meta($order_id);
				# Iterating through each order items (WC_Order_Item_Product objects in WC 3+)
				foreach ($order->get_items() as $item_id => $item_values) {
					$item_id = $item_id;
					$wbtm_bus_id = MP_Global_Function::get_order_item_meta($item_id, '_wbtm_bus_id');
					if (get_post_type($wbtm_bus_id) == 'wbtm_bus') {
						$bus_id = MP_Global_Function::get_order_item_meta($item_id, '_bus_id');
						if ($order->has_status('on-hold')) {
							$status = 4;
							$this->update_bus_seat_status($order_id, $bus_id, $status);
						}
						if ($order->has_status('pending')) {
							$status = 3;
							$this->update_bus_seat_status($order_id, $bus_id, $status);
						}
						if ($order->has_status('processing')) {
							$status = 1;
							$this->update_bus_seat_status($order_id, $bus_id, $status);
						}
						if ($order->has_status('cancelled')) {
							$status = 5;
							$this->update_bus_seat_status($order_id, $bus_id, $status);
						}
						if ($order->has_status('refunded')) {
							$status = 6;
							$this->update_bus_seat_status($order_id, $bus_id, $status);
						}
						if ($order->has_status('failed')) {
							$status = 7;
							$this->update_bus_seat_status($order_id, $bus_id, $status);
						}
						if ($order->has_status('completed')) {
							$status = 2;
							$this->update_bus_seat_status($order_id, $bus_id, $status);
						}
					}
				}
			}
			public function update_bus_seat_status($order_id, $bus_id, $status) {
				$args = array(
					'post_type' => 'wbtm_bus_booking',
					'posts_per_page' => -1,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => 'wbtm_bus_id',
							'value' => $bus_id,
							'compare' => '=',
						),
						array(
							'key' => 'wbtm_order_id',
							'value' => $order_id,
							'compare' => '=',
						),
					),
				);
				$q = new WP_Query($args);
				foreach ($q->posts as $bus) {
					# code...
					update_post_meta($bus->ID, 'wbtm_status', $status);
				}
			}
		}
		new WBTM_Woocommerce();
	}
	function outdated_item_remove() {
		global $woocommerce;
		$has_outdate = false;
		$items = $woocommerce->cart->get_cart();
		if ($items) {
			$buffer_time = mage_bus_setting_value('bus_buffer_time');
			$buffer_time_sec = ($buffer_time && is_numeric($buffer_time)) ? $buffer_time * 60 * 60 : 0;
			$current = current_time('Y-m-d H:i');
			$c_str = strtotime($current);
			foreach ($items as $key => $value) {
				if (isset($value['wbtm_bus_id'])) {
					$j_datetime = $value["wbtm_journey_date"] . " " . ($value["wbtm_journey_time"] ? $value["wbtm_journey_time"] : '23:59');
					$j_str = strtotime($j_datetime) - $buffer_time_sec; // journey time in seconds less buffer
					if ($c_str > $j_str) {
						$woocommerce->cart->remove_cart_item($key);
						$has_outdate = true;
					}
				}
			}
		}
		return ($has_outdate ? $woocommerce->cart->get_cart() : $items);
	}
	add_action('template_redirect', 'wbtm_cart_item_have_two_way_route', 10);
// Main Function
	function wbtm_cart_item_have_two_way_route() {
		global $woocommerce;
		$settings = get_option('wbtm_bus_settings');
		$val = isset($settings['bus_return_discount']) ? $settings['bus_return_discount'] : 'no';
		$is_return_discount_enable = $val ? $val : 'no';
		if ($is_return_discount_enable == 'no') {
			return false;
		}
		if (is_cart() || is_checkout()) {
			$items = outdated_item_remove(); // Remove outdated item
			$count_have_return = 0;
			if ($items) {
				$item_count = count($items);
				foreach ($items as $key => $value) {
					// echo $key.' ----> '. $value['is_return'].'<br>';
					if ($value['is_return'] && $item_count == 1) { // If cart item is single and has return route
						wbtm_update_cart_return_price($key, true); // Update Return Price to original
					}
					elseif (($value['is_return'] == 1 || $value['is_return'] == 2 || $value['is_return'] == '') && $item_count > 1) { // If cart item is more than 1 and has return route
						$start = $value['wbtm_start_stops'];
						$stop = $value['wbtm_end_stops'];
						$j_date = $value['wbtm_journey_date'];
						$has_one_way = wbtm_check_has_one_way($start, $stop, $j_date);
						//var_dump($has_one_way);
						if (!$has_one_way) {
							wbtm_update_cart_return_price($key, true); // Update Return Price to original
						}
						else {
							$count_have_return++;
							if (($count_have_return % 2) == 0) { // Only single return route get discount (One way and return way) nothing else
								wbtm_update_cart_return_price($key, false); // Update Return Price to return
							}
							else {
								wbtm_update_cart_return_price($key, true); // Update Return Price to original
							}
						}
					}
					else {
						// Nothing to do!
					}
				}
			}
		}
	}
// Check One way route is exits or not
	function wbtm_check_has_one_way($start, $stop, $j_date) {
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();
		$return = null;
		foreach ($items as $key => $value) {
			if (($start == $value['wbtm_end_stops']) && ($stop == $value['wbtm_start_stops'])) {
				$return = 1;
				break;
			}
			else {
				$return = 0;
			}
		}
		return $return;
	}
// Update Return Price
	function wbtm_update_cart_return_price($key, $return, $recall = false) {
		$cart = WC()->cart->cart_contents;
		if ($return) {
			foreach ($cart as $id => $cart_item) {
				if ($id == $key) {
					$ticket_price = $cart_item['wbtm_seat_original_fare'];
					$extra_service = extra_price($cart_item['extra_services']);
					$any_date_return_price = $ticket_price;
					$total_price = $ticket_price + $extra_service;
					$cart_item['line_subtotal'] = $total_price;
					$cart_item['wbtm_tp'] = $total_price;
					$cart_item['line_total'] = $total_price;
					$cart_item['is_return'] = 2;
					WC()->cart->cart_contents[$key] = $cart_item;
					break;
				}
			}
		}
		else {
			foreach ($cart as $id => $cart_item) {
				if ($id == $key) {
					$ticket_price = $cart_item['wbtm_seat_return_fare'];
					$extra_service = extra_price($cart_item['extra_services']);
					$any_date_return_price = $ticket_price;
					$total_price = $ticket_price + $extra_service;
					$cart_item['line_subtotal'] = $total_price;
					$cart_item['wbtm_tp'] = $total_price;
					$cart_item['line_total'] = $ticket_price;
					$cart_item['is_return'] = 1;
					WC()->cart->cart_contents[$key] = $cart_item;
					if (!$recall) {
						$this_start = $cart_item['wbtm_start_stops'];
						$this_stop = $cart_item['wbtm_end_stops'];
					}
					break;
				}
			}
			if (isset($this_start) && isset($this_stop)) {
				foreach ($cart as $id => $cart_item) {
					if ($this_start == $cart_item['wbtm_end_stops'] && $this_stop == $cart_item['wbtm_start_stops']) {
						wbtm_update_cart_return_price($id, false, true);
					}
				}
			}
		}
		WC()->cart->set_session(); // Finaly Update Cart
	}
	function create_bus_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, $b_time, $j_time, $_seats = null, $fare = null, $j_date = null, $add_datetime = null, $user_name = null, $user_email = null, $passenger_type = null, $passenger_type_num = null, $user_phone = null, $user_gender = null, $user_address = null, $wbtm_extra_bag_qty = null, $extra_bag_price = null, $usr_inf = null, $counter = null, $status = null, $order_meta = null, $wbtm_billing_type = null, $city_zone = null, $wbtm_pickpoint = null, $extra_services = array(), $user_additional = null, $calculated_fare = null) {
		$add_datetime = current_time("Y-m-d") . ' ' . mage_wp_time(current_time("H:i"));
		$name = '#' . $order_id . get_the_title($bus_id);
		$new_post = array(
			'post_title' => $name,
			'post_content' => '',
			'post_category' => array(),
			'tags_input' => array(),
			'post_status' => 'publish',
			'post_type' => 'wbtm_bus_booking',
		);
		//SAVE THE POST
		$pid = wp_insert_post($new_post);
		update_post_meta($pid, 'wbtm_order_id', $order_id);
		update_post_meta($pid, 'wbtm_bus_id', $bus_id);
		update_post_meta($pid, 'wbtm_user_id', $user_id);
		update_post_meta($pid, 'wbtm_boarding_point', $start);
		update_post_meta($pid, 'wbtm_next_stops', $next_stops);
		update_post_meta($pid, 'wbtm_droping_point', $end);
		update_post_meta($pid, 'wbtm_bus_start', $b_time);
		update_post_meta($pid, 'wbtm_user_start', $j_time);
		update_post_meta($pid, 'wbtm_seat', $_seats);
		update_post_meta($pid, 'wbtm_bus_fare', $fare);
		update_post_meta($pid, 'wbtm_journey_date', $j_date);
		update_post_meta($pid, 'wbtm_booking_date', $add_datetime);
		update_post_meta($pid, 'wbtm_status', $status);
		update_post_meta($pid, 'wbtm_ticket_status', 1);
		update_post_meta($pid, 'wbtm_user_name', $user_name);
		update_post_meta($pid, 'wbtm_user_email', $user_email);
		update_post_meta($pid, 'wbtm_user_phone', $user_phone);
		update_post_meta($pid, 'wbtm_user_gender', $user_gender);
		update_post_meta($pid, 'wbtm_user_address', $user_address);
		update_post_meta($pid, 'wbtm_user_extra_bag', $wbtm_extra_bag_qty);
		update_post_meta($pid, 'wbtm_user_extra_bag_price', $extra_bag_price);
		update_post_meta($pid, 'wbtm_passenger_type', $passenger_type);
		update_post_meta($pid, 'wbtm_passenger_type_num', $passenger_type_num);
		update_post_meta($pid, 'wbtm_billing_type', $wbtm_billing_type);
		update_post_meta($pid, 'wbtm_city_zone', $city_zone);
		update_post_meta($pid, 'wbtm_pickpoint', $wbtm_pickpoint);
		update_post_meta($pid, 'wbtm_user_additional', $user_additional);
		update_post_meta($pid, '_wbtm_tp', $calculated_fare);
		if ($wbtm_billing_type && $j_date && function_exists('mtsa_calculate_valid_date')) {
			$sub_end_date = mtsa_calculate_valid_date($j_date, $wbtm_billing_type);
			update_post_meta($pid, 'wbtm_sub_end_date', $sub_end_date);
		}
		if (!empty($extra_services)) {
			foreach ($extra_services as $service) {
				update_post_meta($pid, 'extra_services_type_qty_' . $service['name'], $service['qty']);
				update_post_meta($pid, 'extra_services_type_price_' . $service['name'], $service['price']);
			}
		}
		update_post_meta($pid, 'wbtm_extra_services', maybe_serialize($extra_services));
		// Custom Field
		if ($order_meta) {
			$get_custom_fields = WBTM_Functions::get_settings('custom_fields', 0);
			if ($get_custom_fields) {
				$get_custom_fields_arr = explode(',', $get_custom_fields);
				if ($get_custom_fields_arr) {
					foreach ($get_custom_fields_arr as $item) {
						if (isset($order_meta[$item][0])) {
							update_post_meta($pid, 'wbtm_custom_field_' . $item, $order_meta[$item][0]);
						}
						else {
							update_post_meta($pid, 'wbtm_custom_field_' . $item, null);
						}
					}
				}
			}
		}
	}
	function wbtm_get_all_stops_after_this($bus_id, $val, $end) {
		//echo $end;
		$end_s = array($val);
		//Getting All boarding points
		$boarding_points = maybe_unserialize(get_post_meta($bus_id, 'wbtm_bus_bp_stops', true));
		$all_bp_stops = array();
		foreach ($boarding_points as $_boarding_points) {
			$all_bp_stops[] = $_boarding_points['wbtm_bus_bp_stops_name'];
		}
		$pos2 = array_search($end, $all_bp_stops);
		// if (sizeof($pos2) > 0) {
		if ($pos2 != '') {
			unset($all_bp_stops[$pos2]);
		}
		// print_r($all_bp_stops);
		// echo '<br/>';
		//Gettings Stops Name Before Droping Stops
		$start_stops = maybe_unserialize(get_post_meta($bus_id, 'wbtm_bus_next_stops', true));
		$all_stops = array();
		if (is_array($start_stops) && sizeof($start_stops) > 0) {
			foreach ($start_stops as $_start_stops) {
				$all_stops[] = $_start_stops['wbtm_bus_next_stops_name'];
			}
		}
		$full_array = $all_stops;
		$mkey = array_search($end, $full_array);
		$newarray = array_slice($full_array, $mkey, count($full_array), true);
		// return $newarray;
		$myArrayInit = $full_array; //<-- Your actual array
		$offsetKey = $mkey; //<--- The offset you need to grab
		//Lets do the code....
		$n = array_keys($myArrayInit); //<---- Grab all the keys of your actual array and put in another array
		$count = array_search($offsetKey, $n); //<--- Returns the position of the offset from this array using search
		$new_arr = array_slice($myArrayInit, 0, $count + 1, true); //<--- Slice it with the 0 index as start and position+1 as the length parameter.
		$pos2 = array_search($end, $new_arr);
		// if (sizeof($pos2) > 0) {
		if ($pos2 != '') {
			unset($new_arr[$pos2]);
		}
		$res = array_merge($all_bp_stops, $new_arr);
		return $res;
		// print_r();
	}
	