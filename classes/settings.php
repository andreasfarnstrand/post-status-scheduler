<?php

  /**
   * Settings
   * 
   * Settings class for the plugin Post Status Scheduler
   * 
   * @author Andreas Färnstrand <andreas@farnstranddev.se>
   * 
   * @todo Configure disallowed posttypes
   */

  namespace post_status_scheduler;

  class Settings {
    
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $disallowed_posttypes = array();

    /**
     * Start up
     */
    public function __construct() {
      
      // Add posttypes not allowed here
      // This should probably be a config of some kind
      $this->disallowed_posttypes = array( 'attachment' );

      add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
      add_action( 'admin_init', array( $this, 'page_init' ) );

    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
      
      add_options_page(
          'Settings Admin', 
          __( 'Post Status Scheduler', 'post-status-scheduler' ), 
          'manage_options', 
          'post-status-scheduler', 
          array( $this, 'create_admin_page' )
      );

    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
      
      // Set class property
      $this->options = get_option( 'post_status_scheduler' );
      ?>
      <div class="wrap">
          <?php screen_icon(); ?>
          <h2><?php _e( 'Post Status Scheduler', 'post-status-scheduler' ); ?></h2>           
          <form method="post" action="options.php">
          <?php
              // This prints out all hidden setting fields
              settings_fields( 'posttypes_group' );   
              do_settings_sections( 'post-status-scheduler' );
              submit_button(); 
          ?>
          </form>
      </div>
      <?php

    }

    /**
     * Register and add settings
     */
    public function page_init() {        
      
      register_setting(
        'posttypes_group', // Option group
        'post_status_scheduler', //Option name
        array( $this, 'sanitize' ) // Sanitize
      );

      add_settings_section(
        'posttypes', // ID
        __( 'Post Types', 'post-status-scheduler' ), // Title
        array( $this, 'print_section_info' ), // Callback
        'post-status-scheduler' // Page
      );  

      add_settings_field(
        'allowed_posttypes', // ID
        __( 'Check the post types you wish to display the Scheduler on', 'post-status-scheduler' ), // Title 
        array( $this, 'id_number_callback' ), // Callback
        'post-status-scheduler', // Page
        'posttypes' // Section           
      );

      add_settings_field(
        'meta_keys', // ID
        __( 'Mark allowed meta fields to be shown as removable', 'post-status-scheduler' ), // Title 
        array( $this, 'metafield_callback' ), // Callback
        'post-status-scheduler', // Page
        'posttypes' // Section           
      );

    }


    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {

      $new_input = array();
      if( isset( $input['allowed_posttypes'] ) ) {

        if( count( $input['allowed_posttypes'] ) > 0 ) {

          foreach( $input['allowed_posttypes'] as $key => $post_type ) {

            $new_input['allowed_posttypes'][$key] = esc_attr( $post_type );

          }

        }

      }

      if( isset( $input['meta_keys'] ) ) {

        if( count( $input['meta_keys'] ) > 0 ) {

          foreach( $input['meta_keys'] as $key => $meta_key ) {

            $new_input['meta_keys'][$key] = esc_attr( $meta_key );

          }

        }

      }

      return $new_input;
      
    }

    /** 
     * Print the Section text
     */
    public function print_section_info() {
        
        print __( 'Enter your settings below:', 'post-status-scheduler' );

    }


    /**
     * id_number_callback
     * 
     * Callback for the posttypes allowed
     */
    public function id_number_callback() {

      // Get all valid public post types
      $post_types = get_post_types( array( 'public' => true ) );

      $options = get_option( 'post_status_scheduler' );

      if( count( $post_types ) > 0 ) {
        
        foreach( $post_types as $post_type ) {

          if( !in_array( $post_type, $this->disallowed_posttypes ) ) {

            $checked = '';
            if( isset( $options['allowed_posttypes'] ) && is_array( $options['allowed_posttypes'] ) && count( $options['allowed_posttypes'] ) > 0 ) {
              if( in_array( $post_type, $options['allowed_posttypes'] ) ) {
              
                $checked = 'checked="checked"';
            
              }
            }

            echo sprintf( '<input type="checkbox" name="post_status_scheduler[allowed_posttypes][%s]" value="%s" %s /> %s<br />', esc_attr( $post_type ), esc_attr( $post_type ), $checked, esc_attr( $post_type ) );

          }
        
        }

      }

    }


    /**
     * metafield_callback
     * 
     * Callback for the meta_keys allowed settings
     */
    public function metafield_callback() {
      global $wpdb;

      $result = array();
      $keys = $wpdb->get_results( "SELECT DISTINCT(meta_key) FROM $wpdb->postmeta ORDER BY meta_key ASC" );

      if( count( $keys ) > 0 ) {

        foreach( $keys as $key_result ) {
          array_push( $result, $key_result->meta_key );
        }

      }

      $options = get_option( 'post_status_scheduler' );
      $chosen_meta_keys = isset( $options['meta_keys'] ) ? $options['meta_keys'] : array();
      
      ?>
      <select name="post_status_scheduler[meta_keys][]" multiple>
        <?php
        foreach( $result as $key_name ) {
          $selected = '';
          if( in_array( $key_name, $chosen_meta_keys ) ) {
            $selected = ' selected="selected" ';
          }
          echo sprintf( '<option value="%s" %s >%s</option>', $key_name, $selected, $key_name );
        }

        ?>
      </select>
      <?php


    }


  }


?>