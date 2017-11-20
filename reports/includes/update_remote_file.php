<?php

// Function to be included into php scripts.
// Purpose:
// to push script file over to a remote host via scp.
// A few of the php scripts require a perl or shell script to be run on a remote host.  If the php end is able to check whether the latest version of the script already exists there and if not then push it across then it makes deployment a lot easier (in fact it means you don't need to do anything) especially if you have made updates to the remote script.

// Requires www-data user to have passwordless ssh access to the remote login account on the remote host.

// Note that remotepath must be writeable by the remote login account.


function update_remote_file( $filetopush, $remotehost, $rlogin, $remotepath, $type ) {
 $retval = 0 ;
 // $type should be "file" or "executable"
 if ( $type == "executable" ){ $typetest = "-x" ; }
 else { $typetest = "-f" ; $type = "file" ;}

 $filename = basename($filetopush);
 $localpath = dirname($filetopush);

 // Test whether remote file exists and is executable on remote host
 // remote path may have a filename or it may have just the dir name.
 $remotefile='';
 if ( basename($filetopush) != basename($remotepath) ) {
   $remotefile = "$remotepath/$filename" ;
 }
 else{
   $remotepath = dirname($remotepath);
 }

  $output = shell_exec(" ssh $rlogin@$remotehost \"test $typetest $remotefile ; echo \\\$? \" 2>&1 ");

  if ( trim($output) != "0" ) {
    // presumably the ssh returned an error
    // which means that there is no remotescript available in $remotepath
      print "Remote $type $remotefile not available. Results may take a bit longer than expected. <br/> \n";
      flush();
      // Try to push the script across.
      $retval = Push_Remotefile( $filetopush, $remotehost, $rlogin, $remotepath );
  }
  else {
      # Remote script is available in the remote /tmp
      # but we need to make sure it's up to date so we'll checksum it

      $remcksum = shell_exec(" ssh $rlogin@$remotehost \"cd $remotepath ; cksum $filename \" 2>&1 ");

      $loccksum = shell_exec("cd $localpath ; cksum $filename 2>&1");

      if ( "$remcksum" != "$loccksum" ) {
        print "Remote Checksum: ${remcksum} <br> Local Checksum: ${loccksum} <br> Remote $type is out of date.  Updating now. <br/>\n";
        flush();
        $retval = Push_Remotefile($filetopush, $remotehost, $rlogin, $remotepath );
      }
  }

  return $retval ;

// END OF FUNCTION update_remote_file()
}


function Push_Remotefile( $filetopush, $remotehost, $rlogin, $remotepath ) {
      // Try to push the script across.
      print "Attempting to push $filetopush to remote host $remotehost by scp. <br/> \n";
      print "\n<!-- DEBUG shell_exec(\" scp $filetopush  $rlogin@$remotehost:$remotepath &>/dev/null ; echo \$? \") -->\n";
      $result =  shell_exec(" scp $filetopush  $rlogin@$remotehost:$remotepath &>/dev/null ; echo \$? ");
      return trim($result) ;
}


