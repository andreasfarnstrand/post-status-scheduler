<?php

  /**
   * This plugin adds capabilites to set an unpublishing date
   * for post types set in the modules settings page. It also
   * gives the possibility to set the new post status directly
   * on the post.
   * You can add or remove a category and also remove postmeta
   * on the scheduled timestamp.
   * 
   * @author Andreas Färnstrand <andreas@farnstranddev.se>
   * 
   */

  namespace post_status_scheduler;

  require_once( 'event.php' );
  require_once( 'taxonomy.php' );
  require_once( 'email.php' );

  use \post_status_scheduler\Event as Event;
  use \post_status_scheduler\Taxonomy as Taxonomy;
  use \post_status_scheduler\Settings as Settings;
  use \post_status_scheduler\Email as Email;
   
  if( !defined( 'POST_STATUS_SCHEDULER_TEXTDOMAIN' ) ) define( 'POST_STATUS_SCHEDULER_TEXTDOMAIN', 'post-status-scheduler' );

  class Scheduler {

    private $options = array();

    /**
    * Constructor - Add hooks
    */
    public function __construct() {
      
      global $pagenow;

      // Load translations
      add_action( 'plugins_loaded', array( $this , 'load_translations') );

      // Add the action used for unpublishing posts
      add_action( 'schedule_post_status_change', array( $this, 'schedule_post_status_change' ), 10, 1 );

      // Remove any scheduled changes for post on deletion or trash post
      add_action( 'delete_post', array( $this, 'remove_schedule' ) );
      add_action( 'wp_trash_post', array( $this, 'remove_schedule' ) );


      if( is_admin() ) {

        $this->options = Settings::get_options();

        if( !is_array( $this->options ) ) {
          $this->options = array();
        }

        // Add html to publish meta box
        add_action( 'post_submitbox_misc_actions', array( $this, 'scheduler_admin_callback' ) );

        // Add scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'add_plugin_resources' ) );

        // Hook into save post
        add_action( 'save_post', array( $this, 'save' ) );

        // Get saved options
        $scheduler_options = $this->options;
        $scheduler_options = isset( $scheduler_options['allowed_posttypes'] ) ? $scheduler_options['allowed_posttypes'] : null;

        // If this is a list of post types then we add columns
        if( isset( $pagenow ) && $pagenow == 'edit.php' ) {

          if( isset( $this->options['extra_column_enabled'] ) && $this->options['extra_column_enabled'] == true ) {
            
            // Set the post type to post if it is not in address field
            if( !isset( $_GET['post_type'] ) ) {
              
              $post_type = 'post';

            } else {
              
              $post_type = $_GET['post_type'];

            }

            // Is this post type set to have unpublishing options?
            if( isset( $post_type ) && is_array( $scheduler_options ) && in_array( $post_type, $scheduler_options ) ) {

              foreach( $scheduler_options as $type ) {
                
                // Add new columns
                add_filter( 'manage_'.$type.'_posts_columns', array( $this, 'add_column' ) );
                // Set column content
                add_action( 'manage_'.$type.'_posts_custom_column', array( $this, 'custom_column' ), 10, 2);
                // Register column as sortable
                add_filter( "manage_edit-".$type."_sortable_columns", array( $this, 'register_sortable' ) );

              }
              
              // The request to use as orderby
              add_filter( 'request', array( $this, 'orderby' ) );

            }

          }

        }

      }

    }


    /**
     * load_translations
     * 
     * Load the correct plugin translation file
     */
    public function load_translations() {

      load_plugin_textdomain( 'post-status-scheduler', false, POST_STATUS_SCHEDULER_TEXTDOMAIN_PATH );

    }


    /**
     * remove_schedule
     * 
     * Remove a scheduled event. Used by the hook
     * 
     * @param int $post_id
     */
    public function remove_schedule( $post_id ) {

      Scheduler::unschedule( $post_id );

    }


    /**
     * Implements hook save_post
     * 
     * @param int $post_id
     */
    public function save( $post_id ) {

      global $post, $typenow, $post_type;

      if( !current_user_can( 'edit_post', $post_id ) ) return $post_id;
      if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
      if( !isset( $this->options ) ) return $post_id;

      // Get the valid post types set in options page
      $scheduler_options = !empty( $this->options['allowed_posttypes'] ) ? $this->options['allowed_posttypes'] : array();

      // Abort if not a valid post type
      if( !in_array( $post_type, $scheduler_options ) ) return $post_id;

      // Add filter for developers to modify the received post data
      $postdata = apply_filters( 'post_status_scheduler_before_save', array( $post->ID, $_POST ) );
      $postdata = $postdata[1];

      // Setup data
      $date = isset( $postdata['scheduler']['date'] ) && strlen( $postdata['scheduler']['date'] ) == 10 ? $postdata['scheduler']['date'] : null;
      $time = isset( $postdata['scheduler']['time'] ) && strlen( $postdata['scheduler']['time'] ) == 5 ? $postdata['scheduler']['time'] : null;

      // Create a new event container
      $event = new Event( $post_id );

      $event->check_status = isset( $postdata['scheduler']['post-status-check'] ) ? true : false;
      $event->status = isset( $postdata['scheduler']['status'] ) ? $postdata['scheduler']['status'] : null;
      
      // Categories
      $event->check_category = isset( $postdata['scheduler']['category-check'] ) ? true : false;

      // Adding categories
      $event->category_action = isset( $postdata['scheduler']['category-action'] ) ? true : false;
      $event->category = isset( $postdata['scheduler']['category'] ) ? $postdata['scheduler']['category'] : array();

      $event->check_meta = isset( $postdata['scheduler']['postmeta-check'] ) ? true : false;
      $event->meta_key = isset( $postdata['scheduler']['meta_key'] ) ? $postdata['scheduler']['meta_key'] : null;

      $event->email_notify = isset( $postdata['scheduler']['email-notify'] ) ? true : false;

      // Check if there is an old timestamp to clear away
      //$old_timestamp = get_post_meta( $post->ID, 'post_status_scheduler_date', true );
      $old_timestamp = Scheduler::previous_schedule( $post->ID );

      // Is there a timestamp to save?
      if( !empty( $date ) && !empty( $time ) && isset( $postdata['scheduler']['use'] ) ) {

        if( !$new_timestamp = Scheduler::create_timestamp( $date, $time ) ) {
          
          return $post_id;

        }

        // Remove old scheduled event and post meta tied to the post
        if( isset( $old_timestamp ) ) {

          Scheduler::unschedule( $post->ID );
          Event::delete( $post->ID );

        }


        if( !$gmt = Scheduler::check_gmt_against_system_time( $new_timestamp ) ) return $post_id;

        // Clear old scheduled time if there is one
        Scheduler::unschedule( $post->ID );

        // Schedule a new event
        $scheduling_result = wp_schedule_single_event( $gmt, 'schedule_post_status_change', array( $post->ID ) );
        $scheduling_result = isset( $scheduling_result ) && $scheduling_result == false ? false : true;

        // Update the post meta tied to the post
        if( $scheduling_result ) {

          $event->date = $new_timestamp;
          $event->save();
          
          apply_filters( 'post_status_scheduler_after_scheduling_success', $post->ID );

        } else {

          apply_filters( 'post_status_scheduler_after_scheduling_error', $post->ID );

        }

      } else {

        // Clear the scheduled event and remove all post meta if
        // user removed the scheduling
        if( isset( $old_timestamp ) ) {

          Scheduler::unschedule( $post->ID );

          // Remove post meta
          Event::delete( $post->ID );

        }

      }

    }


    /**
     * This is the actual function that executes upon
     * hook execution
     * 
     * @param $post_id
     */
    public function schedule_post_status_change( $post_id ) {

      // Get all scheduler postmeta data
      $event = new Event( $post_id );

      $valid_statuses = array_keys( Scheduler::post_statuses() );

      // Add a filter for developers to change the flow
      $filter_result = apply_filters( 'post_status_scheduler_before_execution', array( 'status' => $event->status, 'valid_statuses' => $valid_statuses ), $post_id );
      $scheduler_status = $filter_result['status'];
      $valid_statuses = $filter_result['valid_statuses'];

      $executed_events = array();

      if( $event->check_status ) {

        // Execute the scheduled status change
        if( in_array( $event->status, $valid_statuses ) ) {

          switch( $event->status ) {
            case 'draft':
            case 'pending':
            case 'private':
              wp_update_post( array( 'ID' => $post_id, 'post_status' => $event->status ) );
              break;
            case 'trash':
              wp_delete_post( $post_id );
              break;
            case 'deleted': // Delete without first moving to trash
              wp_delete_post( $post_id, true );
              break;  
            default:
              break;
          }

          // Add the executed event
          $executed_events []= 'check_status';

        }

      }

      // If user just wish to remove a post meta
      if( $event->check_meta ) {

        if( !empty( $event->meta_key ) ) {
          delete_post_meta( $post_id, $event->meta_key );

          // Add the executed event
          $executed_events []= 'check_meta';
        }

      }


      // Add and remove categories
      if( $event->check_category ) {

        if( is_array( $event->category ) ) {

          if( count( $event->category ) > 0 ) {

            // Reset all categories
            Taxonomy::reset_all_terms( $post_id );

            foreach( $event->category as $scheduler_cat ) {

              $scheduler_category_splits = explode( '_', $scheduler_cat );
              if( count( $scheduler_category_splits ) >= 2 ) {
            
                // Get the category id
                $scheduler_category_to_add = array_shift( $scheduler_category_splits );
                // Get the taxonomy of the category
                $scheduler_category_taxonomy = implode( '_', $scheduler_category_splits );

                // Update the categories
                Taxonomy::set_terms( $post_id, array( $scheduler_category_to_add ), $scheduler_category_taxonomy, true );

                // Add the executed event
                $executed_events []= 'check_category';
          
              }

            }
          }

        } else { // This is here for legacy reasons, versions <= 0.2.1

          if( is_string( $event->category ) && strlen( $event->category ) > 0 ) {
            
            if( !empty( $event->category_action ) ) {
        
              $scheduler_category_splits = explode( '_', $event->category );
              if( count( $scheduler_category_splits ) >= 2 ) {
            
                // Get the category id
                $scheduler_category = array_shift( $scheduler_category_splits );

                // Get the taxonomy of the category
                $scheduler_category_taxonomy = implode( '_', $scheduler_category_splits );

                if( $scheduler_category_action == 'add' ) {

                  Taxonomy::set_terms( $post_id, array( $scheduler_category ), $scheduler_category_taxonomy, true );

                } else if( $event->category_action == 'remove' ) {

                  $categories = Taxonomy::get_terms( $post_id, $scheduler_category_taxonomy );
                  $new_categories = array();

                  if( count( $categories ) > 0 ) {

                    foreach( $categories as $key => $category ) {

                      array_push( $new_categories, $category->term_id );

                    }

                  }

                  $position = array_search( $scheduler_category, $new_categories );
                  unset( $new_categories[$position] );

                  Taxonomy::set_terms( $post_id, $new_categories, $scheduler_category_taxonomy );

                  // Add the executed event
                  $executed_events []= 'check_category';

                }

              }

            }

          }

        }

      }

      // Log the execution time on the post
      Scheduler::log_run( $post_id );
      
      // Remove post meta
      Event::delete( $post_id );

      $options = Settings::get_options();

      // Checkto see if we should send an email notification
      //if( isset( $options['notification_email_enabled'] ) && $options['notification_email_enabled'] == true ) {

        // Is the email notification checked on this event
        if( isset( $event->email_notify ) && $event->email_notify == true ) {

          if( is_object( $event->post ) ) {

            Email::update_notification( $event->post );
            
          }

        }

      //}

      apply_filters( 'post_status_scheduler_after_execution', array( 'status' => $scheduler_status, 'valid_statuses' => $valid_statuses ), $post_id );

    }


    /**
     * Add scripts and stylesheets to the page
     * 
     * @param string $hook
     */
    public function add_plugin_resources( $hook ) {

      $current_screen = get_current_screen();

      if( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && isset( $this->options['allowed_posttypes'] ) && in_array( $current_screen->post_type, $this->options['allowed_posttypes'] ) ) {
        
        wp_enqueue_script( 'jquery-timepicker-js', POST_STATUS_SCHEDULER_PLUGIN_PATH . 'js/jquery.ui.timepicker.js', array( 'jquery', 'jquery-ui-core' ), false, true );
        wp_enqueue_script( 'scheduler-js', POST_STATUS_SCHEDULER_PLUGIN_PATH . 'js/scheduler.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), false, true );
        wp_enqueue_style( array( 'dashicons' ) );
        
        wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
        wp_enqueue_style( 'jquery-ui' );

        wp_register_style('jquery-timepicker-css', POST_STATUS_SCHEDULER_PLUGIN_PATH . 'css/jquery.ui.timepicker.css' );
        wp_enqueue_style( 'jquery-timepicker-css' );

        wp_enqueue_style( 'scheduler-style', POST_STATUS_SCHEDULER_PLUGIN_PATH . 'css/scheduler.css' );

        // Add filter so developers can add their own assets
        apply_filters( 'post_status_scheduler_plugin_resources', POST_STATUS_SCHEDULER_PLUGIN_PATH );
        
      }

    }


    /**
     * Logic and HTML for outputting the
     * data on the admin post type edit page
     */
    public function scheduler_admin_callback() {

      global $post, $post_type;

      $event = new Event( $post->ID );

      // Get valid post types set in module settings page
      $allowed_posttypes = isset( $this->options['allowed_posttypes'] ) ? $this->options['allowed_posttypes'] : array();
      // Check if there are any meta keys to be shown
      $meta_keys = isset( $this->options['meta_keys'] ) ? $this->options['meta_keys'] : array();
      // Check if email notification is set
      $use_notification = isset( $this->options['email_notification'] ) ? true : false;

      $categories = Taxonomy::get_posttype_terms( $post_type );

      $post_categories = Taxonomy::get_all_terms( $post->ID );
      $post_categories = Taxonomy::setup_post_terms( $post_categories, $categories );

      // Do not show HTML if there are no valid post types or current edit page is not for a valid post type
      if( count( $allowed_posttypes ) && in_array( $post_type, $allowed_posttypes ) ) {
        
        $date = $event->date;
        $status = $event->status;

        $date = isset( $date ) && strlen( $date ) > 0 ? date( 'Y-m-d H:i', $date ) : null;
        $dates = explode( ' ', $date );
        
        $date = isset( $dates[0] ) ? $dates[0] : null;
        $time = isset( $dates[1] ) ? $dates[1] : null;

        $status = !empty( $status ) ? $status : null;

        // Set a couple of attributes on html
        $checked = !empty( $date ) ? ' checked="checked" ' : '';
        $show = empty( $date ) ? ' style="display: none;" ' : '';

        $scheduler_check_status_checked = ( $event->check_status ) ? ' checked="checked" ' : '';
        $scheduler_check_status_show = ( !$event->check_status ) ? ' style="display: none;" ' : '';

        $scheduler_check_category_checked = ( $event->check_category ) ? ' checked="checked" ' : '';
        $scheduler_check_category_show = ( !$event->check_category ) ? ' style="display: none;" ' : '';

        $scheduler_check_meta_checked = ( $event->check_meta ) ? ' checked="checked" ' : '';
        $scheduler_check_meta_show = ( !$event->check_meta ) ? ' style="display: none;" ' : '';

        // Write the HTML
        echo '<div class="misc-pub-section misc-pub-section-last" id="scheduler-wrapper">
        <span id="timestamp" class="calendar-link before">'
        . '<label> ' . __( 'Schedule Status Change', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . '</label> <input type="checkbox" id="scheduler-use" name="scheduler[use]" ' . $checked . ' /><br />'
        . '<div id="scheduler-settings" ' . $show . ' >'
        . '<label>' . __( 'Date', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . '</label> '
        . '<input type="text" id="schedulerdate" name="scheduler[date]" value="' .$date. '" maxlengt="10" readonly="true" /> '
        . '<label>' . __( 'Time', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . '</label> '
        . '<input type="text" id="schedulertime" name="scheduler[time]" value="' . $time . '" maxlength="5" readonly="true" /><br /><br />'

        // Post Status
        . '<input type="checkbox" name="scheduler[post-status-check]" id="scheduler-status" ' . $scheduler_check_status_checked . ' /> ' . __( 'Change status', 'post-status-scheduler' ) . '<br />'
        . '<div id="scheduler-status-box" ' . $scheduler_check_status_show . ' >'
        . '<label>' . __( 'Set status to', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . '</label> '
        . '<select name="scheduler[status]" style="width: 98%;">';

        foreach( Scheduler::post_statuses() as $key => $value ) {

          echo sprintf( '<option value="%s" ' . selected( $status,  $key ) . ' >%s</option>', $key, $value );

        }
        echo '</select><br />'
        . '</div>';


        // Categories
        if( count( $categories ) > 0 ) {

          echo '<input type="checkbox" name="scheduler[category-check]" id="scheduler-category" ' . $scheduler_check_category_checked . ' /> ' . __( 'Change categories', 'post-status-scheduler' ) . '<br />'
          .'<div id="scheduler-category-box" ' . $scheduler_check_category_show . '>';
          echo __( 'The post will have the following categories on scheduled time', POST_STATUS_SCHEDULER_TEXTDOMAIN );
          echo '<select name="scheduler[category][]" multiple size="5">';
            
          // Need this for legacy reasons. Used to be a string, versions <= 0.2.1
          if( is_string( $event->category ) ) {

            if( $event->category_action == 'add' ) {

              if( !in_array( $event->category, $post_categories ) ) array_push( $post_categories, $event->category );


            } else if( $event->category_action == 'remove' ) {
              
              if( ( $key = array_search( $event->category, $post_categories ) ) !== false ) {
                
                unset( $post_categories[$key] );
              
              } else if( in_array( $category->term_id.'_'.$category->taxonomy, $post_categories ) ) {
                
                $selected = ' selected="selected" ';
              
              }

            }

          }

          // Loop categories and check if selected
          foreach( $categories as $category ) {

            if( is_array( $event->category ) ) {

              $selected = in_array( $category->term_id.'_'.$category->taxonomy, $event->category ) ? ' selected="selected" ' : '';
            
            } else {

              $selected = in_array( $category->term_id.'_'.$category->taxonomy, $post_categories ) ? ' selected="selected" ' : '';

            }

            echo sprintf( '<option value="%s"%s>%s</option>', $category->term_id.'_'.$category->taxonomy, $selected, $category->name );

          }

          echo '</select></div>';

        }

        // Meta keys
        if( count( $meta_keys ) > 0 ) {
          echo '<input type="checkbox" name="scheduler[postmeta-check]" id="scheduler-postmeta" ' . $scheduler_check_meta_checked . ' /> ' . __( 'Remove postmeta', POST_STATUS_SCHEDULER_TEXTDOMAIN ) . '<br />'
          .'<div id="scheduler-postmeta-box" ' . $scheduler_check_meta_show . ' >'
          . '<select name="scheduler[meta_key]">';

          if( count( $meta_keys ) > 0 ) {
            foreach( $meta_keys as $meta_key ) {

              echo sprintf( '<option value="%s">%s</option>', $meta_key, $meta_key );

            }
          }

          echo '</select>'
          . '</div>';
        }

        // Email notification option
        if( isset( $this->options['notification_email_enabled'] ) && $this->options['notification_email_enabled'] == true ) {

          echo '<hr />';

          if( isset( $event->email_notify ) && $event->email_notify == true ) {
            $email_notify_checked = ' checked="checked" ';
          } else {
            $email_notify_checked = '';
          }

          // The checkbox for sending a notification email to author
          echo '<input type="checkbox" id="scheduler-email-notification" name="scheduler[email-notify]" disabled="" ' . $email_notify_checked . '/> ' . __( 'Send email notification on change', POST_STATUS_SCHEDULER_TEXTDOMAIN );
          echo '<p class="description">(' . get_the_author_meta( 'user_email', $post->post_author ) . ')</p>';
        }
        echo '</div>'
        .'</span></div>';

      }

    }


    /**
    * Add a column to the default columnsarray
    *
    * @param array $columns
    * 
    * @return array $columns
    */
    public function add_column( $columns ) {

      $new_columns = array();

      foreach( $columns as $key => $value ) {
        if( $key == 'date' ) {

          $new_columns['scheduler_date']    = __( 'Scheduled date',  POST_STATUS_SCHEDULER_TEXTDOMAIN );

        }
        
        $new_columns[$key] = $value;
      }
      
      return $new_columns;
    }
    
    
    /**
    * Set the column content
    *
    * @param string $columnname
    * @param integer $postid
    * 
    * @return string $columncontent
    */
    public function custom_column( $column_name, $post_id ) {

      if( $column_name == 'scheduler_date' ) {

        $meta_data = get_post_meta( $post_id, 'scheduler_date', true );
        $meta_data = isset( $meta_data ) ? $meta_data : null;

        if( isset( $meta_data ) && strlen( $meta_data ) > 0 ) {
          $date = date( 'Y-m-d H:i', $meta_data );
        } else {
          $date = '';
        }

        $column_content = $date;
        echo $column_content;
      }
      
    }
    
    
    /**
    * Register the column as a sortable
    *
    * @param array $columns
    * 
    * @return array $columns
    */
    public function register_sortable( $columns ) {

      // Register the column and the query var which is used when sorting
      $columns['scheduler_date'] = 'scheduler_date';
      
      return $columns;

    }
    
    
    /**
    * The query to use for sorting
    *
    * @param array vars
    * 
    * @return array $vars
    */
    public function orderby( $vars ) {
      
      if ( isset( $vars['orderby'] ) && 'scheduler_date' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
          'meta_key' => 'scheduler_date',
          'orderby' => 'meta_value'
        ));
      }
      
      return $vars;

    }


    /* ---------------- STATIC FUNCTIONS --------------------- */


    /**
     * check_gmt_against_system_time
     * 
     * @param integer $new_timestamp
     * 
     * @return integer $gmt;
     */
    public static function check_gmt_against_system_time( $new_timestamp ) {

      // Get the current system time to compare with the new scheduler timestamp
        $system_time = microtime( true );
        $gmt = get_gmt_from_date( date( 'Y-m-d H:i:s', $new_timestamp ),'U');

        // The gmt needs to be bigger than the current system time
        if( $gmt <= $system_time ) return false;

        return $gmt;

    }


    /**
     * create_timestamp
     * 
     * Create a new timestamp from given date and time
     * 
     * @param string $date
     * @param string $time
     *
     * @return boolen|integer
     */
    public static function create_timestamp( $date, $time ) {

       $timestamp = strtotime( $date . ' ' . $time . ':00' );

        //Abort if not a valid timestamp
        if( !isset( $timestamp ) || !is_int( $timestamp ) ) return false;

        return $timestamp;

    }


    /**
     * previous_schedule
     * 
     * Return a previously scheduled time for this post
     * 
     * @param $post_id
     * 
     * @return string
     */
    public static function previous_schedule( $post_id ) {

      return get_post_meta( $post->ID, 'post_status_scheduler_date', true );

    }


    /**
     * list_meta_keys
     * 
     * Get all meta keys in postmeta table
     * 
     * @return array
     */
    public static function list_meta_keys() {

      global $wpdb;

      $result = array();
      $keys = $wpdb->get_results( "SELECT DISTINCT(meta_key) FROM $wpdb->postmeta ORDER BY meta_key ASC" );

      if( count( $keys ) > 0 ) {

        foreach( $keys as $key_result ) {
          array_push( $result, $key_result->meta_key );
        }

      }

      return $result;

    }


    /**
     * unschedule
     * 
     * Unschedule a scheduled change
     * 
     * @param int $post_id
     */
    public static function unschedule( $post_id ) {

      wp_clear_scheduled_hook( 'schedule_post_status_change', array( $post_id ) );

    }


    /**
     * log_run
     * 
     * Log the time for the scheduled execution on the post
     * 
     * @param int $post_id
     */
    public static function log_run( $post_id ) {

      update_post_meta( $post_id, 'scheduler_unpublished', current_time( 'timestamp' ) );

    }



    /**
     * post_statuses
     * 
     * Get the valid post stauses to use
     * 
     * @return array
     */
    public static function post_statuses() {

      // All valid post statuses to choose from
      return array( 
        'draft' => __( 'Draft', POST_STATUS_SCHEDULER_TEXTDOMAIN ), 
        'pending' => __( 'Pending', POST_STATUS_SCHEDULER_TEXTDOMAIN ), 
        'private' => __( 'Private', POST_STATUS_SCHEDULER_TEXTDOMAIN ),
        'trash' =>  __( 'Trashbin', POST_STATUS_SCHEDULER_TEXTDOMAIN ),
        'deleted' => __( 'Delete (forced)', POST_STATUS_SCHEDULER_TEXTDOMAIN ),
      );

    }


  }

?>