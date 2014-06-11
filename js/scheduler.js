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
  });

  $('#scheduler-category').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#scheduler-category-box').slideDown();

    } else {

       $('#scheduler-category-box').slideUp();

    }
  });

  $('#scheduler-postmeta').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#scheduler-postmeta-box').slideDown();

    } else {

       $('#scheduler-postmeta-box').slideUp();

    }
  });

});