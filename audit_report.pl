
#!/usr/bin/perl

######################################################################################################################
# Script Description: Read the job config file, audit log file, ldif dumps to generate the user audit report.
#       Developer: Jack Lou
# Change History:
# 15/Sep/2015 - v1.0 initial working version
# 13/Oct/2015 - v2.0 more function added and ready to work in dev/test nas or dcs box.
######################################################################################################################

# Possible known actions:

=actions
/vretrieve/addnote.action
/vretrieve/closevretrievesession.action
/vretrieve/email.action
/vretrieve/find.action
/vretrieve/findresults.action
/vretrieve/logout.action
/vretrieve/performactions.action
/vretrieve/performactionschooseproperties.action
/vretrieve/performactionsdoemail.action
/vretrieve/performactionsdoproperties.action
/vretrieve/performactionsemail.action
/vretrieve/performactionsproperties.action
/vretrieve/performactionspropertieschosen.action
/vretrieve/performactionsstart.action
/vretrieve/printresults.action
/vretrieve/proofing/approvals.action
/vretrieve/proofing/excludelist.action
/vretrieve/proofing/selectproofingrun.action
/vretrieve/runtime/getdocument.action
/vretrieve/runtime/getdocumentlisting.action
/vretrieve/runtime/gettemplates.action
/vretrieve/sendemail.action
/vretrieve/setdefaulttemplate.action
/vretrieve/setproperties.action
/vretrieve/start.action
/vretrieve/streamdocument
/vretrieve/viewdocument.action
/vretrieve/viewnotes.action
/vretrieve/viewproperties.action
/vretrieve/webservices/metadataservice/updateDocumentProperties
/vretrieve/webservices/searchservice/doSearch
/vretrieve/webservices/searchservice/getResultCount
/vretrieve/webservices/searchservice/getSearchProfiles
/vretrieve/webservices/viewdocumentservice/getDocument
/vretrieve/webservices/viewdocumentservice/getSingleDocument
=cut


use strict;
use warnings;
use XML::Simple;
use IO::Handle;
use Time::localtime;
use DateTime;
#use PerlIO::gzip;
use File::Basename;
use Archive::Extract;

use PDF::API2;
use PDF::Table;


####################
# Global vars
####################

#store the total action count hash:
my %actioncount;
#store the uneque actions from audit log:
my %actions;
#store xml config element value:
my %tagval;

#current script running directory: should be:/vretrieve/apps/scripts/vr2uareport/
my $basedir = dirname(__FILE__);

#http host name that is the prefix of the http access.log files:
#access log base name: vr2-wg-prod-http-11_AACL.access.1.log or vr2-wg-prod-http-11.access for all jobs; bnz: bnz-ak-dr-http-01.datamail.co.nz.access.1.log
#my %httphosts = ( "bnz" => "bnz-ak-dr-http-01.datamail.co.nz", "bnzdr" => "", "shared" =>"vr2-wg-prod-http-11");

# constant variable for PDF::API2
use constant mm => 25.4 / 72;
use constant in => 1 / 72;
use constant pt => 1;

# variable for vretrieve database connection string
my $dbhost = "localhost";
my $db     = "vretrieve";
my $dbuser = "monitor_ro";
#not used here:
my $dbpass = "";
my $env = "";

####################
# End of global vars
####################


####################
# Init the related folder structure:
####################
#they are fixed folder in vr system: $basedir=/vretrieve/apps/scripts/vr2uareport
my $logdir = $basedir."/uar_log/";
# the job config files folder:
my $jobconfigdir = $basedir."/uar_config/";
my $ldif_out_dir = $basedir."/uar_ldif/";

# init audit log & ldif dump folders, will update after config loaded:
my $auditlogdir = $basedir."/uar_audit_logs/";

# default archive pdf file path:
my $pdfoutputdir = $basedir."/uar_archive/";

# first house keeping the log file, if more than 500 line, truncated the old line:
my $datetime = getYYYYMMDD(localtime());
$datetime =~ s/-/_/ig;
my $logname = "uar_log_".$datetime.".log";
my $logfile = $logdir.$logname;

# set up the log:
# File mode is open & append '>>', create a new if not existed.
open (my $lh, '>>', $logfile)  || die "can't open the log file: $logfile";
open (STDERR, ">>&=", $lh)    || die "can't redirect STDERR to log file";
$lh->autoflush(1);

# delete old log file that more than 14 days.
deleteOldFiles($logdir, 14);

#"./uar_config";
my @xmlfiles = getAllXMLConfig($jobconfigdir);

doAuditReport(@xmlfiles);

# At the end of script, close the log handler:
close (STDERR);
close ($lh);
##################### END OF THE SCRIPT ###############################


#########################################
# Function Blocks
#########################################

