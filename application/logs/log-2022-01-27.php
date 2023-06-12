<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2022-01-27 02:45:45 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-01-27 03:05:14 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-01-27 12:07:00 --> Query error: In aggregated query without GROUP BY, expression #1 of SELECT list contains nonaggregated column 'droppy2.droppy_uploads.size'; this is incompatible with sql_mode=only_full_group_by - Invalid query: SELECT droppy_uploads.size*count(droppy_downloads.id) AS `total_downloaded`
FROM `droppy_downloads`
JOIN `droppy_uploads` ON `droppy_downloads`.`download_id` = `droppy_uploads`.`upload_id`
WHERE `droppy_uploads`.`pm_email` = '6'
ERROR - 2022-01-27 12:07:00 --> Severity: error --> Exception: Call to a member function num_rows() on bool /var/www/html/application/plugins/droppy_premium/models/Downloads.php 70
ERROR - 2022-01-27 12:07:09 --> Query error: In aggregated query without GROUP BY, expression #1 of SELECT list contains nonaggregated column 'droppy2.droppy_uploads.size'; this is incompatible with sql_mode=only_full_group_by - Invalid query: SELECT droppy_uploads.size*count(droppy_downloads.id) AS `total_downloaded`
FROM `droppy_downloads`
JOIN `droppy_uploads` ON `droppy_downloads`.`download_id` = `droppy_uploads`.`upload_id`
WHERE `droppy_uploads`.`pm_email` = '6'
ERROR - 2022-01-27 12:07:09 --> Severity: error --> Exception: Call to a member function num_rows() on bool /var/www/html/application/plugins/droppy_premium/models/Downloads.php 70
ERROR - 2022-01-27 16:51:49 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-01-27 18:03:55 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-01-27 18:11:26 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
ERROR - 2022-01-27 18:13:54 --> Severity: error --> Exception: count(): Argument #1 ($value) must be of type Countable|array, bool given /var/www/html/application/libraries/CompleteHandler.php 108
