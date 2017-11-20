<?php

function humanReadableBytes($bytes, $s=" ") {
  // Given a number of bytes it converts it to the most appropriate unit, B, kB, MB, GB or TB
  // and returns this value with the units appended
  // $s just contains any padding between the number and the units, 1 space by default
  $retstring;

  if ( $bytes > 1024 && $bytes < 1024*1024 ){
    $retstring = $retstring . round($bytes/1024, 2 ) . $s ."kB";
  }
  elseif ( $bytes > 1024*1024 && $bytes < 1024*1024*1024 ){
    $retstring = $retstring . round($bytes/(1024*1024), 2) . $s ."MB";
  }
  elseif ( $bytes > 1024*1024*1024 ) {
    $retstring = $retstring . round($bytes/(1024*1024*1024), 2) . $s ."GB";
  }
  elseif ( $bytes > 1024*1024*1024*1024 ) {
    $retstring = $retstring . round($bytes/(1024*1024*1024*1024), 2) . $s ."TB";
  }
  else {
    $retstring = $retstring . $bytes . $s ."B";
  }

  return $retstring;

}

?>