sub doAuditReport {
        my (@xmlfiles) = @_;

        # for each job config xml file, check the monitor folder's all files and check
        # if they are single and last modified time more than defined time stamp:
        foreach my $xmlfile (@xmlfiles) {

                #init global variable for each job:
                %actions = ();
                %actioncount = ();

                my $dt = getLogTime();
                %tagval = getXmlTagValue($xmlfile);
                my $org = $tagval{"OrgName"};
                $env = $tagval{"Env"};
                my $ldif_in = $tagval{"LDIFPath"};
                $auditlogdir = $tagval{"AuditLogPath"};

                my $oamhost_elname;
                # assemble the ldif dir in folder:
                if(lc $org eq "bnz") {
                        #dev/test env don't have bnz individual linux box, skip
                        if(lc $env eq "dev" || lc $env eq "test") {
                                print $lh "$dt: BNZ does not have $env environment. Skip the bnz job.\n";
                                next;
                        }
                        $oamhost_elname = uc "OamHostBNZ".$env;
                } else {
                        $oamhost_elname = uc "OamHost".$env;
                }
                my $oamhost = $tagval{$oamhost_elname};
                $ldif_in =~ s/\$oamhost/$oamhost/i;

                #print "ldif in folder for $org is: $ldif_in\n";
                #next;

                print $lh "##############################################################\n";
                print $lh "$dt: Start processing $org audit report ... \n";

                ## check the run date and run type, if not matched, skip to next job:
                my ( $auditdir, $runtype, $rundate, $dayofwk, $today );
                #will run the script in individual server, so bnz dr /bnz prod /weg prod are all same structure:
                $auditdir = $auditlogdir.$org;
                $runtype = lc $tagval{"RunFrequence"};
                # rundate = monday, tuesday, wednesday, thursday, friday, saturday and sunday:
                $rundate = lc $tagval{"ReportRunDate"};
                my $nzdt = DateTime->now ( time_zone => 'Pacific/Auckland' );
                $dayofwk = lc $nzdt->day_name;
                #($day, $month, $year) = (localtime)[3,4,5];
                $today = $nzdt->mday;
                $today = substr( "00".$today, -2, 2 );

                #//skip this job's run if not it's rundate:
                if($runtype eq "weekly" && ($rundate ne $dayofwk && $rundate ne "any") ) {
                          print $lh "$dt: Job running date is $rundate in the $org job config, skip this jobs process.\n";
                                next;
                }
                if($runtype eq "monthly") {
                                $rundate = substr( "00".$rundate, -2, 2 );
                                #both two digit day only: 09 == 09 etc.
                                if($today ne $rundate) {
                                        next;
                                }
                }
                print $lh "$dt: Matched the $runtype and run date $rundate for $org job, processing the ldif dump file first.\n";

                my $ldiffile = getLdifFile($ldif_in, $org, $env);
                if( ! -f $ldiffile ) {
                        print $lh "$dt: Fatal Error: Can not find the ldif user file for $org organization.\n";
                        die;
                }
                print $lh "$dt: Found the LDIF file $ldiffile for $org job.\n";
                print $lh "$dt: Get all groups/users details under the $org organization.\n";
                my($ra_admin_usr, $ra_nonadmin_grp, $rh_nonadmin_usr, $rh_user_logintime) = getUserListFromLdif($org, $ldiffile);

                #               generate the user detail reports:
                #               foreach my $usr ( sort @{$ra_admin_usr} ) {
                #                       print "Admin user is: $usr\n";
                #               }
                #               foreach my $grp ( sort @{$ra_nonadmin_grp} ) {
                #                               print "non admin group: $grp\n";
                #               }
                #foreach my $k ( sort keys  %{$rh_nonadmin_usr} ) {
                        #print "non admin user: $k - ${$rh_nonadmin_usr}{$k}\n";
                #}

                #process all audit log in the date range, it will retun %actioncount and %actions
                #global hash variable:
                print $lh "$dt: processing the audit log for the job.\n";
                my ($startdate, $enddate) = processAuditLogFiles($org, $auditdir, $runtype);

                # now build the reports according the details:
        #               foreach my $act ( sort keys  %actions ) {
        #                       print "Actions: $act\n";
        #               }
        #
        #               foreach my $uid ( sort keys  %actioncount ) {
        #                       #my %ac = $actioncount{$uid};
        #                       foreach my $a (sort keys  %{$actioncount{$uid}}) {
        #                               print "User $uid - $a = $actioncount{$uid}{$a}\n";
        #                       }
        #               }

        # process all http log and find the last login date if a uid has not been accessed withing this two weeks:
        # I will use ldif dump file that include the ds-pwp-last-login-time attributes to get the last login time.
        #processHttpLogFiles($org);

                # function to generate the pdf report;
                my $pdfname =  $tagval{"ReportFileName"};
                my $archivepath = $tagval{"AuditReportArchivePath"};
                # if file path in config not existed, use default one:
                if (! -d $archivepath) {
                        $archivepath = $pdfoutputdir;
                }

                my $ts = getTimeStamp();
                $pdfname =~ s/\$TimeStamp/$ts/i;
                $pdfname = $archivepath.$pdfname;

                # delte old archived pdf file that more than 14 days:
                print $lh "$dt: Delete old pdf file that more than 14 days.\n";
                deleteOldFiles($archivepath, 14);

                print $lh "$dt: Generate the $org audit report $pdfname.\n";
                generatePDFReport($startdate, $enddate, $pdfname, $ra_admin_usr, $ra_nonadmin_grp, $rh_nonadmin_usr, $rh_user_logintime);

                print $lh "$dt: End processing $org audit report.\n";

                #send report to client by email:
                my $subject = $tagval{"EmailSub"}." - ".$env;
                #replace $RunFrequence in the subject element value:
                $subject =~ s/\$RunFrequence/$tagval{"RunFrequence"}/i;
                my $message = $tagval{"EmailBody"};
                #It is also possible to specify multiple recipients by joining them with a comma
                my $to = $tagval{"EmailTo"};
                my $cc = $tagval{"EmailCc"};
                my $bcc = '';
                #To specify a "FROM" name and address, use the "-r" option. The name should be followed by the address wrapped in "<>".
                #my $frm = 'vrautomation@nzpost.co.nz';
                sendMail($subject, $message, $to, $cc, $bcc, $pdfname);

        } # end of foreach xml loop.

} # end of doAuditReport function.


#########################################
# Get the ldif file according the org name:
sub getLdifFile {
  # return list of log files, with path relative to auditdir
  my ($ldif_dir_in, $org) = @_;
  my $dt = getLogTime();
  my $today = getYYYYMMDD(localtime());
  my $ldfile;

  if ( ! -d $ldif_dir_in ) {
        print $lh "$dt: Fatal Error - LDIF directory $ldif_dir_in does not exist\n";
    die;
  } elsif ( ! opendir ADIR, $ldif_dir_in ){
        print $lh "$dt: Fatal Error - Cannot open ldif dir $ldif_dir_in\n";
    die;
  }

  #today's ldif file: opendj-2015-10-13-070301.ldif.gz
  my $pregzldif = "opendj-".$today;
  if ( lc $org eq 'bnz' ) {
      $pregzldif = "opends-".$today;
  }
  print $lh "$dt: going to find todays ldif file in folder: $ldif_dir_in\n";
  #my $env = $tagval{"Env"};
  my $file_out;
        while (my $file = readdir ADIR){
                next if (! -f "$ldif_dir_in/$file" );
                if ( $file =~ /^($pregzldif-\d+\.ldif)\.gz$/ ) {

                        print $lh "$dt: Found the matched ldif file: $file.\n";

                        $ldfile = $ldif_dir_in."/".$file;
                        $file_out = $1;
                        last;
                }
        }

        #die if no ldif file found, please note, some time it's permission issue,
        #must have rwx permission for vretrieve:vretrieve to vr2-wg-dev-nas-11/vretrieve/backups/vr2-wg-dev-oam-11 folder.
        if ( ! -f $ldfile ) {
                print $lh "$dt: Fatal Error - no ldif dump file found.\n";
            die;
        }

        #empty the ldif output folder:
        deleteOldFiles($ldif_out_dir, 1);

        my $ae = Archive::Extract->new( archive => $ldfile );
        $ae->extract( to => $ldif_out_dir ) or print $lh "Error extract the ldif file $ldfile.\n".$ae->error;

  return $ldif_out_dir.$file_out;
} # end of getAuditLogList function.

