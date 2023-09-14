<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	$start_route = $start_route ?? '';
	$end_route = $end_route ?? '';
	$date = $date ?? '';
	$label = WBTM_Functions::get_name();
	$bus_ids = WBTM_Query::get_bus_id($start_route, $end_route);
	if (sizeof($bus_ids) > 0) {
		?>
		<div class="wbtm_bus_list_area">
			<input type="hidden" name="wbtm_start_route" value="<?php echo esc_attr($start_route); ?>"/>
			<input type="hidden" name="wbtm_end_route" value="<?php echo esc_attr($end_route); ?>"/>
			<input type="hidden" name="wbtm_date" value="<?php echo esc_attr(date('Y-m-d', strtotime($date))); ?>"/>
			<table>
				<thead>
				<tr>
					<th><?php esc_html_e('Image', 'bus-ticket-booking-with-seat-reservation'); ?></th>
					<th colspan="4">
						<div class="flexEqual">
							<span><?php echo esc_html($label) . ' ' . esc_html__('Name', 'bus-ticket-booking-with-seat-reservation'); ?></span>
							<span><?php echo WBTM_Functions::get_settings('wbtm_schedule_text', esc_html__('Schedule', 'bus-ticket-booking-with-seat-reservation')); ?></span>
						</div>
					</th>
					<th colspan="4" class="_textCenter">
						<div class="flexEqual">
							<span><?php echo WBTM_Functions::get_settings('wbtm_type_text', esc_html__('Coach Type', 'bus-ticket-booking-with-seat-reservation')); ?></span>
							<span>
								<?php echo WBTM_Functions::get_settings('wbtm_fare_text', esc_html__('Fare', 'bus-ticket-booking-with-seat-reservation')); ?>
								<sub><?php echo '/' . WBTM_Functions::get_settings('wbtm_seat_text', esc_html__('Seat', 'bus-ticket-booking-with-seat-reservation')); ?></sub>
							</span>
							<span><?php echo WBTM_Functions::get_settings('wbtm_seats_available_text', esc_html__('Available', 'bus-ticket-booking-with-seat-reservation')); ?></span>
							<span><?php echo WBTM_Functions::get_settings('wbtm_view_text', esc_html__('Action', 'bus-ticket-booking-with-seat-reservation')); ?></span>
						</div>
					</th>
				</tr>
				</thead>
				<tbody class="_bgWhite">
				<?php foreach ($bus_ids as $bus_id) { ?>
					<?php
					$all_info = WBTM_Functions::get_bus_all_info($bus_id, $date, $start_route, $end_route);
					//echo '<pre>';print_r($bp_array);echo '</pre>';
					if (sizeof($all_info) > 0) {
						$row_price = $all_info['price'];
						$price = MP_Global_Function::wc_price($bus_id, $row_price);
						$view_text = WBTM_Functions::get_settings('wbtm_view_seats_text', esc_html__('View Seats', 'bus-ticket-booking-with-seat-reservation'));
						?>
						<tr class="bus_item_row">
							<td><?php MP_Custom_Layout::bg_image($bus_id); ?></td>
							<td colspan="4">
								<div class="flexEqual">
									<div>
										<h6 class="_textTheme" data-href="<?php echo esc_attr(get_the_permalink($bus_id)); ?>"><?php echo get_the_title($bus_id); ?></h6>
										<small class="dBlock"><?php echo esc_html(MP_Global_Function::get_post_info($bus_id, 'wbtm_bus_no')); ?></small>
									</div>
									<div class="_fdColumn">
										<h6>
											<span class="fa fa-angle-double-right"></span>
											<?php echo esc_html($all_info['bp']) . ' ' . esc_html($all_info['bp_time'] ? ' (' . MP_Global_Function::date_format($all_info['bp_time'], 'full') . ' )' : ''); ?>
										</h6>
										<h6>
											<span class="fa fa-stop"></span>
											<?php echo esc_html($all_info['dp']) . ' ' . esc_html($all_info['dp_time'] ? ' (' . MP_Global_Function::date_format($all_info['dp_time'], 'full') . ' )' : ''); ?>
										</h6
									</div>
								</div>
							</td>
							<td colspan="4" class="_textCenter">
								<div class="flexEqual">
									<h6><?php echo WBTM_Functions::get_bus_type($bus_id); ?></h6>
									<div>
										<strong><?php echo MP_Global_Function::esc_html($price); ?></strong>
									</div>
									<h6> <?php echo esc_html($all_info['available_seat']); ?>/<?php echo esc_html($all_info['total_seat']); ?> </h6>
									<div class="_allCenter">
										<button type="button" class="_dButton_xs" id="get_wbtm_bus_details"
											data-bus_id="<?php echo esc_attr($bus_id); ?>"
											data-open-text="<?php echo esc_attr($view_text); ?>"
											data-close-text="<?php esc_attr_e('Close Seat', 'bus-ticket-booking-with-seat-reservation'); ?>"
											data-add-class="mActive"
										>
											<span data-text><?php echo esc_html($view_text); ?></span>
										</button>
									</div>
								</div>
							</td>
						</tr>
						<tr data-row_id="<?php echo esc_attr($bus_id); ?>">
							<td colspan="9" class="wbtm_bus_details"></td>
						</tr>
					<?php } ?>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<?php
	}
	else {
		?>
		<div class="divider"></div>
		<div class="_dLayout_bgWarning">
			<h3><?php echo WBTM_Functions::get_settings('wbtm_no_bus_found_text', esc_html__('No Bus Found!', 'bus-ticket-booking-with-seat-reservation')); ?></h3>
		</div>
		<?php
	}
//echo '<pre>';	print_r($bus_ids);	echo '</pre>';