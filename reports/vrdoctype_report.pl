#!/usr/bin/perl

# 2007/11/15  Robin CJ
# Script to generate report data of totals of each document type loaded for
# a given organisation and date
# 2011/08/19  Robin CJ
# Modified to output repository info for each doc type when printing doc types.
# 2013/11/27  Robin CJ
# Modified so that doc count totals only include deletes for jobs loaded and deleted in the same period.

use strict;
use Getopt::Long;
use Data::Dumper;

# PostGres Database Interface not installed on this host
#use DBI;
#use DBD::Pg;

##########
# Variables - to be parameterised
##########
my $org;

my $dbhost = "vretrieve-app-database";
my $db     = "vretrieve";
my $dbuser;
my $dbpass;

my ( $usage, $nousage );

my $startdate;

# Default end date is the first of the current month
my $month   = ( localtime( time() ) )[4] + 1;
my $year    = ( localtime( time() ) )[5] + 1900;
my $enddate = "$year-$month-01";

my $category = "doctype";    # do doctype counts by default

my ( @names, @ids, $listreps, $listorgs, $listtypes, $totals, $showbytes,
	$listbatches );

##########################
# Predeclare Subroutines
##########################
sub pgpass;
sub dosql;
sub usage_exists;
sub getDocTypes;
sub getDocTypesById;
sub getDocTypesByName;
sub getDocTypePeriodTotals;
sub getOrgs;
sub getOrgFullnames;
sub printOrgs;
sub processAllOrgs;

##########################
# Get CL parameters
##########################
GetOptions(
	"name=s"           => \@names,
	"id=s"             => \@ids,
	"usage"            => \$usage,          # set to force use of usage table
	"nousage"          => \$nousage,        # set to prevent use of usage table
	"startdate=s"      => \$startdate,
	"enddate=s"        => \$enddate,
	"org=s"            => \$org,
	"listorgs"         => \$listorgs,       # List organisations and exit
	"listreps"         => \$listreps,       # List repositories and exit
	"listrepositories" => \$listreps,       # List repositories and exit
	"types"            => \$listtypes,      # List doc types and exit
	"listtypes"        => \$listtypes,      # List doc types and exit
	"listbatches"      => \$listbatches,    # List batch names for period
	"batches"          => \$listbatches,    # List batch names for period
	"totals"           =>
	  \$totals,  # Display total num docs to enddate as well as total for period
	"bytes" => \$showbytes
	,    # Display bytes for period, and total if -totals option also used
	"host=s"     => \$dbhost,
	"db=s"       => \$db,
	"login=s"    => \$dbuser,
	"user=s"     => \$dbuser,
	"password=s" => \$dbpass,
	"category=s" => \$category
);

##########################
# MAIN - Process CL parameters
##########################

# Error if dbuser or dbpassword not supplied
#if ( ! $dbuser || ! $dbpass ){
# Error if dbuser or dbpassword not supplied
if ( !$dbuser ) {
	print
"ERROR: You must specify a database user using the \"-user <dbuser>\" and optional \"-password <password>\" parameters\n";
	exit 1;
}

# Determine whether the usage table exists or not
# if it has not already been forced at the command line
if ( !$usage && !$nousage ) {
	$usage = "exists" if ( usage_exists() );
}

# Display a list of org info if -listorgs option is used
if ($listorgs) {
	printOrgs;
	exit;
}

# Error if an org is not provided
if ( !$org ) {
	print
"ERROR: You must specify an  organisation using the \"-org <ORG>\" parameter.\n";
	exit 1;
}

# if a doctype id or name list is not provided then do all by default:
if ( $category !~ /^rep/i && !@ids && !@names ) {
	my %doctypes;
	getDocTypesById( $org, \%doctypes );
	@ids = keys(%doctypes);
}

# if a rep id or name list is not provided then do all by default:
if ( $category =~ /^rep/i && !@ids && !@names ) {
	my %reps = getRepsById($org);
	@ids = keys(%reps);
}

# If listtypes option is set then just list document types and exit
if ($listtypes) {
	printDocTypes($org);
	exit;
}

# If listrepositories option is set then just list repository names and exit
if ($listreps) {
	printRepositories($org);
	exit;
}

# If listbatches option is set then just list batch info and exit
if ($listbatches) {
	printBatchNames( $org, $startdate, $enddate );
	exit;
}