###############################################
# get all audit log files that matched the run date:
sub processAuditLogFiles {
        my ( $org, $auditdir, $runtype ) = @_;
        my ( $today, $startdate, $enddate );
        # logic the log file start and end date range:
        if($runtype eq "daily") {
                #get today's date:
                $startdate = getYYYYMMDD(localtime());
                $enddate = $startdate;
        } elsif ($runtype eq "weekly") {
                #get the current week start date:
                #it's 6 day's before today's date:
                $today = DateTime->now();
                $today->add( days => -6 );
                # date as yyyy-mm-dd format:
                $startdate = $today->ymd;
                $enddate = DateTime->now()->ymd;
                #print $dt->ymd."\n";# . ' ' .  $dt->hms;
        } elsif ($runtype eq "monthly") {
                #get the today's date at the previous month:
                $today = DateTime->now();
                $today->add( months => -1 );
                # date as yyyy-mm-dd format:
                $startdate = $today->ymd;
                $enddate = DateTime->now()->ymd;
        }

        my @logfiles = getAuditLoglist($auditdir, $startdate, $enddate);
  # loop each log file and process it:
  foreach my $logfile (@logfiles) {
    getUserActionDetails($org, $logfile);
  }

  return ($startdate, $enddate);

}

#########################################
# Get the log list files from audit log directory;
sub getAuditLoglist {
  # return list of log files, with path relative to auditdir
  my ($auditdir, $start, $end ) = @_;
  my @logfiles;
  my $dt = getLogTime();
  # list content of audit dir and processed dir
  foreach my $dir ($auditdir , "$auditdir/processed" ){
          if ( ! -d $dir ) {
                print $lh "$dt: Fatal Error - Audit directory $dir does not exist\n";
            die;
          } elsif ( ! opendir ADIR, $dir ){
                print $lh "$dt: Fatal Error - Cannot open audit dir $dir\n";
            die;
          }
          print $lh "$dt: check audit log inside $dir directory.\n";
                while ( my $file = readdir ADIR ) {
                  next if ( ! -f "$dir/$file" || $file !~ /^\d\d\d\d-\d+-\d+\.log$/ );
                  my ($filedate) = ( $file =~ /^(\d\d\d\d-\d+-\d+)\.log$/ );
                  my $epoch = yyyymmdd_to_epoch($filedate);
                  my $epoch_start = yyyymmdd_to_epoch($start);
                  my $epoch_end = yyyymmdd_to_epoch($end);
                  #skip those not in the date range's audit log files:
                  next if ( $epoch > $epoch_end || $epoch < $epoch_start );
                  push @logfiles, "$dir/$file";
                  print $lh "$dt: The valid audit log: $dir/$file.\n";
                }
        }
        return @logfiles;

} # end of getAuditLogList function.


#########################################
# get the base file name, strip off the path etc. get the active user list
# and the action count etc. details.
sub getUserActionDetails {
  my ($org, $logfile) = @_;
  # init log time:
  my $dt = getLogTime();

  if (! open FILE, "<$logfile" ){
    print $lh "$dt: FATAL ERROR: Cannot open $logfile, ignore this log.\n";
    next;
  }

  my $filedate = getFileNameOfFullPath($logfile);
  $filedate =~ s/(.*)\.\w+$/$1/;

  while ( my $line = <FILE> ){
    my ( $linedate, $lineday, $linehour, $lineorg, $uid, $srcip, $action, $params ) = getLogLineDetails($line);
                next if ( $linedate eq "SKIP" );
    if ( $lineorg ne $org ){
      print $lh "$dt: FATAL ERROR: Found entry for org '$lineorg' in logfile for '$org', $logfile\n";
    }

                #    my $datekey = $filedate;
                #    if ( $freq =~ /^h/i ) {
                #      # if frequency option has been set to hourly and append the hour to the time key
                #      $datekey = "$filedate $linehour:00";
                #    }
                # %actioncount = { uid1 => { start.action => 10, addnote.action =>5, ...}, uid2 => { start.action => 9, }, .... }
    if ( ! exists( $actioncount{$uid}{$action} ) ){
      $actioncount{$uid}{$action} = 0;
    }
    my $count = $actioncount{$uid}{$action};
    #assing the same action with the new count number:
    $actioncount{$uid}{$action} = $count + 1;
  }

} #End of function.


#########################################
# Parse each log line, extract useful information from the line.
sub getLogLineDetails {

  my ($line) = @_;
  # Example line from log file:
  # May 21 09:44:52  192.168.136.130  vr-audit  BPO  13  5443 1  GET  202.168.50.40  /vretrieve/viewdocument.action  &documentID=106513804
  # 0   1  2       3                      4         5         6    7   8  9     10                         11             12                              13
  # Aug 21 08:20:31 localhost         vr-audit  FLTP            Adam.Hooper@fleetpartnersnz.co.nz       1  GET      220.101.33.251  /vretrieve/start.action
  # split the line by whitespace
  # Note that there is a space and a tab between the time and the IP address

        # Some log entries have entire multi-line note or email bodies which we don't want to parse, so skip line if it doesn't look like a date at the start:
  return "SKIP" if ( $line !~ /^\w\w\w\s\d\d\s\d\d:\d\d:\d\d\s.*\svr-audit\s.*(?:GET|POST)/ );

  chomp $line;
  my @parts = split(/\s/, $line );
  my $date = join(" ", ($parts[0], $parts[1], $parts[2]) );
  my $day = join(" ", ($parts[0], $parts[1]) );
  my $time = $parts[2];
  my $hour = (split(/:/,$time))[0];
  my $lineorg = $parts[6];
  my $uid = $parts[8];
  my $srcip = $parts[11];
  my $action = $parts[12];

  # Strip the end off streamdocument path /vretrieve/streamdocument/110141577/input_0000006347.pdf
  #$action =~ s/(\/vretrieve\/streamdocument).*/$1/;
  #merge streamdocument = viewdocument.action;
  $action =~ s/(\/vretrieve\/streamdocument).*/viewdocument.action/;
  # Strip off the /vretrieve/ prefix
  $action =~ s/^\/vretrieve\/(.*)$/$1/;

  $actions{$action} = 1; # Unique list of actions

  my $params = '';
  # $#parts - the lengh of the array.
  for ( my $i = 13 ; $i <= $#parts ; $i++ ){
    $params = "$params ". $parts[$i];
  }

  # Should probably add in a check to ensure that parts[5] is the correct org.

  return ( $date, $day, $hour, $lineorg, $uid, $srcip, $action, $params );

} #End of function.

