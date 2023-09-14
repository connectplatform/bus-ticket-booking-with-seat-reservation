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
	$ticket_infos = $ticket_infos??WBTM_Functions::get_ticket_info($post_id, $start_route, $end_route);
	$seat_price = $seat_price??WBTM_Functions::get_seat_price($post_id, $start_route, $end_route);
	$bp_time = $bp_time??WBTM_Functions::get_time($post_id, $start_route);
	$dp_time = $dp_time??WBTM_Functions::get_time($post_id, $end_route, true);
?>
	<table class="_layoutFixed">
		<tbody>
		<tr>
			<th>
				<span class="fas fa-map-marker-alt"></span>
				<?php echo WBTM_Functions::get_settings('wbtm_boarding_points_text', esc_html__('Boarding : ', 'bus-ticket-booking-with-seat-reservation')); ?>
			</th>
			<td><?php echo esc_html($start_route) . ' ' . esc_html($bp_time ? ' (' . MP_Global_Function::date_format($bp_time, 'time') . ' )' : ''); ?></td>
		</tr>
		<tr>
			<th>
				<span class="fas fa-map-marker-alt"></span>
				<?php echo WBTM_Functions::get_settings('wbtm_dropping_points_text', esc_html__('Dropping : ', 'bus-ticket-booking-with-seat-reservation')); ?>
			</th>
			<td><?php echo esc_html($end_route) . ' ' . esc_html($dp_time ? ' (' . MP_Global_Function::date_format($dp_time, 'time') . ' )' : ''); ?></td>
		</tr>
		<tr>
			<th>
				<span class="fa fa-calendar"></span>
				<?php echo WBTM_Functions::get_settings('wbtm_date_text', esc_html__('Date : ', 'bus-ticket-booking-with-seat-reservation')); ?>
			</th>
			<td><?php echo MP_Global_Function::date_format($date); ?></td>
		</tr>
		<tr>
			<th>
				<span class="fas fa-bus"></span>
				<?php echo WBTM_Functions::get_settings('wbtm_type_text', esc_html__('Coach Type : ', 'bus-ticket-booking-with-seat-reservation')); ?>
			</th>
			<td><?php echo WBTM_Functions::get_bus_type($post_id); ?></td>
		</tr>
		<tr>
			<th>
				<span class="fas fa-money-bill"></span>
				<?php echo WBTM_Functions::get_settings('wbtm_fare_text', esc_html__('Fare : ', 'bus-ticket-booking-with-seat-reservation')); ?>
			</th>
			<td>
				<?php echo wc_price($seat_price); ?>
				<small>/<?php esc_html_e('Seat', 'bus-ticket-booking-with-seat-reservation'); ?></small>
			</td>
		</tr>
		</tbody>
	</table>
<?php