#!/usr/bin/perl -w

# Creates a compressed version of all appropriate files which can be served instead,
#   e.g. x.html is gzipped to x.html.gz
# Also remove any .gz files which do not have the corresponding file, so if files
#   are deleted then their .gz file is removed too

# NOTE: this file also checks cached html and ics files. They should have
# been gzipped as part of the cacheing process, but no harm in double checking. 

# Argument is the server_root directory (i.e. "~" on the 1and1 server, or "htdocs")

# The regex of file suffixes to process is set in $suffixesRegex
# Directories to ignore (relative to the root, no leading slash) are in @ignoreDirs 
# All directories beginning with a dot except .cache are also ignored, so .git etc.
# Minimum size to compress is also set in $compressMinFileSize
# These are hardcoded as we want to store this information in the repository 

# Note: to delete all .gz files use:
#	find . -type f -name '*.gz' -delete

use strict;
use feature "state";
use File::Find;
###################################
use constant {
	DEBUG => 0
};

# Read dir from input
my $dir = shift @ARGV or die "Usage: $0 input-dir\n";
if (!-d $dir) {
    print "Invalid path specified.\n";
    exit 1;
}

# minimum size of file to compress, in bytes
my $compressMinFileSize = 250;
# suffixes to process
# NB: if these are changed make sure to update the .htaccess files
# for FilesMatch for "Header append Vary: Accept-Encoding" etc.
my $suffixesRegex = qr/\.(css|csv|doc|gpx|html|ico|ics|js|svg|xls)$/;

# directories to ignore
my @ignoreDirs = qw( backup \/logs \/rest bin semla\/core 
					template-parts wp-admin wp-includes );
my $ignoreDirsStr = join "|",@ignoreDirs;
my $ignoreDirsRegex = qr/$ignoreDirsStr/;

# files to ignore - matches any part of filename
# \.cache\/fixtures_\d\d\d\d was for default fixtures page in old version - but WordPress version
# does not create that file

# my $ignoreFiles = qw ();
# my $ignoreFilesStr = join "|",@ignoreFiles;
# my $ignoreFilesRegex = qr/$ignoreFilesStr/;
my $ignoreFilesStr = '';
my $ignoreFilesRegex = undef;

print "processing suffixes $suffixesRegex\n";
print "ignoring directories $ignoreDirsStr\n";
print "ignoring files $ignoreFilesStr\n";

my $gzipped = 0;
my $alreadyZipped = 0;
my $gzDeleted = 0;
my $tempDeleted = 0;
my $tooSmall = 0;
my $gzLargerThanSource = 0;
my $ignoredDirs = 0;
my $ignoredFiles = 0;

find(\&gzCompress, $dir);

print "gzipped $gzipped files, $alreadyZipped files already gzipped\n";
print "$gzDeleted .gz files deleted , $tempDeleted temp files deleted, $tooSmall files too small to zip\n";
print "ignored $ignoredDirs dirs and $ignoredFiles files\n";
print "$gzLargerThanSource files not zipped because gz larger than source!\n" if $gzLargerThanSource; 
exit 0;

sub gzCompress {
	if (-d) {
		print "checking dir " . $File::Find::name ."\n" if DEBUG;
		# NOTE: /^\..+/ excludes all dot directorys (except .cache previously allowed)
		# but we don't want to exclude '.' as that would prune the current dir
		if ((/^\..+/ && !/\.cache/) || $File::Find::name =~ /$ignoreDirsRegex/) {
			$ignoredDirs++;
			print "skipping dir $File::Find::name\n" if DEBUG;
			$File::Find::prune = 1;
		}
		return;
	}
	if ($ignoreFilesRegex && $File::Find::name =~ /$ignoreFilesRegex/) {
		$ignoredFiles++;
		print "skipping file $File::Find::name\n" if DEBUG;
		return;
	}
	my $file = $_;
	if ($file =~ /\.gz$/) {
		(my $base = $file) =~ s/\.gz$//;
		if (!-e $base) {
			print "removing $File::Find::name\n";
			unlink $file;
			$gzDeleted++;
		}
	} elsif ($file =~ /\.gztmp$/) {
		print "tidying up temp file!! $File::Find::name\n";
		unlink $file;
		$tempDeleted++;
	} elsif ($file =~ /$suffixesRegex/) {
		my $fileSize = -s $file;
		my $fileSizePretty = formatSize($fileSize);
		my $gzFile = $file . '.gz';
		if ($fileSize < $compressMinFileSize) {
			$tooSmall++;
			# if file is too small to compress then make sure there isn't a .gz version
			if (-e $gzFile) {
				print "removing $File::Find::dir/$gzFile: base $fileSizePretty\n";
				unlink $gzFile;
				$gzDeleted++;
			}
			return;
		}
		if ((!-e $gzFile) || ( (stat($gzFile))[9] < (stat($file))[9] ) ) {
			# write to a temp file first, so we don't serve a half written file
			my $gzFileTmp = $file . '.gztmp';
			`gzip -c "$file" > "$gzFileTmp"`;
			rename $gzFileTmp,$gzFile;
			$gzipped++;
			my $gzSize = -s $gzFile;
			my $gzSizePretty = formatSize($gzSize);
			my $saved = $fileSize - $gzSize;
			if ($saved < 0) {
				my $savedPretty = formatSize(-1 * $saved);
				print "!!!!!!!! $File::Find::name: $fileSizePretty -> $gzSizePretty (-$savedPretty)\n";
				$gzLargerThanSource++;
				return;
			}
			my $savedPretty = formatSize($saved);
			print "gzip $File::Find::name: $fileSizePretty -> $gzSizePretty ("
			. (int($gzSize/$fileSize*10000) / 100) . "%) -$savedPretty\n";
		} else {
			$alreadyZipped++;
			print "already gzipped $File::Find::name\n" if DEBUG;
		}
	}
#	print $File::Find::dir . ", " . $_ . ", " . $File::Find::name . " " ."\n";
	return;
}
sub formatSize {
    my $size = shift;
    my $exp = 0;
    state $units = [qw(B KB MB GB TB PB)];
    for (@$units) {
        last if $size < 1024;
        $size /= 1024;
        $exp++;
    }
    return wantarray ? ($size, $units->[$exp]) : sprintf("%.2f%s", $size, $units->[$exp]);
}