#########################################
# get the base file name, strip off the path and remain the file name only: 2015-09-18.log
sub getFileNameOfFullPath {
  my ( $filename ) = @_;
  $filename =~ s/.*\/([^\/]+)$/$1/;
  return $filename;
}

# main function to process the ldif dumps everyday to get the org user list details
# the detail will used to generate the pdf report.
# return; @admin_user arrays; @nonadmin_grp non admin user groups array;
# %nonadmin_usr: non admin user +> 1 hash.
sub getUserListFromLdif {
        my ( $org, $ldiffile ) = @_;
        my $dt = getLogTime();
        # tmp ldif file:
        #my $uar_ldif_file = "/opendj-2015-09-14-070301.ldif";
        #$tagval["LDIFPath"]
        #open IN, "<:gzip", "somefile.gz" or die "$!\n";
        #open OUT, ">:gzip", "numbered.gz" or die "$!\n";
        #while (<IN>) {
        #    print OUT join " ", ++$i, $_;
        #}

        print $lh "$dt: Process ldif $ldiffile file.\n";
        if (! open ( LDIF, "<$ldiffile" ) ) {
                print $lh "$dt: Can not open ldif $ldiffile for reading.\n";
                return;
  }

  my @admin_usr;
  my %nonadmin_usr; #will be {uid => 1}, i.e. only one uid allow inserted.
  my @nonadmin_grp; # store non admin group name in this array.
  my %userlastlogin; # store user's last login time if any.
  my $uid;
  # add the admin group name in the first element of array:
  # push @admin_usr, $org." SWS User administration";

  while ( <LDIF> ){
    # find any users between: "$org SWS User Admininistration" lines and blank line.
    if (/^dn\:\s+cn\=$org\s+SWS\s+User\s+administration/i) {
        #push @grabbed, $_;
        #print "Fount SWS user header line: $_\n";
        while (<LDIF>) {
            last if /^$/;
            #find the uid: uniqueMember: uid=dmadmin-msd-wi@datam.co.nz,ou=people,dc=datamail,dc=co,dc=nz
            if (/^uniqueMember\:\s+uid\=([^,]+)\,/i) {
                #print "Fount SWS user: $1\n";
                push @admin_usr, $1;
                }
        }
    }
    # second round try to find the non admin users:
    if (/^dn\:\s+cn\=($org\s+(?!(SWS\s+User\s+administration))[^\,]*)\,/i) {
                        #print "Fount non admin group 1: $1\n";
                        push @nonadmin_grp, $1;
                        while (<LDIF>) {
        last if /^$/;
        #find the uid: uniqueMember: uid=dmadmin-msd-wi@datam.co.nz,ou=people,dc=datamail,dc=co,dc=nz
        if (/^uniqueMember\:\s+uid\=([^,]+)\,/i) {
         #print "Fount SWS user: $1\n";
         $nonadmin_usr{$1} = 1;
         #$uid = $1;
        }
        }
    }

    # third round try to find each person's last login time:
    if (/^dn\:\s+uid\=([^\@]+\@[^\,]+)\,/i) {
                        #print "Fount each user's uid: $1\n";
                        $uid = $1;
                        while (<LDIF>) {
        last if /^$/;
        #find the "ds-pwp-last-login-time:" attribute value if any:
        if (/^ds-pwp-last-login-time\:\s*(\d+)Z/i) {
         #print "$uid got ds-pwp-last-login-time: $1\n";
         $userlastlogin{$uid} = $1;
        }
        }
    }

        }
        close(LDIF);
        return (\@admin_usr, \@nonadmin_grp, \%nonadmin_usr, \%userlastlogin);
} # end of function.

################
# convert the yyyy-mm-dd to the epoch format, we can compair:
sub yyyymmdd_to_epoch {
  my ($date) = @_;
  my $dt = getLogTime();
  if ( $date !~ /^(\d\d\d\d)-(\d+)-(\d+)/ ) {
        print $lh "$dt: Fatal Error - Date '$date' is in an invalid format.  Should be yyyy-mm-dd\n";
    die;
  }
  my ( $yyyy, $mm, $dd ) = ( $date =~ /^(\d\d\d\d)-(\d+)-(\d+)/ );
  use Time::Local;
  my $epoch = timelocal( 0, 0, 0, $dd, $mm-1, $yyyy);
  return $epoch;
}

#########################################
# get current week's date hash: {Monday => 2015-09-18, Tuesday => ....}
sub getThisWeeksDate {
        #get monday's date of this week:
        my $start_of_week = DateTime->today()->truncate( to => 'week' );
        #print "week date: $start_of_week\n";
        my %hday;
        foreach ( 0..6 ) {
            #my $d = $start_of_week->clone()->add( days => $_ )."\n";
            my $d = $start_of_week->clone()->add( days => $_ );
            $d =~ s/^(\d+\-\d+\-\d+)T.*$/$1/i;
            if($_ == 0) {
                $hday{"Monday"} = $d;
            }
            if($_ == 1) {
                $hday{"Tuesday"} = $d;
            }
            if($_ == 3) {
                $hday{"Wednesday"} = $d;
            }
            if($_ == 4) {
                $hday{"Thursday"} = $d;
            }
            if($_ == 5) {
                $hday{"Friday"} = $d;
            }
            if($_ == 6) {
                $hday{"Saturday"} = $d;
            }
            if($_ == 7) {
                $hday{"Sunday"} = $d;
            }
        }
        return \%hday;
}