# If Doctype names are given then convert them to IDs and add them to @ids
if ( $category !~ /^rep/i && @names ) {
	my %doctypes;
	getDocTypesById( $org, \%doctypes );
	foreach my $dtname (@names) {
		foreach my $dtid ( keys(%doctypes) ) {
			if ( $doctypes{$dtid}{dtname} eq $dtname ) {
				push @ids, $dtid;
			}
		}
	}
}

# If Doctype ids are given then list document type totals for them
if ( $category !~ /^rep/i && @ids ) {
	printDocTypeTotalsById( $org, @ids );
}

# If Repository names are given then convert them to IDs and add them to @ids
if ( $category =~ /^rep/i && @names ) {
	my %reps = getRepsByName($org);
	foreach (@names) { push @ids, $reps{$_} }
}

# If Repository ids are given then list document totals for them
if ( $category =~ /^rep/i && @ids ) {
	printRepTotalsById( $org, @ids );
}

##########################
# Subroutines
##########################

sub printOrgs {

	# Print a list of organisations: id|name|name_abbr
	my @output = getOrgs;

	foreach (@output) {

# Split output by pipe into id and doc type, removing any leading or trailing whitespace
		if (/^\s*(\d+)\s*\|\s*(\S+.*?\S+)\s*\|\s*(\S+.*?\S+)\s*$/) {
			print "$1|$2|$3\n";
		}
	}

}

sub printBatchNames {

	# Print a list of batches for organisation &/or date range
	my ( $org, $start, $end ) = @_;
	foreach ( getBatchNames( $org, $start, $end ) ) {

		# Split output by pipe, removing any leading or trailing whitespace
		if (/^\s*(\w+)\s*\|\s*(\S+.*?\S+)\s*\|\s*(\S+.*?\S+)\s*$/) {
			print "$1|$2|$3\n";
		}
	}

}

sub printDocTypes {
	my ($org) = @_;
	my %doctypes;
	getDocTypesById( $org, \%doctypes );

	foreach ( keys(%doctypes) ) {

		#print " id: ".$doctypes{$_}." name: $_ \n";
		#print $doctypes{$_}."|$_\n";
		print join(
			"|",
			(
				$_,                 $doctypes{$_}{dtname},
				$doctypes{$_}{rid}, $doctypes{$_}{rname}
			)
		  )
		  . "\n";
	}

}

sub printRepositories {
	my ($org) = @_;
	my %reps = getRepsByName($org);

	foreach ( keys(%reps) ) {

		#print " id: ".$reps{$_}." name: $_ \n";
		print $reps{$_} . "|$_\n";
	}

}

sub printDocTypeTotalsById {
	my ( $org, @ids ) = @_;
	my %doctotals;
	my %docperiodtotals;

	my @orgnames = getOrgFullnames;

	if ($totals) {

		# Set period start date to the beginning of time
		my $startdate = "1900-01-01";
		%doctotals = getDocTypePeriodTotals( $org, $startdate, @ids );
	}

	if ($startdate) {
		%docperiodtotals = getDocTypePeriodTotals( $org, $startdate, @ids );
	}

	# combine the org id lists in a uniqued list by holding them as hash keys
	my %orgidlist;
	foreach my $orgid ( keys(%doctotals), keys(%docperiodtotals) ) {
		$orgidlist{$orgid} = 1;
	}

	foreach my $orgid ( keys(%orgidlist) ) {
		my $org;

#my $org = $doctotals{$orgid}{orgname} ? $doctotals{$orgid}{orgname} : $docperiodtotals{$orgid}{orgname};
		if ( $doctotals{$orgid}{orgname} =~ /\w/ ) {
			$org = $doctotals{$orgid}{orgname};
		}
		else { $org = $docperiodtotals{$orgid}{orgname} }

		my %types;
		getDocTypesById( $org, \%types );
		foreach my $id ( keys(%types) ) {

			my $name        = $types{$id}{dtname};
			my $total       = $doctotals{$orgid}{dts}{$id}{count};
			my $totalbytes  = $doctotals{$orgid}{dts}{$id}{bytes};
			my $periodtotal = $docperiodtotals{$orgid}{dts}{$id}{count};
			my $periodbytes = $docperiodtotals{$orgid}{dts}{$id}{bytes};
			my $orgfullname = $orgnames[$orgid];

			print
"$orgid|$org|$id|$name|$periodtotal|$total|$periodbytes|$totalbytes|$orgfullname\n";

		}
	}

}

