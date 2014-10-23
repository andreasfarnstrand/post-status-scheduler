<?php


	/**
	 * Email
	 * 
	 * This class is a wrapper class for the Emails being sent
	 * by the scheduler.
	 * 
	 * @author Andreas Färnstrand <andreas@farnstranddev.se>
	 */

	namespace post_status_scheduler;

	class Email {

		public static function update_notification( $post ) {

			// Get the email of the post author
      $author_email = get_the_author_meta( 'user_email', $post->post_author );
      // Apply filters
      $author_email = apply_filters( 'post_status_scheduler_email_notification_recipient_email', $author_email );

      // Set email subject
      $email_subject = __( 'Post Status Scheduler update', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . ': "' . $post->post_title . '"';
      // Apply filters
      $email_subject = apply_filters( 'post_status_scheduler_email_notification_subject', $email_subject );

      $current_date_and_time = date_i18n('Y-m-d H:i');
      $current_date_and_time = apply_filters( 'post_status_scheduler_email_notification_date', $current_date_and_time );

      // Set email body
      $email_body = __( 'A scheduled update has been executed at', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . ' ' . $current_date_and_time . ' ' . __( 'on your post', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . ' "' . $post->post_title . '".' . "\r\n\r\n";
      $email_body .= __( 'Regards', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . ",\r\nPost Status Scheduler\r\n" . site_url();
			
			// Apply filters
			$email_body = apply_filters( 'post_status_scheduler_email_notification_body', $email_body );   

      // Send the notification email
      wp_mail( $author_email, $email_subject, $email_body );

		}

	}


?>