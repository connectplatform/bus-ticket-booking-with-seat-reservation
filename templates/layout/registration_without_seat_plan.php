<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	$post_id = $post_id ?? MP_Global_Function::data_sanitize($_POST['post_id']);
	$start_route = $start_route ?? MP_Global_Function::data_sanitize($_POST['start_route']);
	$end_route = $end_route ?? MP_Global_Function::data_sanitize($_POST['end_route']);
	$date = $_POST['date'] ?? '';
	if ($post_id > 0 && $start_route && $end_route && $date) {
		$total_seat = WBTM_Functions::get_total_seat($post_id);
		$total_sold = WBTM_Query::query_total_booked($post_id, $start_route, $end_route, $date);
		$available_seat = $total_seat - $total_sold;
		if ($available_seat > 0) {
			$link_wc_product=MP_Global_Function::get_post_info($post_id,'link_wc_product');
			$ticket_infos = WBTM_Functions::get_ticket_info($post_id, $start_route, $end_route);
			$seat_price = WBTM_Functions::get_seat_price($post_id, $start_route, $end_route);
			$bp_time = WBTM_Functions::get_time($post_id, $start_route);
			$dp_time = WBTM_Functions::get_time($post_id, $end_route, true);
			$bp_full_date=date('Y-m-d', strtotime($date.' '.$bp_time));
			
			?>
			<div class="_infoLayout_mZero">
				<form action="" method="post" class="wbtm_registration_area">
					<input type="hidden" name='wbtm_journey_date' value='<?php echo esc_attr($bp_full_date); ?>'/>
					<input type="hidden" name='wbtm_start_stops' value="<?php echo esc_attr($start_route); ?>"/>
					<input type='hidden' name='wbtm_end_stops' value='<?php echo esc_attr($end_route); ?>'/>
					<input type="hidden" name="bus_id" value="<?php echo esc_attr($post_id); ?>"/>
					<div class="mpRow">
						<div class="_dLayout_mZero col_6">
							<?php require WBTM_Functions::template_path('layout/bus_info.php'); ?>
						</div>
						<div class="_dLayout_mZero col_6">
							<?php if (sizeof($ticket_infos) > 0) { ?>
								<?php require WBTM_Functions::template_path('layout/regular_ticket.php'); ?>
								<?php require WBTM_Functions::template_path('layout/extra_service.php'); ?>
								<div class="divider"></div>
								<div class="justifyBetween">
									<h4><?php esc_html_e('Total : ', 'bus-ticket-booking-with-seat-reservation'); ?></h4>
									<h4 class="wbtm_total"><?php echo wc_price(0); ?></h4>
								</div>
							<?php } else { ?>
								<div class="_bgWarning">
									<h4 class="_textCenter">
										<?php esc_html_e('No Ticket found !', 'bus-ticket-booking-with-seat-reservation'); ?>
									</h4>
								</div>
							<?php } ?>
						</div>
						<div class="_dLayout_mZero col_12 wbtm_form_submit_area">
							<div class="justifyBetween">
								<div></div>
								<button type="submit" class="_navy_blueButton" name="add-to-cart" value="<?php echo esc_attr($link_wc_product); ?>">
									<?php echo WBTM_Functions::get_settings('wbtm_book_now_text', esc_html__('Book Now ', 'bus-ticket-booking-with-seat-reservation')); ?>
								</button>
							</div>
						</div>
					</div>
				</form>
			</div>
			<?php
		}
		else {
			?>
			<div class="_dLayout_bgWarning">
				<h3><?php esc_html_e('No Seat Available !', 'bus-ticket-booking-with-seat-reservation'); ?></h3>
			</div>
			<?php
		}
	}