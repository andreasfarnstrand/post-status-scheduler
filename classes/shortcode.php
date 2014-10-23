<?php

	/**
	 * Shortcode
	 * 
	 * Wrapper class for Scheduler shortcodes
	 * 
	 * @author Andreas Färnstrand <andreas@farnstranddev.se>
	 */

	namespace post_status_scheduler;

	class Shortcode {

		public function __construct() {

			add_shortcode( 'pss_scheduled_time', array( $this, 'scheduled_time' ) );

		}


		/**
		 * scheduled_time
		 * 
		 * shortcode logic for pss_scheduled_time
		 * 
		 * @param array $arguments
		 * @return string
		 */
		public function scheduled_time( $arguments ) {

			$args = shortcode_atts( array(
        'post_id' => 'id'
    	), $arguments );

			$result = '';

			if( !empty( $args['post_id'] ) ) {

				$post_id = (int) esc_attr( $args['post_id'] );

				// Try to get a scheduled date and time
				$scheduled_date = get_post_meta( $post_id, 'scheduler_date', true );

				// If there is a scheduled date and time - format it according to site settings
				if( !empty( $scheduled_date ) ) {

					$date_format = get_option( 'date_format' );
					$time_format = get_option( 'time_format' );
					$result = date_i18n( "{$date_format} {$time_format}", $scheduled_date );

				}

			}

			return $result;

		}

	}


?>