sub getDocTypesById {

# returns a list of document type ids and type names for a given org indexed by type id
	my ( $org, $hashref ) = @_;

	#my @output = getDocTypes($org);
	my @output = getDocTypes( $org, $hashref );
	return $hashref;

	my %doctypes;
	foreach (@output) {

# Split output by pipe into id and doc type, removing any leading or trailing whitespace
# Put the results into a hash, indexed by the type id
		if (/^\s*(\d+)\s*\|\s*(\S+.*?\S+)\s*$/) {
			$doctypes{$1} = $2;
		}
	}

	return %doctypes;

}

sub getDocTypesByName {

# Do not use.  Some orgs have multiple doc types with the same name, in different repositories
# returns a list of document type ids and type names for a given org indexed by type name
	my ($org) = @_;
	my @output = getDocTypes($org);
	my %doctypes;
	foreach (@output) {

# Split output by pipe into id and doc type, removing any leading or trailing whitespace
# Put the results into a hash, indexed by the type name
		if (/^\s*(\d+)\s*\|\s*(\S+.*?\S+)\s*$/) {
			$doctypes{$2} = $1;
		}
	}

	return %doctypes;

}

sub getDocTypes {

# returns sql output of a list of document type ids and type names for a given org
	my ( $org, $hashref ) = @_;

	my $where;
	$where = "where name_abbreviation = '$org'" if ( $org !~ /^all$/i );

	my $sql = "
  	set enable_seqscan=off;
	select dt.id, document_type_name, r.id, repository_name  from document_type dt
	join document_store ds on dt.document_store_id  = ds.id
	join repository r on ds.repository_id = r.id
	join content_organisation co on r.content_organisation_id = co.id
	$where
  ";

	foreach my $line ( dosql($sql) ) {
		chomp $line;
		my ( $dtid, $dtname, $rid, $rname ) = split( '\|', $line );
		$hashref->{$dtid}{dtname} = $dtname;
		$hashref->{$dtid}{rid}    = $rid;
		$hashref->{$dtid}{rname}  = $rname;
	}

	return $hashref;
}

sub getRepsById {

   # returns a list of Rep ids and type names for a given org indexed by type id
	my ($org) = @_;
	my @output = getReps($org);
	my %reps;
	foreach (@output) {

# Split output by pipe into id and rep name, removing any leading or trailing whitespace
# Put the results into a hash, indexed by the type name
		if (/^\s*(\d+)\s*\|\s*(\S+.*?\S+)\s*$/) {
			$reps{$1} = $2;
		}
	}

	return %reps;

}

sub getRepsByName {

	# returns a list of repository ids and names for a given org indexed by name
	my ($org) = @_;
	my @output = getReps($org);
	my %reps;
	foreach (@output) {

# Split output by pipe into id and doc type, removing any leading or trailing whitespace
# Put the results into a hash, indexed by the type name
		if (/^\s*(\d+)\s*\|\s*(\S+.*?\S+)\s*$/) {
			$reps{$2} = $1;
		}
	}

	return %reps;
}

sub getReps {

	# returns sql output of a list of repository ids and names for a given org
	my ($org) = @_;
	my $where;
	$where = "where name_abbreviation = '$org'" if ( $org !~ /^all$/i );

	my $sql = "
  	set enable_seqscan=off;
	select r.id, repository_name  from repository r
	join content_organisation co on r.content_organisation_id = co.id
	$where
  ";

	my @output = dosql($sql);

	return @output;
}

sub getDocTypeIdsFromRepIds {
	my (@repids) = @_;
	my $repids = join( ",", @repids );

	my $sql = "
	select document_type.id 
	from document_type
	join document_store ds  on document_type.document_store_id = ds.id
  	join repository r       on ds.repository_id = r.id
	where r.id in ( $repids )
  ";

	my @output = dosql($sql);

	return @output;
}

