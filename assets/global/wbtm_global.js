//==================================================Search area==================//
(function ($) {
	"use strict";
	$(document).on("click", "#wbtm_area button.wbtm_next_date", function () {
		let date = $(this).data('date');
		let parent = $(this).closest('#wbtm_area');
		let name = $(this).closest('#wbtm_return_container').length > 0 ? 'r_date' : 'j_date';
		parent.find('input[name=' + name + ']').val(date).promise().done(function () {
			parent.find('.wbtm_get_bus_list').trigger('click');
		});
	});
	$(document).on("change", "div.wbtm_search_area .wbtm_start_point input.formControl", function () {
		let current = $(this);
		let start_route = current.val();
		let parent = current.closest('.wbtm_search_area');
		let target = parent.find('.wbtm_dropping_point');
		parent.find('.wbtm_dropping_point .mp_input_select_list').remove();
		target.find('input.formControl').val('');
		dLoader_xs(parent);
		let exit_route = 0;
		parent.find('.wbtm_start_point .mp_input_select_list li').each(function () {
			let current_route = $(this).data('value');
			if (current_route === start_route) {
				exit_route = 1;
			}
		}).promise().done(function () {
			if (exit_route > 0) {
				let post_id = parent.find('[name="wbtm_post_id"]').val();
				$.ajax({
					type: 'POST',
					url: mp_ajax_url,
					data: {
						"action": "get_wbtm_dropping_point",
						"start_route": start_route,
						"post_id": post_id,
					},
					success: function (data) {
						target.append(data).promise().done(function () {
							dLoaderRemove(parent);
							target.find('input.formControl').trigger('click');
						});
					},
					error: function (response) {
						console.log(response);
					}
				});
			} else {
				dLoaderRemove(parent);
				mp_alert(target);
				current.val('').trigger('click');
			}
		});
		//alert(start_route);
	});
}(jQuery));
//====================================================================//
(function ($) {
	"use strict";
	$(document).on("click", "#get_wbtm_bus_details", function () {
		let parent = $(this).closest('.wbtm_bus_list_area');
		let post_id = $(this).attr('data-bus_id');
		let target = parent.find('tr[data-row_id=' + post_id + ']').find('td.wbtm_bus_details');
		if ($(this).hasClass('mActive')) {
			target.find('>div').slideUp('fast');
			mp_all_content_change($(this));
		} else {
			parent.find('#get_wbtm_bus_details.mActive').each(function () {
				$(this).trigger('click');
			});
			if (target.find('>div').length > 0) {
				target.find('>div').slideDown('fast');
			} else {
				let start = parent.find('input[name="wbtm_start_route"]').val();
				let end = parent.find('input[name="wbtm_end_route"]').val();
				let date = parent.find('input[name="wbtm_date"]').val();
				if (start && end && date && post_id) {
					$.ajax({
						type: 'POST',
						url: mp_ajax_url,
						data: {
							"action": "get_wbtm_bus_details",
							"start_route": start,
							"end_route": end,
							"post_id": post_id,
							"date": date,
						},
						beforeSend: function () {
							dLoader(parent);
						},
						success: function (data) {
							target.html(data);
							dLoaderRemove(parent);
						},
						error: function (response) {
							console.log(response);
						}
					});
				}
			}
			mp_all_content_change($(this));
		}
	});
}(jQuery));
//====================================================================//
(function ($) {
	"use strict";
	function wbtm_price_calculation(parent) {
		let target_summary = parent.find('.wbtm_total');
		let total = 0;
		let total_qty = 0;
		parent.find('[name="wbtm_seat_qty[]"]').each(function () {
			let qty = parseInt($(this).val());
			let price = $(this).attr('data-price');
			price = price && price >= 0 ? price : 0;
			total = total + parseFloat(price) * qty;
			total_qty = total_qty + qty;
		}).promise().done(function () {
			if (total_qty > 0) {
				parent.find('.wbtm_ex_service_area').slideDown('fast');
				parent.find('.wbtm_form_submit_area').slideDown('fast');
				parent.find('[name="extra_service_qty[]"]').each(function () {
					let ex_qty = parseInt($(this).val());
					let ex_price = $(this).attr('data-price');
					ex_price = ex_price && ex_price >= 0 ? ex_price : 0;
					total = total + parseFloat(ex_price) * ex_qty;
				}).promise().done(function () {
					target_summary.html(mp_price_format(total));
				});
			} else {
				parent.find('.wbtm_ex_service_area').slideUp('fast');
				parent.find('.wbtm_form_submit_area').slideUp('fast');
				target_summary.html(mp_price_format(total));
			}
		});
	}
	$(document).on('change', '.wbtm_registration_area [name="wbtm_seat_qty[]"]', function () {
		let parent = $(this).closest('.wbtm_registration_area');
		wbtm_price_calculation(parent);
	});
	$(document).on('change', '.wbtm_registration_area [name="extra_service_qty[]"]', function () {
		let parent = $(this).closest('.wbtm_registration_area');
		wbtm_price_calculation(parent);
	});
}(jQuery));