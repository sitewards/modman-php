modman-php
==========

PHP implementation for modman, to use it on every operating system with PHP support (also Windows).

Currently implemented:
 - init (creates .modman directory)
 - link <target> (creates symlinks)
 - deploy <module> (update symlinks)
 - deploy-all (updates all modules)
 - repair (repairs all symlinks)

 --force is available for link, deploy and deploy-all, if not set script aborts when conflicts are found

Usage examples:

    php modman.php init
    php modman.php link ..\B2BProfessional
    php modman.php deploy B2BProfessional


 Started at Magento Hackathon in Zürich 2013-03-09


 Influenced by the original modman at https://github.com/colinmollenhour/modman/