sub printRepTotalsById {
	my ( $org, @ids ) = @_;
	my %doctotals;
	my %docperiodtotals;

	# Convert repository id list to doctype id list
	if ( $#ids >= 0 ) { @ids = getDocTypeIdsFromRepIds(@ids); }

	my @orgnames = getOrgFullnames;

	if ($totals) {

		# Set period start date to the beginning of time
		my $startdate = "1900-01-01";
		%doctotals = getRepPeriodTotals( $org, $startdate, @ids );
	}

	if ($startdate) {
		%docperiodtotals = getRepPeriodTotals( $org, $startdate, @ids );
	}

	# combine the org id lists in a uniqued list by holding them as hash keys
	my %orgidlist;
	foreach my $orgid ( keys(%doctotals), keys(%docperiodtotals) ) {
		$orgidlist{$orgid} = 1;
	}

	foreach my $orgid ( keys(%orgidlist) ) {
		my $org;

#my $org = $doctotals{$orgid}{orgname} ? $doctotals{$orgid}{orgname} : $docperiodtotals{$orgid}{orgname};
		if ( $doctotals{$orgid}{orgname} =~ /\w/ ) {
			$org = $doctotals{$orgid}{orgname};
		}
		else { $org = $docperiodtotals{$orgid}{orgname} }

		$org =
		    $doctotals{$orgid}{orgname}
		  ? $doctotals{$orgid}{orgname}
		  : $docperiodtotals{$orgid}{orgname};

		my %reps = getRepsById($org);
		foreach my $rid ( keys(%reps) ) {

			my $repname     = $reps{$rid};
			my $total       = $doctotals{$orgid}{reps}{$rid}{count};
			my $totalbytes  = $doctotals{$orgid}{reps}{$rid}{bytes};
			my $periodtotal = $docperiodtotals{$orgid}{reps}{$rid}{count};
			my $periodbytes = $docperiodtotals{$orgid}{reps}{$rid}{bytes};
			my $orgfullname = $orgnames[$orgid];

			print
"$orgid|$org|$rid|$repname|$periodtotal|$total|$periodbytes|$totalbytes|$orgfullname\n";

		}
	}

}

sub getBatchNames {

	# returns a list of batch load_job_names for a given org and date range
	my ( $org, $start, $end ) = @_;

	my $wheresql = " 1=1 ";

	my $loaddate_colname = "document.document_load_date";
	$loaddate_colname = "usage.end_date" if ($usage);

	my $orgsql = " 1=1 ";
	$orgsql = "co.name_abbreviation = '$org'" if ($org);
	my $startsql = " 1=1 ";
	$startsql = "$loaddate_colname > '$start'" if ($start);
	my $endsql = " 1=1 ";
	$endsql = "$loaddate_colname < '$end'" if ($end);

	$wheresql = "$orgsql AND $startsql AND $endsql AND "
	  if ( $org || $start || $end );

	# SQL to use if there is no usage table set up (default).
	my $sql = "
  	select name_abbreviation, document_load_date, load_job_reference 
	from document
	join document_type dt 	on document.document_type_id = dt.id
	join document_store ds 	on dt.document_store_id = ds.id
	join repository r 	on ds.repository_id = r.id
	join content_organisation co on r.content_organisation_id = co.id
        WHERE
	$wheresql
	group by name_abbreviation, document_load_date, load_job_reference;
  ";

	# SQL to use if the usage is table set up.
	$sql = "
     SELECT name_abbreviation, end_date, reference
     FROM usage
     LEFT OUTER JOIN content_organisation co ON co.id = content_organisation_id 
     WHERE 
     delta = true AND
     $wheresql
     
  " if ($usage);

	my @output = dosql($sql);

	return @output;
}

sub getOrgs {

	# returns a list of organisation id, name and abbreviation
	my $sql =
"SELECT id, name, name_abbreviation FROM content_organisation ORDER BY name";

	my @output = dosql($sql);
	return @output;
}

sub getOrgFullnames {

	# returns an array of org fullnames with id as index
	my @orgout = getOrgs;
	my @orgarray;

	foreach my $line (@orgout) {
		my ( $id, $name, $abbr ) = split( /\|/, $line );
		$orgarray[$id] = $name;
	}

	return @orgarray;
}

sub getDocTypePeriodTotals {

# Outputs a the total number of documents loaded in a given period for each doc type for a given org
	my ( $org, $startdate, @ids ) = @_;
	my $idlist = join( ",", @ids );

	# Remove any trailing commas and spaces
	$idlist =~ s/^(.*),\s*$/$1/;

	# If $showbytes is set then we also want total bytes so modify the sql
	my $bytesum1 = "";
	my $bytesum2 = "";
	my $bytesum3 = "";

	if ($showbytes) {
		# The chunks of sql that need to be inserted into the query to obtain file sizes
		# $bytesum = ", sum(size) from document  join document_file df   on document.id = df.document_id";
		$bytesum1 = ", sum(size)";
		$bytesum2 = ", sum(file_size) as size";
		$bytesum3 = "join document_file df on document.id = df.document_id";
	}

	my $sql;
	my $where;
	if ( $org !~ /all/i ) {
		$where = "document.document_type_id IN ( $idlist ) AND ";
		$where = "usage.document_type_id IN ( $idlist ) AND " if ($usage);
	}

	$sql =
"select co.id, name_abbreviation, dt.id, document_type_name, sum(files) $bytesum1
        from (
                select document.document_type_id, count(*) as files $bytesum2
                from document
                $bytesum3

                where $where
                document_load_date <= '$enddate'
                and document_load_date >= '$startdate'

                group by document_type_id
        ) doc_type_totals

        join document_type dt   on doc_type_totals.document_type_id = dt.id
        join document_store ds  on dt.document_store_id = ds.id
        join repository r       on ds.repository_id = r.id
        join content_organisation co on r.content_organisation_id = co.id

        group by co.id, name_abbreviation, dt.id, document_type_name
    ";

	my $usageTypeSql = "u";
	if ( $startdate eq "1900-01-01" ) {

		# Include all deletes and expiries
		$usageTypeSql = "(
		SELECT * FROM u WHERE operation_type = 'LOAD_JOB'
		UNION
		SELECT * FROM u WHERE operation_type = 'DELETE_JOB'
		AND reference IN (SELECT reference FROM u WHERE operation_type = 'LOAD_JOB')
	) periodusage";
	}

	$sql = "
	-- DROP TABLE IF EXISTS u;
  	CREATE TEMP TABLE u AS
     SELECT  usage.content_organisation_id, name_abbreviation, document_type_id, document_type_name, documents, size, operation_type, reference
     FROM usage
     LEFT OUTER JOIN content_organisation co ON co.id = usage.content_organisation_id
     , document_type
     WHERE
     $where
     document_type.id = usage.document_type_id
     AND delta = true
     AND end_date BETWEEN '$startdate' AND '$enddate'
    ;
	SELECT content_organisation_id, name_abbreviation, document_type_id, document_type_name, sum(documents) $bytesum1
    FROM $usageTypeSql
	GROUP BY content_organisation_id, name_abbreviation, document_type_id, document_type_name    
     ; " if ($usage);

	my @output = dosql($sql);
	my %totals;

	foreach (@output) {

# Split output by pipe into id and doc type, removing any leading or trailing whitespace
# Put the results into a hash, indexed by the type name
		my $regex =
		  '^\s*(\d+)\s*\|\s*(\w+)\s*\|\s*(\d+)\s*\|\s*(\w.*)\s*\|\s*(\d+)\s*$';
		if ($showbytes) {
			$regex =
'^\s*(\d+)\s*\|\s*(\w+)\s*\|\s*(\d+)\s*\|\s*(\w.*)\s*\|\s*(\d+)\s*\|\s*(\d+)\s*$';
		}

		#print "DEBUG: checking line $_\n against $regex\n";
		next if ( !/$regex/ );

		#print "DEBUG: Matches\n";
		my ( $orgid, $org, $id, $name, $doccount, $bytes ) = /$regex/;

		$totals{$orgid}{orgname} = $org;
		$totals{$orgid}{dts}{$id}{name} = $name;
		$totals{$orgid}{dts}{$id}{count} = $doccount ? $doccount : 0;
		$totals{$orgid}{dts}{$id}{bytes} = $bytes    ? $bytes    : 0;
	}

	#print "DEBUG:". Dumper(%totals);

	return %totals;
}