#########################################
# convert local time to yyyy-mm-dd format to match with the audit log file:
sub getYYYYMMDD {
  my ($tm) = @_;
  my ($dd, $mm, $yyyy) = ($tm->mday, $tm->mon, $tm->year);
  #my ( $YYYY, $MM, $DD, $hh, $mm, $ss ) =
  #             ( $lt[5], $lt[4], $lt[3] , $lt[2], $lt[1],  $lt[0] );
  $yyyy = $yyyy + 1900;
  $mm = $mm+1;
  #padding 0 if only one digit:
  $mm = substr( "00".$mm, -2, 2 );
  $dd = substr( "00".$dd, -2, 2 );
  my $ymd = "$yyyy-$mm-$dd";
  return $ymd;
}

#########################################
# convert local time to yyyy-mm-dd HH:MM:SS format for the log time:
sub getLogTime {
        my $tm = localtime();
  my ( $yy, $mm, $dd, $hh, $min, $ss ) =
  ($tm->year, $tm->mon, $tm->mday, $tm->hour, $tm->min, $tm->sec );
  $yy = $yy + 1900;
  $mm = $mm+1;
  #padding 0 if only one digit:
  $mm = substr( "00".$mm, -2, 2 );
  $dd = substr( "00".$dd, -2, 2 );
  $hh = substr( "00".$hh, -2, 2 );
  $min = substr( "00".$min, -2, 2 );
  $ss = substr( "00".$ss, -2, 2 );
  my $ymdhhmmss = "$yy-$mm-$dd $hh:$min:$ss";
  return $ymdhhmmss;
}

#########################################
# convert local time to yyyymmddHHMMSS format: 20151002193025
sub getTimeStamp {
        my $tm = localtime();
  my ( $yy, $mm, $dd, $hh, $min, $ss ) =
  ($tm->year, $tm->mon, $tm->mday, $tm->hour, $tm->min, $tm->sec );
  $yy = $yy + 1900;
  $mm = $mm+1;
  #padding 0 if only one digit:
  $mm = substr( "00".$mm, -2, 2 );
  $dd = substr( "00".$dd, -2, 2 );
  $hh = substr( "00".$hh, -2, 2 );
  $min = substr( "00".$min, -2, 2 );
  $ss = substr( "00".$ss, -2, 2 );
  my $ymdhhmmss = "$yy$mm$dd$hh$min$ss";
  return $ymdhhmmss;
}

# loading in the entire xml file to get the content of one tag is very inefficient
# and slow when run over lots of files.  Instead we'll parse the file using perl
# and stop parsing as soon as we have what we want.
# Tag name should be passed without brackets, eg. <ClientName>..</ClientName>
# should be sent as just 'ClientName'
# only works for simple, single tags, only returns the value for the first instance of that tag
sub getTagContent {
    my ( $file, $tag ) = @_;
    if (! open ( XMLFILE, "<$file" ) ) {
        print "Cannot read file $file\n";
        return;
    }
    while(<XMLFILE>){
        if ( /<$tag>([^<]+)<\/$tag>/ ){
            close XMLFILE;
            return $1;
        }
    }
}

###########################################################################################################
# loop through each line and get element and value
# into the hash. Only works with single level of xml config file, following is the element list:
###########################################################################################################
# ReportName = Bank of New Zealand User Audit Report
# OrgFullName = Bank of New Zealand
# OrgName = BNZ
# ReportFormat = PDF
# AuditJobConfigPath = ./uar_config
# AuditLogPath = ./uar_audit_logs
# AuditReportArchivePath = ./uar_archive
# LDIFPath = ./uar_ldif
# RunFrequence = Weekly
# ReportRunDate = Sunday
# ReportRunTime = 22:00
# EmailSub = VRetrieve User Audit $RunFrequence Report
# EmailFrom = VR2ReportAutomation@nzpost.co.nz
# EmailTo = jack.lou@nzpost.co.nz,jack.lou@datamail.co.nz
###########################################################################################################
sub getXmlTagValue {
    my ( $file) = @_;
    if (! open ( XMLFILE, "<$file" ) ) {
        print "Cannot read file $file\n";
        return;
    }
    my %kv;

    while(<XMLFILE>){
        if ( /<([^>]+)>([^<]+)<\/[^>]+>/ ){
            #close XMLFILE;
            #return $1;
            $kv{$1} = $2;
        }
    }
    return %kv;
}

##############################
# Fuction Description: Get all xml job config file located under ./uar_config
# using Mail::Sender perl module. Please note, might be need install the module in
# Monlan/Tudra if haven't been installed yet.
##############################
sub getAllXMLConfig {
    # Return full paths of all file mon job config xml file:
    my ( $xmldir ) = @_;
    my @list;
    my $dt = getLogTime();
    opendir(DIR, $xmldir ) or die "Error - cannot open job config xml dir $xmldir";

    while (my $file = readdir DIR){
        if ( $file =~ /^uar_.*\.xml$/i ){
                print $lh "$dt: Found the job config: $file.\n";
          push (@list, $xmldir."/".$file);
        }
    }

    return @list;
}

#################
# Email
#################

sub sendMail {
  # by default the Mail::Mailer module is not installed so
  # we cannot guarantee its availability.
  # Instead we'll use a messy system call to the mail command.
  # TO, CC etc variables should contain a comma delimited list of email addresses.
  my ($subject, $message, $to, $cc, $bcc, $attachment ) = @_ ;

        if ($cc) {
        $cc = " -c $cc " ;
  } else {
        $cc = '';
  }
  $bcc = " -b $bcc " if ($bcc);
  my $date = localtime();
  my $hostname = qx(hostname);
  chomp $hostname;
  $message =~ /^(\w+)/;
  #$subject  = $self." $1" if (! $subject);

  #my $command = "mail -s'$subject' $cc $bcc $to <<EOT
  my $command = "mail -s'$subject' -a $attachment $cc $bcc $to <<EOT
  $message

  This email has been sent by the NZPost VRetrieve Report Automation System.

  Kind Regards

EOT
";

        my $dt = getLogTime();
        my $retval = qx($command);
  print $lh "$dt: Email command execute status: $retval.\n";
  if ( $retval eq '') {
                print $lh "$dt: User Audti Report has been send to $to, cc to $cc.\n";
        }
        return;
}

