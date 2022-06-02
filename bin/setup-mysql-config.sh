#!/bin/bash

# Loads the database config into mysql config files. Only needed for Linux, as Windows
# will load with command line/env vars
cd $(dirname "$0")
DBUSER=$(grep DB_USER ../wp-config.php | awk -F "'" '{print $4}')
DBHOST=$(grep DB_HOST ../wp-config.php | awk -F "'" '{print $4}')
DBPASS=$(grep DB_PASSWORD ../wp-config.php | awk -F "'" '{print $4}')
if [[ -x "$(command -v mysql_config_editor)" ]]; then
    if [[ "$(mysql_config_editor print | grep client)" ]]; then
        echo mysql_config_editor is already configured, print is:
        echo --------------------------
        mysql_config_editor print
        echo --------------------------
        echo if you need you can add the following settings
        echo mysql_config_editor set --login-path=client --host=$DBHOST --user=$DBUSER --password
        echo Enter the password $DBPASS when prompted
    else
        echo Enter the password $DBPASS when prompted
        mysql_config_editor set --login-path=client --host=$DBHOST --user=$DBUSER --password
    fi
else
    # no mysql_config_editor, so store in ~/.my.cnf if it doesn't exist
    STR="[client]
user = $DBUSER
password = $DBPASS
host = $DBHOST"
   if [[ -f ~/.my.cnf ]]; then
        echo mysql config file \~/.my.cnf already exists
        echo If needed you can manually enter the following values:
        echo --------------------------
        echo "$STR"
        echo --------------------------
        echo Current \~/.my.cnf is
        echo --------------------------
        cat ~/.my.cnf
    else
        touch ~/.my.cnf
        chmod 0600 ~/.my.cnf
        echo "$STR" > ~/.my.cnf
    fi
fi