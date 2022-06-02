#!/usr/local/bin/perl

# Export db, and search/replace URLs. Will respect PHP serialised objects

use strict;
use warnings;
use File::Basename;

# If running under Local shell then my.cnf is set up, and you can set all to '' except DBNAME
my $DBHOST = ''; # default ::1
my $DBPORT = ''; # default 10004
my $DBUSER = 'root';
my $DBPASS = 'root';
my $DBNAME = 'local';

if ($#ARGV > 2) {
    print "Usage: export.pl [URL-from URL-to]";
    exit 1;
}

my $from = shift @ARGV || 'dev.southlacrosse';
my $to = shift @ARGV || 'wordpress.southlacrosse';
my $diff = length($to) - length($from);
die 'Use a batch/shell script as URL lengths are the same' if $diff == 0;

my $command = "mysqldump";
$command .= " -u $DBUSER" if $DBUSER;
$command .= " -h $DBHOST" if $DBHOST;
$command .= " -p$DBPASS" if $DBPASS;
$command .= " -P $DBPORT" if $DBPORT;
$command .= " --no-tablespaces --skip-comments $DBNAME wp_commentmeta wp_comments wp_links wp_options wp_postmeta wp_posts wp_term_relationships wp_term_taxonomy wp_termmeta wp_terms wp_usermeta wp_users";

open(my $in_fh, '-|', $command)
    or die "Couldn't open input: $!";
open(my $out_fh, '|-', 'gzip > ' . dirname(__FILE__) . "/db-wp.sql.gz")
    or die "Couldn't open output: $!";

my $regex = qr/$from/;
# regex to change PHP serialized objects, as for strings they has s:length:"string"
my $serializeRegex = qr/s:(\d+):\\"([^"]*)$from/;

while (my $line = <$in_fh>) {
    $line =~ s/$serializeRegex/sprintf('s:%d:\\"%s%s',$1+$diff,$2,$to)/eg;
    $line =~ s/$regex/$to/g;
    print $out_fh $line;
}
close($in_fh);
close($out_fh);
exit(0);