####################################
# House keeping, delete the web log file that more than two weeks
sub deleteOldFiles {
        my ( $file_dir, $file_age ) = @_;

        #read the directory
        opendir(DIR, $file_dir) or die "Can not open the directory $file_dir\n";

        my @files;
        my $name;
        my $dt = getLogTime();

         while($name = readdir(DIR)) {
                 #save data file names in an array, don't include . and ..
           push(@files, $name) if( !($name eq '.' || $name eq '..' || $name eq '.svn') );
         }

         # check each files, if more than 30 days, delete them:
         foreach my $f (@files) {
                 $f = $file_dir."/".$f;
                 #print "log file: $f\n";
                 my $file_age_days = -M $f;
                 if($file_age_days > $file_age) {
                                        print $lh "$dt: Delete old file $f more than $file_age days.\n";
                                        #print "$dt: Delete old log file: $f\n";
                                        unlink($f)
                                        #don't die if can not delete the file;
                                        #or die "Can not delete the old file $f\n";
                 }
         }

        closedir(DIR) or  die "Can not close directory $file_dir\n";

}

##############################
# Fuction Description: Query to the vr database and get all content groups name
# will do it later, currently we use ldap data only for simplicity.
##############################
sub getOrgGroups {
    # Return full paths of all file mon job config xml file:

}

##############################
# Fuction Description: command to query the vr postgres database,
# return the results from the query.
##############################
sub dosql {

        # Run SQL and return results in an array
        my ($sql) = @_;
        my @output;
        my $psql = "psql -tAq -h $dbhost -d $db -U $dbuser";
        pgpass();

        #print "DEBUG SQL:  $sql\n";

        open( SQLOUT, "echo \"$sql\" | $psql |" );

        while (<SQLOUT>) {
                chomp;
                push @output, $_;
        }

        return @output;

}

sub pgpass {

        # Since we don't have pg module available and can't pass a password to
        # psql we'll use a .pgpass file, creating it if necessary

        my $pgpassfile = ".pgpass";
        my $pgpassline = "$dbhost:5432:$db:$dbuser:$dbpass\n";

        # See if the pgpass file already has this entry
        if ( -f $pgpassfile ) {
                open( PGPASS, "< $pgpassfile" ) or die "Cannot open $pgpassfile";
                while (<PGPASS>) {
                        return if (/^$pgpassline$/);
                }
                close PGPASS;
        }

        # If not then add the line.
        open( PGPASS, ">> $pgpassfile" ) or die "Cannot open $pgpassfile";
        print PGPASS $pgpassline;
        chmod 0600, $pgpassfile;
}

