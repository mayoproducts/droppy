<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2022-02-04 00:48:09 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-02-04 00:51:35 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-02-04 03:29:39 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-02-04 08:57:20 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-02-04 11:41:56 --> in gendi  messagea6cK3U
ERROR - 2022-02-04 11:41:56 --> in register messageArray
(
    [share] => link
    [destruct] => no
    [email_to] => Array
        (
            [0] => 
        )

    [email_from] => 
    [message] => 
    [password] => 
    [expire] => 604800
    [upload_id] => a6cK3U
)

ERROR - 2022-02-04 11:41:56 --> postdata in indexArray
(
    [upload_id] => a6cK3U
    [file_uid] => 9xmloinaj9t
)

ERROR - 2022-02-04 11:41:56 --> in index function messageArray
(
    [files] => Array
        (
            [0] => stdClass Object
                (
                    [name] => admin_screen.JPG
                    [size] => 61113
                    [type] => image/jpeg
                    [url] => https://www.wetfr.com/uploads/temp/admin_screen.JPG
                    [deleteUrl] => https://www.wetfr.com/index.php?file=admin_screen.JPG
                    [deleteType] => DELETE
                )

        )

)

ERROR - 2022-02-04 11:41:56 --> in complete messageArray
(
    [share] => link
    [destruct] => no
    [email_to] => Array
        (
            [0] => 
        )

    [email_from] => 
    [message] => 
    [password] => 
    [expire] => 604800
    [upload_id] => a6cK3U
)

ERROR - 2022-02-04 11:42:35 --> in gendi  messageAUyFiU
ERROR - 2022-02-04 11:42:35 --> in register messageArray
(
    [share] => link
    [destruct] => no
    [email_to] => Array
        (
            [0] => 
        )

    [email_from] => 
    [message] => 
    [password] => 
    [expire] => 604800
    [upload_id] => AUyFiU
)

ERROR - 2022-02-04 11:42:35 --> postdata in indexArray
(
    [upload_id] => AUyFiU
    [file_uid] => kcd4cudrbx
)

ERROR - 2022-02-04 11:42:35 --> in index function messageArray
(
    [files] => Array
        (
            [0] => stdClass Object
                (
                    [name] => admin_screen.JPG
                    [size] => 61113
                    [type] => image/jpeg
                    [url] => https://www.wetfr.com/uploads/temp/admin_screen.JPG
                    [deleteUrl] => https://www.wetfr.com/index.php?file=admin_screen.JPG
                    [deleteType] => DELETE
                )

        )

)

ERROR - 2022-02-04 11:42:36 --> in complete messageArray
(
    [share] => link
    [destruct] => no
    [email_to] => Array
        (
            [0] => 
        )

    [email_from] => 
    [message] => 
    [password] => 
    [expire] => 604800
    [upload_id] => AUyFiU
)

ERROR - 2022-02-04 23:44:43 --> Query error: In aggregated query without GROUP BY, expression #1 of SELECT list contains nonaggregated column 'droppy2.droppy_uploads.size'; this is incompatible with sql_mode=only_full_group_by - Invalid query: SELECT droppy_uploads.size*count(droppy_downloads.id) AS `total_downloaded`
FROM `droppy_downloads`
JOIN `droppy_uploads` ON `droppy_downloads`.`download_id` = `droppy_uploads`.`upload_id`
WHERE `droppy_uploads`.`pm_email` = '8'
ERROR - 2022-02-04 23:44:43 --> Severity: error --> Exception: Call to a member function num_rows() on bool /var/www/html/application/plugins/droppy_premium/models/Downloads.php 70
