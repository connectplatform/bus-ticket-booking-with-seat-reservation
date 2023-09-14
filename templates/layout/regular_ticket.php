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
	$total_seat = $total_seat??WBTM_Functions::get_total_seat($post_id);
	$total_sold = $total_sold??WBTM_Query::query_total_booked($post_id, $start_route, $end_route, $date);
	$available_seat = $available_seat??$total_seat - $total_sold;
?>
	<table class="_layoutFixed_textCenter">
		<thead>
		<tr>
			<th><?php esc_html_e('Ticket', 'bus-ticket-booking-with-seat-reservation'); ?></th>
			<th><?php esc_html_e('Quantity', 'bus-ticket-booking-with-seat-reservation'); ?></th>
			<th><?php esc_html_e('Price', 'bus-ticket-booking-with-seat-reservation'); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($ticket_infos as $ticket_info) { ?>
			<?php
			$price=MP_Global_Function::wc_price($post_id,$ticket_info['price']);
			$row_price=MP_Global_Function::price_convert_raw($price);
			?>
			<tr>
				<th><?php echo esc_html($ticket_info['name']); ?></th>
				<td>
					<input type="hidden" name="passenger_type[]" value="<?php echo esc_attr($ticket_info['type']); ?>">
					<?php MP_Custom_Layout::qty_input('wbtm_seat_qty[]',$row_price,$available_seat,0,0,$available_seat); ?>
				</td>
				<th><?php echo wc_price($row_price); ?></th>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php