sub getRepPeriodTotals {

# Outputs a the total number of documents loaded in a given period for each repository for a given org
	my ( $org, $startdate, @ids ) = @_;

	# we are now doing this using doc ids rather than repository ids
	my $idlist = join( ",", @ids );

	# Remove any trailing commas and spaces
	$idlist =~ s/^(.*),\s*$/$1/;

	# If $showbytes is set then we also want total bytes so modify the sql
	my $bytesum1 = "";
	my $bytesum2 = "";
	my $bytesum3 = "";

	if ($showbytes) {

# The chunks of sql that need to be inserted into the query to obtain file sizes
#$bytesum = ", sum(size) from document  join document_file df   on document.id = df.document_id";
		$bytesum1 = ", sum(size)";
		$bytesum2 = ", sum(file_size) as size";
		$bytesum3 = "join document_file df on document.id = df.document_id";
	}

	my $sql;
	my $where;
	if ( $org !~ /all/i ) {

		#$where = "ds.repository_id in ( $idlist ) and ";
		$where = "document.document_type_id in ( $idlist ) and ";
		$where = "usage.document_type_id in ( $idlist ) and " if ($usage);

	}

	$sql =
"select co.id, name_abbreviation, ds.repository_id, repository_name, sum(files) $bytesum1
	from (
		select document.document_type_id, count(*) as files $bytesum2
		from document
		$bytesum3

		where $where
		document_load_date <= '$enddate'
        	and document_load_date >= '$startdate'
		
		group by document_type_id
	) doc_type_totals

      	join document_type dt   on doc_type_totals.document_type_id = dt.id
       	join document_store ds  on dt.document_store_id = ds.id
       	join repository r       on ds.repository_id = r.id
        join content_organisation co on r.content_organisation_id = co.id

        group by co.id, name_abbreviation, repository_id, repository_name
    ";

	my $usageTypeSql = "u";
	if ( $startdate && $startdate ne "1900-01-01" ) {
		# Must be limited date range so exclude expiries and deletes that do not relate to this period
		$usageTypeSql = "(
		SELECT * FROM u WHERE operation_type = 'LOAD_JOB'
		UNION
		SELECT * FROM u WHERE operation_type = 'DELETE_JOB'
		AND reference IN (SELECT reference FROM u WHERE operation_type = 'LOAD_JOB')
	) periodusage";
	}

	$sql = "
	-- DROP TABLE IF EXISTS u;
  	CREATE TEMP TABLE u AS
     SELECT  usage.content_organisation_id, name_abbreviation, repository_id, r.repository_name, documents, size, operation_type, reference
     FROM usage
     LEFT OUTER JOIN content_organisation co ON co.id = usage.content_organisation_id
     , repository r
     WHERE
     $where
     r.id = usage.repository_id 
     AND end_date BETWEEN '$startdate' AND '$enddate'
     AND delta = true
    ;
	SELECT content_organisation_id, name_abbreviation, repository_id, repository_name, sum(documents) $bytesum1
    FROM $usageTypeSql
	GROUP BY content_organisation_id, name_abbreviation, repository_id, repository_name    
  ;" if ($usage);

	my @output = dosql($sql);
	my %totals;

	foreach (@output) {

# Split output by pipe into id and doc type, removing any leading or trailing whitespace
# Put the results into a hash, indexed by the type name
		my $regex =
		  '^\s*(\d+)\s*\|\s*(\w+)\s*\|\s*(\d+)\s*\|\s*(\w.*)\s*\|\s*(\d+)\s*$';
		if ($showbytes) {
			$regex =
'^\s*(\d+)\s*\|\s*(\w+)\s*\|\s*(\d+)\s*\|\s*(\w.*)\s*\|\s*(\d+)\s*\|\s*(\d+)\s*$';
		}

		#print "DEBUG: checking line $_\n against $regex\n";
		next if ( !/$regex/ );

		#print "DEBUG: Matches\n";
		my ( $orgid, $org, $rid, $rname, $doccount, $bytes ) = /$regex/;

		$totals{$orgid}{orgname} = $org;
		$totals{$orgid}{reps}{$rid}{rname} = $rname;
		$totals{$orgid}{reps}{$rid}{count} = $doccount ? $doccount : 0;
		$totals{$orgid}{reps}{$rid}{bytes} = $bytes    ? $bytes    : 0;
	}

	#print "DEBUG:". Dumper(%totals);

