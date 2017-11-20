
function loadCalendars() {
  $('#cal1_container').datepicker({ dateFormat: 'dd/mm/yy' });
  $('#cal2_container').datepicker({ dateFormat: 'dd/mm/yy' });
}

$('#daterangeselect').ready( function(){ loadCalendars(); } );
