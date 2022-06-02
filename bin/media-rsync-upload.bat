@echo off

rem To run you will need to install rsync from Cygwin, and enter you user/host information
rem and make sure the local media dir is correct (this file is using c:\local\southlacrosse-repos\www\media)

echo This will copy the media folder www\media from your local machine
echo to the server,. Note that this will not delete any files on the server
echo which do not exist locally.
pause
rsync --dry-run -rtvizpP -e '/bin/ssh -p <port>' %1 --stats --exclude=".*" --chmod=Du=rwx,Do=rx,Dg=,Fu=rw,Fo=r,Fg= "/cygdrive/c/local/southlacrosse-repos/www/media/" user@host:~/media/
pause