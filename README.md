﻿modman-php
==========

PHP implementation for modman, to use it on every operating system with PHP support (also Windows).

Currently implemented:
 - init (creates .modman directory)
 - link <target> (creates symlinks)
 - deploy <module> (update symlinks)
 - deploy-all (updates all modules)
 - repair (repairs all symlinks)
 - clean (removes all dead symlinks)
 - create (creates a modman file for an existing module)
 - clone (clones a git repository)
 - remove <target> (removes the symlinks)


--force is available for link, deploy, deploy-all, remove and clone, if not set script aborts when conflicts are found

Usage examples:

    php modman.php init
    php modman.php link ..\B2BProfessional
    php modman.php deploy B2BProfessional

Or directly clone which does also init and deploy:

    php modman.php clone https://github.com/sitewards/B2BProfessional

Currently supported in modman files:
 - symlinks (incl. wildcards)
 - @import
 - @shell

For Windows users there's also a batch file available, so instead of typing php and directory to modman.php you could just use modman.bat everywhere if you add it to your %PATH%-variable:

    modman link c:\B2BProfessional


Started at Magento Hackathon in Zürich 2013-03-09


Influenced by the original modman at https://github.com/colinmollenhour/modman/


init
====

Creates the .modman directory, which is used for all other operations.

    cd $PROJECT
    modman init

or

    cd $PROJECT
    modman init <basedir>

If you don't specify a basedir (aka magento directory) the current working directory will be used.
The basedir functionality is supposed to be used to move the .modman directory outside of the magento main directory.
- That first of all helps to structure your projects better
- But is also a security feature as modman might link sensitive data like docs into your magento magento main directory.

link
====

Creates symlink from a modman file

    cd $PROJOECT
    modman link /path/to/myMageModule

Optional parameter --force to automatically remove conflicted files

deploy
======

Updates the symlinks of a linked module

    cd $PROJECT
    modman deploy myMageModule

Optional parameter --force to automatically remove conflicted files

deploy-all
==========

Updates all symlinks of linked modules

    cd $PROJECT
    modman deploy-all

Optional parameter --force to automatically remove conflicted files

repair
======

Repairs all symlinks of all linked modules

    cd $PROJECT
    modman repair

clean
=====

Scans directory for dead symlinks and deletes them. Useful if a module was deleted and not removed in the project

    cd $PROJECT
    modman clean

remove
======

Removes links of a project

    cd $PROJECT
    modman remove myMageModule

create
======

Scans through the current directory and creates a modman file containing all files and folders

    cd $MODULE
    modman create
	
	
Optional parameter --force to automatically overwrite existing modman-file.
Optional parameter --include-hidden to list hidden files and directories in modman-file.
Optional parameter --include <include_file> to include a template file at the end of the new modman-file.
If the current directory is recognized as a magento module, only the path to the module's code directory is added to the modman file.

clone
=====

Clones a git repository

    cd $PROJECT
    modman clone https://git.url

Optional parameter --force to overwrite existing folder.
Optional parameter --create-modman to create a new modman file in the cloned folder if there is no modman file yet.

Feature ideas
=============

- Check if "allow symlinks" is activated in Magento when linking template files
