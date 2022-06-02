#!/bin/sh -e
TMPFILE=`mktemp /tmp/SLC.XXXXXXXX` || exit 1
gzip -c "$1" > $TMPFILE
chmod o+r $TMPFILE
mv $TMPFILE "$1.gz"
