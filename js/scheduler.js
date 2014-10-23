jQuery( document ).ready( function( $ ) {

  $('#schedulerdate').datepicker({

    dateFormat: 'yy-mm-dd'

  });

  $('#schedulertime').timepicker();

  $('#scheduler-use').change( function() {

    if( $(this).is(':checked') ) {
       
      $('#scheduler-settings').slideDown();

    } else {

      $('#scheduler-settings').slideUp();

    }

  });

  $('#scheduler-status').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#scheduler-status-box').slideDown();

    } else {

       $('#scheduler-status-box').slideUp();

    }

    toggle_email_notification();
  });

  $('#scheduler-category').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#scheduler-category-box').slideDown();

    } else {

       $('#scheduler-category-box').slideUp();

    }

    toggle_email_notification();
  });

  $('#scheduler-postmeta').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#scheduler-postmeta-box').slideDown();

    } else {

       $('#scheduler-postmeta-box').slideUp();

    }

    toggle_email_notification();
  });

  toggle_email_notification();

  function toggle_email_notification() {

    if( $('#scheduler-status').is(':checked') || $('#scheduler-category').is(':checked') || $('#scheduler-postmeta').is(':checked') ) {

      $('#scheduler-email-notification').removeAttr('disabled');

    } else {

      $('#scheduler-email-notification').attr('disabled', 'disabled');
      $('#scheduler-email-notification').removeAttr('checked', 'checked');

    }

  }

});