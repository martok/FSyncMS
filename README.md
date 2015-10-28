FSyncMS
=======

PHP Sync Server for Firefox/Pale Moon Sync
An extension of the Weave-Minimal Server.

More information about the original implementation of this server, past versions, etc.
can be found here:

https://www.ohnekontur.de/category/technik/sync/fsyncms/

**Contributing Authors**:

* Balu (Original Author)
* j-ed (passing HTTP/S state to iPhone/iPod Touch)
* rivu (PostgreSQL support)
* tobiashollerung (SQL improvements)
* Trellmor (bcrypt passwort hashing)
* Moonchild (Pale Moon Sync: Account removal)
* Martok (merging forks, code refactoring)


Although the original author has planned further extesnions to this implementation,
the current state of this server implementation is rather stagnant ans missing two
important features:

* Delete account from the web
* Reset password from the web (similar to reset inside the client)

If you wish to help complete the missing features, please feel free to clone this repository and make 
the necessary edits -- kindly submit a pull request after you've tested your changes so it can be merged
back in and improve this software!

Release notes for older original versions:

FSyncMS v013
======
Database upgrade
for more information and some migration notice see
http://www.ohnekontur.de/2013/07/05/fsyncms-version-0-13-database-upgrade/


FSyncMS v012
======
Compatibility update 

FSyncMS v011
======
Added dedicated setup script, which will create the database and the config file: settings.php

If you want to create it by your own, just generate the settings.php with the following content

    <?php
        //you can disable registration to the firefox sync server here,
        // by setting ENABLE_REGISTER to false
        //
        //
        //define("ENABLE_REGISTER",false);
        define("ENABLE_REGISTER", true);


        //pleas set the URL where firefox clients find the root of 
        // firefox sync server
        // this should end with a /
        //
        define("FSYNCMS_URL","https://DOMAIN.de/Folder_und_ggf_/index.php/");

        //MYSQL Params
        define("MYSQL_ENABLE", false);
        define("MYSQL_HOST","localhost");
        define("MYSQL_DB","databaseName");
        define("MYSQL_USER", "databaseUserName");
        define("MYSQL_PASSWORD", "databaseUserPW");

    ?>


FSyncMS v010
======
MYSQL Support

FSyncMS v 09
======
Change Password now supported 
working with firefox 12 (and lower)

Changelog:
Added change Password feature

FSyncMS v 08
======
Should be working with firefox 11 and lower (tested with 11)

Changelog:
Fixed user registration process,
fixed some delete problems