######################################################################################################################
# Function Description: the core part to generate the pdf audit report
#                                                                                               according the ldif data and audit log file
# Change History:
# v1.0 15/Sep/2015 initial version based on the code from http://rick.measham.id.au/pdf-api2/
######################################################################################################################
sub generatePDFReport {
                my ( $startdate, $enddate, $pdfname, $ra_admin_usr, $ra_nonadmin_grp, $rh_nonadmin_usr, $rh_user_logintime ) = @_;
                #my ( $paragraph1, $paragraph2, $picture ) = get_data();
                #my $pdf = PDF::API2->new( -file => $pdfname );
                my $pdf = PDF::API2->new(); #use dynamic name first.
                my $pdftable = new PDF::Table;
                my $page = $pdf->page();
                ## A4 as defined by PDF::API2 is h=842 w=545 for portrait
                $pdf->mediabox('A4');

                #my $page = $pdf->page;
                #$page->mediabox( 105 / mm, 148 / mm );
                #$page->bleedbox(  5/mm,   5/mm,  100/mm,  143/mm);
                #$page->cropbox( 7.5 / mm, 7.5 / mm, 97.5 / mm, 140.5 / mm );
                #$page->artbox  ( 10/mm,  10/mm,   95/mm,  138/mm);

                my %font = (
                    Helvetica => {
                        Bold   => $pdf->corefont( 'Helvetica-Bold',    -encoding => 'latin1' ),
                        Roman  => $pdf->corefont( 'Helvetica',         -encoding => 'latin1' ),
                        Italic => $pdf->corefont( 'Helvetica-Oblique', -encoding => 'latin1' ),
                    },
                    Times => {
                        Bold   => $pdf->corefont( 'Times-Bold',   -encoding => 'latin1' ),
                        Roman  => $pdf->corefont( 'Times',        -encoding => 'latin1' ),
                        Italic => $pdf->corefont( 'Times-Italic', -encoding => 'latin1' ),
                    },
                    Arial => {
                        Bold   => $pdf->corefont( 'Arial-Bold',   -encoding => 'latin1' ),
                        Roman  => $pdf->corefont( 'Arial',        -encoding => 'latin1' ),
                        Italic => $pdf->corefont( 'Arial-Italic', -encoding => 'latin1' ),
                    },
                );

                # 1. create the pdf header:
                my $blue_box = $page->gfx;
                $blue_box->fillcolor('#0078A6');
                # LEFT, TOP dimension in mm (for the left, bottom corner),
                # width, height in mm (from left bottom corner to right and up):
                #$blue_box->rect( 5 / mm, 125 / mm, 95 / mm, 18 / mm );
                $blue_box->rect( 10 / mm, 250 / mm, 190 / mm, 36 / mm );
                $blue_box->fill;

                # put the header text in the blue box:
                my $headline_text = $page->text;
                $headline_text->font( $font{'Arial'}{'Bold'}, 16 / pt );
                $headline_text->fillcolor('white');
                #To place text, we need to move the cursor to the point at which we want to place it.
                #It's at the right side of the artbox (95mm) and 131mm from the bottom of the mediabox.
                $headline_text->translate( 105 / mm, 270 / mm );
                $headline_text->text_center(uc ($tagval{"OrgName"}." ".$tagval{"Env"}." ".$tagval{"ReportName"}));
                #can user: 1. $headline_text->translate( 25 / mm, 260 / mm ); 2. distance(26/mm, -10/mm) or:
                #$headline_text->cr(-10/mm); # carrige return + line feed:
                $headline_text->translate( 105 / mm, 260 / mm );
                $headline_text->text_center(uc ("( From $startdate T0 $enddate ".$tagval{"RunFrequence"}." )"));

                # background of the page:
                my $background = $page->gfx;
                $background->strokecolor('lightgrey');
                #$background->circle( 20 / mm, 45 / mm, 45 / mm );
                #$background->circle( 18 / mm, 48 / mm, 43 / mm );
                #$background->circle( 19 / mm, 40 / mm, 46 / mm );
                $background->stroke;

                # 2. build the user data for user's table:
                my @admin_tbl_data;
                #push @admin_tbl_data, ['Admin Users', 'Non Admin Users'];
                push @admin_tbl_data, ['Admin Users', 'Last Login Time (yyyymmddhhmmss)'];
                #sort as alphabetical order:
                my @st_adminuser = sort { lc($a) cmp lc($b) } @{$ra_admin_usr};
                my $adminuser = shift(@st_adminuser);

                my $admin_lastlogin = '';
                #get corresponding admin user's last login time:
                if( exists ${$rh_user_logintime}{$adminuser} ) {
                        $admin_lastlogin = ${$rh_user_logintime}{$adminuser};
                }

                foreach my $u ( @st_adminuser ) {
                        $adminuser = $adminuser."\n".$u;
                        if ( exists ${$rh_user_logintime}{$u} ) {
                                $admin_lastlogin = $admin_lastlogin."\n".${$rh_user_logintime}{$u};
                        }       else {
                                $admin_lastlogin = $admin_lastlogin."\n".'-';
                        }

                }
                #print "Admin user list: $adminuser\n";
                push @admin_tbl_data, [$adminuser, $admin_lastlogin];

                # build the non admin user data table:
                my @nonadmin_tbl_data;
                #push @admin_tbl_data, ['Admin Users', 'Non Admin Users'];
                push @nonadmin_tbl_data, ['Non Admin Users', 'Last Login Time (yyyymmddhhmmss)'];
                my $nonadmin_lastlogin = '';
                my @nonadmin = sort (keys %{$rh_nonadmin_usr});
                my $nonadminuser = shift (@nonadmin);
                if( exists ${$rh_user_logintime}{$nonadminuser} ) {
                        $nonadmin_lastlogin = ${$rh_user_logintime}{$nonadminuser};
                }

                foreach my $nonadm ( @nonadmin ) {
                        $nonadminuser = $nonadminuser."\n".$nonadm;
                        if ( exists ${$rh_user_logintime}{$nonadm} ) {
                                $nonadmin_lastlogin = $nonadmin_lastlogin."\n".${$rh_user_logintime}{$nonadm};
                        }       else {
                                $nonadmin_lastlogin = $nonadmin_lastlogin."\n".'-';
                        }
                }
                #print "Non Admin user list: $nonadminuser\n";

                push @nonadmin_tbl_data, [$nonadminuser, $nonadmin_lastlogin];

                # 3. build the user action data for action details table:
                my @action_data;
                my $actionlist;
                my $countlist;
                push @action_data, ['Active Users', 'Actions User Performed', 'Count'];
                foreach my $uid ( sort keys  %actioncount ) {
                        #my %ac = $actioncount{$uid};
                        my @al = sort (keys %{$actioncount{$uid}});
                        $actionlist = shift (@al);
                        $countlist = $actioncount{$uid}{$actionlist};
                        foreach my $a (@al) {
                                my $b = $a;
                                $b =~ s/start.action/LoggedIn/i;
                                $actionlist = $actionlist."\n".$b;
                                $countlist = $countlist."\n".$actioncount{$uid}{$a};
                        }
                        #print "Action List: $actionlist\n";
                        #print "Count List: $countlist\n";
                        #each $uid save to the array list:
                        push @action_data, [$uid, $actionlist, $countlist];
                }

                # 4. build the admin user's details table:
                # A4 page size:  210 ▒ 297 mm
                $pdftable->table(
                # required params
                $pdf,
                $page,
                \@admin_tbl_data,
                #$blue_box->rect( 10 / mm, 250 / mm, 190 / mm, 36 / mm );
                # Geometry of the document
                x  => 10/mm,
                -w => 190/mm, # dashed params supported for backward compatibility. dash/non-dash params can be mixed;# width of table start from x.
                start_y  => 240/mm,     # Y coordinate of upper left corner of the table at the initial page. ( measured from bottom up ).
                -start_h => 225/mm, # height of the table of init page.
                next_y   => 270/mm,
                next_h   => 250/mm,

                # some optional params for fancy results
                -padding              => 5/mm,
                #padding_left         => 10/mm,
                #padding_right         => 10/mm,
                #background_color_odd  => 'lightblue',
                #background_color_even => "#EEEEAA",     #cell background color for even rows
                header_props          => {
                        padding                   => 10/mm,
                        justify    => "left",
                        #bg_color   => "#F0AAAA",
                        bg_color   => "#0078A6",
                        font       => $pdf->corefont( "Arial", -encoding => "utf8" ),
                        font_size  => 15,
                        font_color => "white",
                        repeat     => 1
                },
                column_props => [
                        {
                                max_w      => 100/mm,
                                justify    => "left",
                                font       => $pdf->corefont( "Arial", -encoding => "latin1" ),
                                font_size  => 12,
                                font_color => 'black',
                                #background_color => '#8CA6C5',
                        },                                 #no properties for the first column
                        {
                                max_w      => 80/mm,
                                justify    => "left",
                                font       => $pdf->corefont( "Arial", -encoding => "latin1" ),
                                font_size  => 12,
                                font_color => 'black',
                                #background_color => '#8CA6C5',
                        },
                ],

                #       cell_props => [
                #               [ #This is the first(header) row of the table and here wins %header_props
                #                       {
                #                               background_color => '#000000',
                #                               font_color       => 'blue',
                #                       },
                #
                #                       # etc.
                #               ],
                #               [    #Row 2
                #                       {    #Row 2 cell 1
                #                               background_color => '#000000',
                #                               font_color       => 'white',
                #                       },
                #                       {    #Row 2 cell 2
                #                               background_color => '#AAAA00',
                #                               font_color       => 'red',
                #                       },
                ##                      {    #Row 2 cell 3
                ##                              background_color => '#FFFFFF',
                ##                              font_color       => 'green',
                ##                      },
                #
                #                       # etc.
                #               ],
                #               [        #Row 3
                #                       {    #Row 3 cell 1
                #                               background_color => '#AAAAAA',
                #                               font_color       => 'blue',
                #                       },
                #
                #                       # etc.
                #               ],
                #
                #               # etc.
                #       ],
                # End of cell properties define.

        );

        # 5. build the non admin user details table:
        #append another talbe after:
        # use new page, will automatically append the page to the end of current pdf
        # if use page($pagenumber), it will insert after $pagenumber of the pdf:
        my $nonadminpage = $pdf->page();
        $pdftable->table(
                # required params
                $pdf,
                $nonadminpage,
                \@nonadmin_tbl_data,
                #$blue_box->rect( 10 / mm, 250 / mm, 190 / mm, 36 / mm );
                # Geometry of the document
                x  => 10/mm,
                -w => 190/mm, # dashed params supported for backward compatibility. dash/non-dash params can be mixed
                start_y  => 270/mm,
                -start_h => 250/mm,
                next_y   => 270/mm,
                next_h   => 250/mm,

                # some optional params for fancy results
                -padding              => 5/mm,
                #padding_left         => 10/mm,
                #padding_right         => 10/mm,
                #background_color_odd  => 'lightblue',
                #background_color_even => "#EEEEAA",     #cell background color for even rows
                header_props          => {
                        padding                   => 10/mm,
                        justify    => "left",
                        bg_color   => "#0078A6",
                        font       => $pdf->corefont( "Arial", -encoding => "utf8" ),
                        font_size  => 15,
                        font_color => "white",
                        repeat     => 1
                },
                column_props => [
                        {
                                max_w      => 100/mm,
                                justify    => "left",
                                font       => $pdf->corefont( "Arial", -encoding => "latin1" ),
                                font_size  => 12,
                                font_color => 'black',
                                #background_color => '#8CA6C5',
                        },                                 #no properties for the first column
                        {
                                max_w      => 80/mm,
                                justify    => "left",
                                font       => $pdf->corefont( "Arial", -encoding => "latin1" ),
                                font_size  => 12,
                                font_color => 'black',
                                #background_color => '#8CA6C5',
                        },
                ],

                #       cell_props => [
                #               [ #This is the first(header) row of the table and here wins %header_props
                #                       {
                #                               background_color => '#000000',
                #                               font_color       => 'blue',
                #                       },
                #
                #                       # etc.
                #               ],
                #               [    #Row 2
                #                       {    #Row 2 cell 1
                #                               background_color => '#000000',
                #                               font_color       => 'white',
                #                       },
                #                       {    #Row 2 cell 2
                #                               background_color => '#AAAA00',
                #                               font_color       => 'red',
                #                       },
                ##                      {    #Row 2 cell 3
                ##                              background_color => '#FFFFFF',
                ##                              font_color       => 'green',
                ##                      },
                #
                #                       # etc.
                #               ],
                #               [        #Row 3
                #                       {    #Row 3 cell 1
                #                               background_color => '#AAAAAA',
                #                               font_color       => 'blue',
                #                       },
                #
                #                       # etc.
                #               ],
                #
                #               # etc.
                #       ],
                # End of cell properties define.

        );

        # 6. build the user action details table:
        #append another talbe after:
        # use new page, will automatically append the page to the end of current pdf
        # if use page($pagenumber), it will insert after $pagenumber of the pdf:
        my $newpage = $pdf->page();
        $pdftable->table(
                # required params
                $pdf,
                $newpage,
                \@action_data,
                #$blue_box->rect( 10 / mm, 250 / mm, 190 / mm, 36 / mm );
                # Geometry of the document
                x  => 10/mm,
                -w => 190/mm, # dashed params supported for backward compatibility. dash/non-dash params can be mixed
                start_y  => 270/mm,
                -start_h => 250/mm,
                next_y   => 270/mm,
                next_h   => 250/mm,

                # some optional params for fancy results
                -padding              => 5/mm,
                #padding_left         => 10/mm,
                #padding_right         => 10/mm,
                #background_color_odd  => 'lightblue',
                #background_color_even => "#EEEEAA",     #cell background color for even rows
                header_props          => {
                        padding                   => 10/mm,
                        bg_color   => "#0078A6",
                        font       => $pdf->corefont( "Helvetica", -encoding => "utf8" ),
                        font_size  => 15,
                        font_color => "white",
                        repeat     => 1
                },
                column_props => [
                        {
                                max_w      => 80/mm,
                                justify    => "left",
                                font       => $pdf->corefont( "Arial", -encoding => "latin1" ),
                                font_size  => 12,
                                font_color => 'black',
                                #background_color => '#8CA6C5',
                        },                                 #no properties for the first column
                        {
                                max_w      => 80/mm,
                                justify    => "left",
                                font       => $pdf->corefont( "Arial", -encoding => "latin1" ),
                                font_size  => 12,
                                font_color => 'black',
                                #background_color => '#8CA6C5',
                        },
                        {
                                max_w      => 20/mm,
                                justify    => "left",
                                font       => $pdf->corefont( "Arial", -encoding => "latin1" ),
                                font_size  => 12,
                                font_color => 'black',
                                #background_color => '#8CA6C5',
                        },
                ],

                #       cell_props => [
                #               [ #This is the first(header) row of the table and here wins %header_props
                #                       {
                #                               background_color => '#000000',
                #                               font_color       => 'blue',
                #                       },
                #
                #                       # etc.
                #               ],
                #               [    #Row 2
                #                       {    #Row 2 cell 1
                #                               background_color => '#000000',
                #                               font_color       => 'white',
                #                       },
                #                       {    #Row 2 cell 2
                #                               background_color => '#AAAA00',
                #                               font_color       => 'red',
                #                       },
                ##                      {    #Row 2 cell 3
                ##                              background_color => '#FFFFFF',
                ##                              font_color       => 'green',
                ##                      },
                #
                #                       # etc.
                #               ],
                #               [        #Row 3
                #                       {    #Row 3 cell 1
                #                               background_color => '#AAAAAA',
                #                               font_color       => 'blue',
                #                       },
                #
                #                       # etc.
                #               ],
                #
                #               # etc.
                #       ],
                # End of cell properties define.

        );

        #$pdf->save;
        $pdf ->saveas($pdfname);
        $pdf->end();

} #End of generatePDFReport function.