# Check whether a count row was actually returned for each id
#if ( $#ids >= 0 ){
#  foreach (@ids){
#    if ( ! $totals{$orgid}{reps}{$_}{count} ){ $totals{$orgid}{reps}{$_}{count} = 0 }
#    if ( ! $totals{$orgid}{reps}{$_}{bytes} ){ $totals{$orgid}{reps}{$_}{bytes} = 0 }
#  }
#}

	return %totals;
}

sub usage_exists {
	my $sql = "SELECT * FROM usage LIMIT 10;";

	# Run the sql but send error to /dev/null because we will always
	# get an error when the usage table does not exist.
	my $psql = "psql -tAq -h $dbhost -d $db -U $dbuser 2>/dev/null";
	pgpass;

	open( SQLOUT, "echo \"$sql\" | $psql |" );

	# Count the number of rows in the output.
	my $count;
	while (<SQLOUT>) {
		$count++;
	}

# There should be lots of rows in the usage table so if the sql
# we've limited the return to 10 rows but if it
# returns 5 or less rows then presumably the usage table does not exist or is empty
	return 1 if ( $count > 5 );
	return 0;
}

sub dosql {

	# Run SQL and return results in an array
	my ($sql) = @_;
	my @output;
	my $psql = "psql -tAq -h $dbhost -d $db -U $dbuser";
	pgpass;

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


