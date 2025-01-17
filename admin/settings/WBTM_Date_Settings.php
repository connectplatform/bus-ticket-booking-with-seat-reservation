<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('WBTM_Date_Settings')) {
		class WBTM_Date_Settings {
			public function __construct() {
				add_action('add_wbtm_settings_tab_content', [$this, 'tab_content']);
				add_action('wbtm_settings_save', [$this, 'settings_save']);
			}
			public function tab_content($post_id) {
				$date_format = MP_Global_Function::date_picker_format();
				$now = date_i18n($date_format, strtotime(current_time('Y-m-d')));
				$date_type = MP_Global_Function::get_post_info($post_id, 'show_operational_on_day', 'no');
				/*********************/
				$repeated_start_date = MP_Global_Function::get_post_info($post_id, 'wbtm_repeated_start_date');
				$hidden_repeated_start_date = $repeated_start_date ? date('Y-m-d', strtotime($repeated_start_date)) : '';
				$visible_repeated_start_date = $repeated_start_date ? date_i18n($date_format, strtotime($repeated_start_date)) : '';
				$repeated_end_date = MP_Global_Function::get_post_info($post_id, 'wbtm_repeated_end_date');
				$hidden_repeated_end_date = $repeated_end_date ? date('Y-m-d', strtotime($repeated_end_date)) : '';
				$visible_repeated_end_date = $repeated_end_date ? date_i18n($date_format, strtotime($repeated_end_date)) : '';
				$repeated_after = MP_Global_Function::get_post_info($post_id, 'wbtm_repeated_after', 1);
				$active_days = MP_Global_Function::get_post_info($post_id, 'wbtm_active_days');
				/******************************/
				$off_days = MP_Global_Function::get_post_info($post_id, 'wbtm_off_days');
				$off_day_array = $off_days?explode(',', $off_days):[];
				$days = MP_Global_Function::week_day();
				?>
				<div class="tabsItem" data-tabs="#wbtm_settings_date">
					<h5><?php esc_html_e('Date Settings', 'bus-ticket-booking-with-seat-reservation'); ?></h5>
					<div class="divider"></div>
					<div class="_dLayout_xs_mp_zero">
						<div class="_bgColor_2_padding_xs">
							<label class="max_700">
								<span class="max_300">
									<?php esc_html_e('Bus Operation Date Type', 'bus-ticket-booking-with-seat-reservation'); ?>
									<i class="textRequired">&nbsp;*</i>
								</span>
								<select class="formControl" name="show_operational_on_day" data-collapse-target required>
									<option disabled selected><?php esc_html_e('Please select ...', 'bus-ticket-booking-with-seat-reservation'); ?></option>
									<option value="yes" data-option-target="#mp_particular" <?php echo esc_attr($date_type == 'yes' ? 'selected' : ''); ?>><?php esc_html_e('Particular', 'bus-ticket-booking-with-seat-reservation'); ?></option>
									<option value="no" data-option-target="#mp_repeated" <?php echo esc_attr($date_type == 'no' ? 'selected' : ''); ?>><?php esc_html_e('Repeated', 'bus-ticket-booking-with-seat-reservation'); ?></option>
								</select>
							</label>
							<?php WBTM_Settings::info_text('show_operational_on_day'); ?>
						</div>
						<div class="_padding_xs <?php echo esc_attr($date_type == 'yes' ? 'mActive' : ''); ?>" data-collapse="#mp_particular">
							<div class="dFlex">
								<span class="_max_300_fs_label"><?php esc_html_e('Particular Dates', 'bus-ticket-booking-with-seat-reservation'); ?></span>
								<div class="mp_settings_area max_400">
									<div class="mp_item_insert mp_sortable_area">
										<?php
											$particular_date_lists = MP_Global_Function::get_post_info($post_id, 'wbtm_particular_dates', array());
											if (sizeof($particular_date_lists)) {
												foreach ($particular_date_lists as $particular_date) {
													if ($particular_date) {
														$this->particular_date_item('wbtm_particular_dates[]', $particular_date);
													}
												}
											}
										?>
									</div>
									<?php MP_Custom_Layout::add_new_button(esc_html__('Add New Particular date', 'bus-ticket-booking-with-seat-reservation')); ?>
									<div class="mp_hidden_content">
										<div class="mp_hidden_item">
											<?php $this->particular_date_item('wbtm_particular_dates[]'); ?>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="<?php echo esc_attr($date_type == 'no' ? 'mActive' : ''); ?>" data-collapse="#mp_repeated">
							<div class="_padding_xs">
								<label class="max_700">
									<span class="max_300"><?php esc_html_e('Repeated Start Date', 'bus-ticket-booking-with-seat-reservation'); ?></span>
									<input type="hidden" name="wbtm_repeated_start_date" value="<?php echo esc_attr($hidden_repeated_start_date); ?>"/>
									<input type="text" readonly name="" class="formControl date_type" value="<?php echo esc_attr($visible_repeated_start_date); ?>" placeholder="<?php echo esc_attr($now); ?>"/>
								</label>
							</div>
							<div class="_bgColor_2_padding_xs">
								<label class="max_700">
									<span class="max_300"><?php esc_html_e('Repeated End Date', 'bus-ticket-booking-with-seat-reservation'); ?></span>
									<input type="hidden" name="wbtm_repeated_end_date" value="<?php echo esc_attr($hidden_repeated_end_date); ?>"/>
									<input type="text" readonly name="" class="formControl date_type" value="<?php echo esc_attr($visible_repeated_end_date); ?>" placeholder="<?php echo esc_attr($now); ?>"/>
								</label>
							</div>
							<div class="_padding_xs">
								<label class="max_700">
									<span class="max_300"><?php esc_html_e('Repeated after', 'bus-ticket-booking-with-seat-reservation'); ?></span>
									<input type="text" name="wbtm_repeated_after" class="formControl mp_number_validation" value="<?php echo esc_attr($repeated_after); ?>"/>
								</label>
							</div>
							<div class="_bgColor_2_padding_xs">
								<label class="max_700">
									<span class="max_300"><?php esc_html_e('Maximum advanced day booking', 'bus-ticket-booking-with-seat-reservation'); ?></span>
									<input type="text" name="wbtm_active_days" class="formControl mp_number_validation" value="<?php echo esc_attr($active_days); ?>"/>
								</label>
							</div>
							<div class="_padding_xs">
								<div class="dFlex">
									<span class="_max_300_fs_label"><?php esc_html_e('Off Day', 'bus-ticket-booking-with-seat-reservation'); ?></span>
									<div class="groupCheckBox flexWrap">
										<input type="hidden" name="wbtm_off_days" value="<?php echo esc_attr($off_days); ?>"/>
										<?php foreach ($days as $key => $day) { ?>
											<label class="customCheckboxLabel min_200">
												<input type="checkbox" <?php echo esc_attr(in_array($key, $off_day_array) ? 'checked' : ''); ?> data-checked="<?php echo esc_attr($key); ?>"/>
												<span class="customCheckbox"><?php echo esc_html($day); ?></span>
											</label>
										<?php } ?>
									</div>
								</div>
							</div>
							<div class="_bgColor_2_padding_xs">
								<div class="dFlex">
									<span class="_max_300_fs_label"><?php esc_html_e('Off Dates', 'bus-ticket-booking-with-seat-reservation'); ?></span>
									<div class="mp_settings_area max_400">
										<div class="mp_item_insert mp_sortable_area">
											<?php
												$off_day_lists = MP_Global_Function::get_post_info($post_id, 'wbtm_off_dates', array());
												if (sizeof($off_day_lists)) {
													foreach ($off_day_lists as $off_day) {
														if ($off_day) {
															$this->particular_date_item('wbtm_off_dates[]', $off_day);
														}
													}
												}
											?>
										</div>
										<?php MP_Custom_Layout::add_new_button(esc_html__('Add New Off date', 'bus-ticket-booking-with-seat-reservation')); ?>
										<div class="mp_hidden_content">
											<div class="mp_hidden_item">
												<?php $this->particular_date_item('wbtm_off_dates[]'); ?>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="_padding_xs">
								<div class="dFlex">
									<span class="_max_300_fs_label"><?php esc_html_e('Off Dates in Range', 'bus-ticket-booking-with-seat-reservation'); ?></span>
									<div class="mp_settings_area _fullWidth">
										<div class="mp_item_insert mp_sortable_area">
											<?php
												$off_day_schedules = MP_Global_Function::get_post_info($post_id, 'wbtm_offday_schedule', array());
												if (sizeof($off_day_schedules)) {
													foreach ($off_day_schedules as $off_day_schedule) {
														if (sizeof($off_day_schedule) > 0 && $off_day_schedule['from_date'] && $off_day_schedule['to_date']) {
															$this->off_day_range($off_day_schedule['from_date'], $off_day_schedule['to_date']);
														}
													}
												}
											?>
										</div>
										<?php MP_Custom_Layout::add_new_button(esc_html__('Add New Off date range', 'bus-ticket-booking-with-seat-reservation')); ?>
										<div class="mp_hidden_content">
											<div class="mp_hidden_item">
												<?php $this->off_day_range(); ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function particular_date_item($name, $date = '') {
				?>
				<div class="mp_remove_area">
					<div class="justifyBetween">
						<?php $this->date_item_without_year($name, $date); ?>
						<?php MP_Custom_Layout::move_remove_button(); ?>
					</div>
					<div class="divider"></div>
				</div>
				<?php
			}
			public function off_day_range($from_date = '', $to_date = '') {
				?>
				<div class="mp_remove_area">
					<div class="justifyBetween">
						<?php $this->date_item_without_year('wbtm_from_date[]', $from_date); ?>
						<?php $this->date_item_without_year('wbtm_to_date[]', $to_date); ?>
						<?php MP_Custom_Layout::move_remove_button(); ?>
					</div>
					<div class="divider"></div>
				</div>
				<?php
			}
			public function date_item_without_year($name, $date = '') {
				$year = current_time('Y');
				$date = $date ? date('Y-m-d', strtotime($year . '-' . $date)) : '';
				$date_format = MP_Global_Function::date_picker_format_without_year();
				$now = date_i18n($date_format, strtotime(current_time('m-d')));
				$hidden_date = $date ? date('m-d', strtotime($date)) : '';
				$visible_date = $date ? date_i18n($date_format, strtotime($date)) : '';
				?>
				<label class="_fullWidth_mR">
					<input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($hidden_date); ?>"/>
					<input value="<?php echo esc_attr($visible_date); ?>" class="formControl date_type_without_year" placeholder="<?php echo esc_attr($now); ?>"/>
				</label>
				<?php
			}
			/*************************************/
			public function settings_save($post_id) {
				if (get_post_type($post_id) == WBTM_Functions::get_cpt()) {
					//************************************//
					$date_type = MP_Global_Function::get_submit_info('show_operational_on_day', 'no');
					update_post_meta($post_id, 'show_operational_on_day', $date_type);
					//**********************//
					$particular_dates = MP_Global_Function::get_submit_info('wbtm_particular_dates', array());
					$particular = array();
					if (sizeof($particular_dates) > 0) {
						foreach ($particular_dates as $particular_date) {
							if ($particular_date) {
								$particular[] = $particular_date;
							}
						}
					}
					update_post_meta($post_id, 'wbtm_particular_dates', array_unique($particular));
					//*************************//
					$repeated_start_date = MP_Global_Function::get_submit_info('wbtm_repeated_start_date');
					$repeated_start_date = $repeated_start_date ? date('Y-m-d', strtotime($repeated_start_date)) : '';
					update_post_meta($post_id, 'wbtm_repeated_start_date', $repeated_start_date);
					//**********************//
					$repeated_end_date = MP_Global_Function::get_submit_info('wbtm_repeated_end_date');
					$repeated_end_date = $repeated_end_date ? date('Y-m-d', strtotime($repeated_end_date)) : '';
					update_post_meta($post_id, 'wbtm_repeated_end_date', $repeated_end_date);
					//**********************//
					$repeated_after = MP_Global_Function::get_submit_info('wbtm_repeated_after', 1);
					update_post_meta($post_id, 'wbtm_repeated_after', $repeated_after);
					$active_days = MP_Global_Function::get_submit_info('wbtm_active_days');
					update_post_meta($post_id, 'wbtm_active_days', $active_days);
					//**********************//
					$off_days = MP_Global_Function::get_submit_info('wbtm_off_days', array());
					update_post_meta($post_id, 'wbtm_off_days', $off_days);
					//**********************//
					$off_dates = MP_Global_Function::get_submit_info('wbtm_off_dates', array());
					$_off_dates = array();
					if (sizeof($off_dates) > 0) {
						foreach ($off_dates as $off_date) {
							if ($off_date) {
								$_off_dates[] = $off_date;
							}
						}
					}
					update_post_meta($post_id, 'wbtm_off_dates', $_off_dates);
					//**********************//
					$off_schedules = [];
					$from_dates = MP_Global_Function::get_submit_info('wbtm_from_date', array());
					$to_dates = MP_Global_Function::get_submit_info('wbtm_to_date', array());
					if (sizeof($from_dates) > 0) {
						foreach ($from_dates as $key => $from_date) {
							if ($from_date && $to_dates[$key]) {
								$off_schedules[] = [
									'from_date' => $from_date,
									'to_date' => $to_dates[$key],
								];
							}
						}
					}
					update_post_meta($post_id, 'wbtm_offday_schedule', $off_schedules);
				}
			}
		}
		new WBTM_Date_Settings();
	}