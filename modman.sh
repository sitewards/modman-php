#!/bin/bash

#
# If running under Cygwin, set the type of symlinks we want to use.
# For modman-php we want native NTFS symbolic links.  Windows shortcuts
# and Cygwin default symbolic links are not seen by Git or PhpStorm
# and therefore renders modman-php useless in these cases.
#
# Using winsymlinks:native works for me, but according to this thread:
#  http:#stackoverflow.com/questions/19780951/cygwin-winsymlinksnative-doesnt-work
# some people may experience problems. Perhaps there are version issues.
# To cover all cases more investigation and additional logic may be needed.
# RJR 9-Apr-15
#
function setupEnv() {
    shopt -s nocasematch
    if [[ `cmd /c ver` =~ Version\ *[6789] ]]
    then
        if [[ `uname -a` =~ cygwin ]]
        then
            CYGWIN=`echo "$CYGWIN" | sed -e 's/winsymlinks[:a-z]*//' -e 's/$/ winsymlinks:native/'`
            export CYGWIN
        fi
    fi
}

setupEnv
sScript="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/modman.php";
php "$sScript" "$@"; 
