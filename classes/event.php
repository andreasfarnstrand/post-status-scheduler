<?php

	/**
	 * Event
	 * 
	 * This class is a container class for the scheduled event
	 * 
	 * @author Andreas Färnstrand <andreas@farnstranddev.se>
	 */

	namespace post_status_scheduler;

	class Event {

		// Contains all the event properties
		protected $data;


		/**
		 * __construct
		 * 
		 * Init the event with the post's meta data
		 */
		public function __construct( $post_id ) {

			$this->id = $post_id;

      // Load the event post
      $this->post = get_post( $post_id );

      // Load the event date
			$this->date = get_post_meta( $post_id, 'scheduler_date', true );

			// Get all scheduler postmeta data
      $check_status = get_post_meta( $post_id, 'scheduler_check_status', true );
      $this->check_status = !empty( $check_status ) ? true : false;

      $this->status = get_post_meta( $post_id, 'scheduler_status', true );

      // Get check category meta
      $check_category = get_post_meta( $post_id, 'scheduler_check_category', true );
      $this->check_category = !empty(  $check_category ) ? true : false;

      // Get the category action - used by old versions
      $this->category_action = get_post_meta( $post_id, 'scheduler_category_action', true );

      // Get the activated categories
      $this->category = get_post_meta( $post_id, 'scheduler_category', true );

      // Get if we need to check meta data
      $check_meta = get_post_meta( $post_id, 'scheduler_check_meta', true );
      $this->check_meta = !empty( $check_meta ) ? true : false;
      $this->meta_key = get_post_meta( $post_id, 'scheduler_meta_key', true );

      // Get email notify
      $email_notify = get_post_meta( $post_id, 'scheduler_email_notify', true );
      $this->email_notify = !empty( $email_notify ) ? $email_notify : false;

		}


    /**
     * save
     * 
     * Save the scheduler postmeta
     */
		public function save() {

			update_post_meta( $this->id, 'scheduler_date', $this->date );

      // Post status
      update_post_meta( $this->id, 'scheduler_check_status', $this->check_status );
      update_post_meta( $this->id, 'scheduler_status', $this->status );

      // post category
      update_post_meta( $this->id, 'scheduler_check_category', $this->check_category );

      // Add categories
      update_post_meta( $this->id, 'scheduler_category_action', $this->category_action );
      update_post_meta( $this->id, 'scheduler_category', $this->category );

      // post meta
      update_post_meta( $this->id, 'scheduler_check_meta', $this->check_meta );
      update_post_meta( $this->id, 'scheduler_meta_key', $this->meta_key );

      // Email notification
      if( isset( $this->email_notify ) && $this->email_notify == true ) {
        update_post_meta( $this->id, 'scheduler_email_notify', true );
      } else {
        update_post_meta( $this->id, 'scheduler_email_notify', false );
      }

		}


		/**
		 * delete
		 * 
		 * Delete all meta data for gven post
		 * 
		 * @param integer $post_id
		 */
		public static function delete( $post_id ) {

			// Remove post meta
      delete_post_meta( $post_id, 'scheduler_date' );
          
      // post status
      delete_post_meta( $post_id, 'scheduler_check_status' );
      delete_post_meta( $post_id, 'scheduler_status' );

      // Legacy code for old versions of plugin
      delete_post_meta( $post_id, 'scheduler_category_action' );
      delete_post_meta( $post_id, 'scheduler_category' );

      // post categories
      delete_post_meta( $post_id, 'scheduler_check_category' );
      delete_post_meta( $post_id, 'scheduler_category_action_add' );
      delete_post_meta( $post_id, 'scheduler_category_add' );
      delete_post_meta( $post_id, 'scheduler_category_action_remove' );
      delete_post_meta( $post_id, 'scheduler_category_remove' );
      
      // post meta
      delete_post_meta( $post_id, 'scheduler_check_meta' );
      delete_post_meta( $post_id, 'scheduler_meta_key' );

      // Email notification
      delete_post_meta( $post_id, 'scheduler_email_notify' );

		}


    /**
     * __get
     * 
     * Automagical get function for object
     * Returns the requested property if exists
     * 
     * @param string $key
     * 
     * @return $value|null
     */
		public function __get( $key ) {
			return isset( $this->data[$key] ) ? $this->data[$key] : null;
		}


    /**
     * __set
     * 
     * Automagical set function for object
     * Sets the property to the given value
     * 
     * @param $key The property to set
     * @param $value The value to set the property with
     */
		public function __set( $key, $value ) {
			$this->data[$key] = $value;
		}


    /**
     * __isset
     * 
     * Automagical isset function for object
     * 
     * @param string $value
     * 
     * @return boolean
     */
    public function __isset( $value ) {
      return isset( $this->data[$value] );
    }


	}


?>