<?php
/**
 * Class responsible for scheduling and un-scheduling events (cron jobs).
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Class responsible for scheduling and un-scheduling events (cron jobs).
 *
 * This class defines all code necessary to schedule and un-schedule cron jobs.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class ACS_Voucher_For_Woocommerce_Cron {

	const ACS_VOUCHER_FOR_WOOCOMMERCE_CRON_HOOK = 'acs_voucher_auto_close';
	const ACS_VOUCHER_FOR_WOOCOMMERCE_CHECK_STATUS = 'acs_voucher_check_status';

	/**
	 * Check if already scheduled, and schedule if not.
	 */
	public static function schedule() {
		if ( ! self::next_scheduled_daily() ) {
			self::daily_schedule();
		}
		if ( ! self::next_scheduled_hourly() ) {
			self::hourly_schedule();
		}else {
			self::hourly_schedule(true);
		}
	}

	/**
	 * Unschedule.
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::ACS_VOUCHER_FOR_WOOCOMMERCE_CRON_HOOK );
		wp_clear_scheduled_hook( self::ACS_VOUCHER_FOR_WOOCOMMERCE_CHECK_STATUS );
	}

	/**
	 * @return false|int Returns false if not scheduled, or timestamp of next run.
	 */
	private static function next_scheduled_daily() {
		return wp_next_scheduled( self::ACS_VOUCHER_FOR_WOOCOMMERCE_CRON_HOOK );
	}

	/**
	 * @return false|int Returns false if not scheduled, or timestamp of next run.
	 */
	private static function next_scheduled_hourly() {
		return wp_next_scheduled( self::ACS_VOUCHER_FOR_WOOCOMMERCE_CHECK_STATUS );
	}

	/**
	 * Create new schedule.
	 */
	private static function daily_schedule() {
		$auto_close_time=get_option('webexpert_acs_auto_close','20:00:00');
		if(version_compare(get_bloginfo('version'),'5.3', '>=') )
			$datetime=new DateTime($auto_close_time,wp_timezone());
		else
			$datetime=new DateTime($auto_close_time);

		wp_schedule_event( $datetime->getTimestamp(), 'daily', self::ACS_VOUCHER_FOR_WOOCOMMERCE_CRON_HOOK );
	}

	/**
	 * Create new schedule.
	 */
	private static function hourly_schedule($reschedule=false) {
		if(version_compare(get_bloginfo('version'),'5.3', '>=') )
			$datetime=new DateTime('now',wp_timezone());
		else
			$datetime=new DateTime('now');

		if ($reschedule) {
			wp_reschedule_event($datetime->getTimestamp(), "twicedaily", self::ACS_VOUCHER_FOR_WOOCOMMERCE_CHECK_STATUS);
		}else {
			wp_schedule_event( $datetime->getTimestamp(), 'twicedaily', self::ACS_VOUCHER_FOR_WOOCOMMERCE_CHECK_STATUS );
		}
	}
}