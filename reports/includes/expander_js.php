<?php

// Requires jquery-ui


script('
   function showhide(elementid, expander, type) {
		var el = "#"+elementid;
		var disp = $(el).css("display") ;
		if ( ! type ) { type = "blind" ; }

    $(el).toggle( type, "", 500);

		$(expander).find(".expander-icon").toggleClass("ui-icon-triangle-1-e");
		$(expander).find(".expander-icon").toggleClass("ui-icon-triangle-1-s");

    //$(el).toggle( type,"",500, function(){ toggleExpanderContent(expander, disp; });

   }
	
	function toggleExpanderContent(expander, disp) {
		var ex = "#"+expander;
    if ( disp != "none" ) { $(ex).html("&nbsp;+&nbsp;"); }
    else { $(ex).html("&nbsp;-&nbsp;"); }
	}

');

/*
script('
      function showhide(elementid, expander) {
       var el = document.getElementById(elementid);
        if (el.style.display == "block" ) {
          el.style.display = "none";
          expander.innerHTML = ("&nbsp;+&nbsp;");
        }
        else {
          el.style.display = "block";
          expander.innerHTML = ("&nbsp;-&nbsp;");
        }
      }
');
*/

?>
