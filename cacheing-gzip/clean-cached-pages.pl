#!/usr/bin/perl -w

# Cleanup cached pages
#
# For some PHP pages we cache the output files as normal html and html.gz
# files (or .ics and .ics.gz files for calendars).
# These files are then served using mod_rewrite, so once generated the PHP
# script is never called again until the cached file is deleted.
#
# This script runs as a git post-update hook to check if those cached files
# are out of date compared to the source php and xml files, and need to be
# removed so that they will be re-generated when next accessed.

# NOTE: originally had /php/cache.inc as a dependancy of all cached files,
# but it's probably never going to effect the cached files, so if it does
# then those files will have to be deleted manually

use strict;
use constant {
	DEBUG => 0, # set to display debugging info
	DELETE_FILES => 1, # set to 0 to test without deleting files
};

# Read dir from input
my $root = shift @ARGV or die "Usage: $0 input-dir\n";
die "Invalid path specified.\n" if !-d $root;

print "Cleaning cached files - root directory $root\n";
my $checked = 0;
my $removed = 0;
my $removedGz = 0;

# preload modification times of files which are needed more than once
my $fixturesIncMtime = getMtime('php/fixtures.inc');
my $fixturesAllMtime = getMtime('fixtures-all.xml');
my $teamArrayMtime = getMtime('team-array.inc');

# fixtures
# - dependancies fixtures.inc, team-array.inc, fixtures-all.xml,
# 		fixtures.php, fixture-date-array.inc, club-array.inc
my $fixturesDependanciesMtime = latestMtime($root,
		maxMtime($fixturesIncMtime,$teamArrayMtime,$fixturesAllMtime),
		,'fixtures.php','fixture-date-array.inc','club-array.inc');
cleanDir($root,'/.cache','\.html$',$fixturesDependanciesMtime);
# at the moment don't need to be specific as only fixtures pages cached
#cleanDir($root,'/.cache','fixtures_.*.html$',$fixturesDependanciesMtime);

# results
# dependancies are calculated in resultsMtime(), and for each year they are
#   fixtures.inc, results.php, tables-$year.inc, fixtures-$year.inc, fixtures-$year.xml

# hash to store mtimes for results dependancies by year
my %resultsDependanciesMtimes = ();
# used in resultsMtime()
my $resultsBaseDependanciesMtime = latestMtime($root.'/history/league',
	$fixturesIncMtime,'results.php');
cleanDir($root.'/history/league','/.cache','^results-\d\d\d\d.*\.html$',0,\&resultsMtime);

# calendars
# - dependancies fixtures-calendar.php, team-array.inc, fixtures-all.xml,
#     fixtures2ics.xsl, UUID.inc
my $icsDependanciesMtime = latestMtime($root,
		maxMtime($teamArrayMtime,$fixturesAllMtime),
		'fixtures-calendar.php','php/fixtures2ics.xsl','php/UUID.inc');
cleanDir($root,'','^fixtures_.*\.ics$',$icsDependanciesMtime);

print "Completed - checked $checked files, removed $removed files and $removedGz .gz files\n";
exit 0;

sub cleanDir {
	# clean a directory of cached files, and their .gz compressed versions
	# $dir = base directory to clean - full path
	# $cachePath relative path to cache file, including leading slash
	# $regex regex to match file names to check
	# $dependanciesMtime max modification time of any dependancies
	# $mtimeFunc if $dependanciesMtime is not specified then this function is
	#  called to find the modification time of the file's dependancies
	my ($dir,$cachePath,$regex,$dependanciesMtime,$mtimeFunc) = @_;
	
	my $cacheDir = $dir . $cachePath;
	return if !-d $cacheDir; # check directory exists
	
	print "cleaning cache dir $cacheDir regex $regex\n";
	opendir(DIR,"$cacheDir") || die "opendir $cacheDir failed";
	my @list = grep(/$regex/,readdir(DIR));
	closedir(DIR);

    foreach my $item (@list) {
    	$checked++;
        my $file = $cacheDir.'/'.$item;
        print "checking $item\n" if DEBUG;
	    my $filemtime = (stat($file))[9];
		if ($dependanciesMtime) {
			next if $filemtime > $dependanciesMtime; 
		} else {
		    next if $filemtime > $mtimeFunc->($dir,$item);
		}
		print "--- removing $item\n" if DEBUG;
		unlink $file if DELETE_FILES;
		$removed++;
		$file .= '.gz';
		if (-e $file) {
			print "--- removing $item.gz\n" if DEBUG;
			unlink $file if DELETE_FILES;
			$removedGz++;
		}
		
    }	
}
sub getMtime{
	# get the modification time of file, die if not found
	# Note: filename is relative to root
	my ($file) = @_;
	return (stat($root.'/'.$file))[9] || die "Cannot find file $root$file\n";
}
sub latestMtime {
	# return latest modification date for a list of files relative to a directory
	# first param is directory
	# second param is the max modification time of any dependancies which have
	#   been preloaded - e.g. mod time of fixtures.inc is loaded once. Callers with
	#	more than once dependancy should call calling maxMtime(a,b,c) and pass
	#   that result
	# remaining params are additional file names relative to the directory to check
	# this function dies if any of the remaining files cannot be found
	my $dir = shift() . '/';
	
	my $mtime = shift();
	foreach (@_) { 
	    my $mtime2 = (stat($dir . $_))[9] || die "Cannot find file $dir$_\n";
	    $mtime = $mtime2 if $mtime2 > $mtime;
	} 	
	return $mtime;
}
# maximum mtime from a list of mtimes
sub maxMtime {
	my $mtime = shift();
	foreach (@_) { 
	    $mtime = $_ if $_ > $mtime;
	} 	
	return $mtime;
}
# maximum modification time for dependancies of a results file
# NOTE: results have a minimal dependay on fixtures.inc
sub resultsMtime {
	my ($dir,$item) = @_;

	my ($year) = ($item =~ /results-(\d\d\d\d)/);
	if (!$year) {
		print "no year in file $item!!\n";
		return $resultsBaseDependanciesMtime;
	}
	if (exists($resultsDependanciesMtimes{$year})) {
		return $resultsDependanciesMtimes{$year};
	}
 	my $mtime = $resultsDependanciesMtimes{$year} 
			= latestMtime($dir,$resultsBaseDependanciesMtime,"tables-$year.inc"
					,"fixtures-$year.inc","fixtures-$year.xml");
	return $mtime;
}
