<?php


  /*
  Plugin Name: Post Status Scheduler
  Description: Change status, category or postmeta of any post type at a scheduled timestamp.
  Version: 0.1
  Author: Andreas Färnstrand <andreas@farnstranddev.se>
  Author URI: http://www.farnstranddev.se
  Text Domain: post-status-scheduler
  */

 
  /*  Copyright 2014  Andreas Färnstrand  (email : andreas@farnstranddev.se)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

  use post_status_scheduler as post_status_scheduler;

 	if( !class_exists( 'Post_Status_Scheduler' ) ) { 

 		require_once 'classes/settings.php';
 		require_once 'classes/scheduler.php';

 		define( 'POST_STATUS_SCHEDULER_PLUGIN_PATH', plugin_dir_url( __FILE__ ) );
 		define( 'POST_STATUS_SCHEDULER_TEXTDOMAIN', 'post_status_scheduler' );
    define( 'POST_STATUS_SCHEDULER_TEXTDOMAIN_PATH', dirname( plugin_basename( __FILE__) ) .'/languages' );

  	$pss = new \post_status_scheduler\Scheduler();

  	if( is_admin() ) {
  		$settings = new \post_status_scheduler\Settings();
  	}

  }


?>