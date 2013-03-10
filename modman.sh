#!/bin/bash

sScript="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/modman.php";
php "$sScript" "$@"; 
