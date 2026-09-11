<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

/**
 * If you copy this project into a subfolder of your WAMPP htdocs, e.g.
 *   C:/wamp64/www/pool-booking-php
 * and open it as http://localhost/pool-booking-php/, set BASE_URL below
 * to match:
 *
 *   define('BASE_URL', '/pool-booking-php');
 *
 * If this project itself IS your htdocs root, or you set up a dedicated
 * virtual host that points straight at it, leave BASE_URL as ''.
 */
// define('BASE_URL', '/aws-swimming-pool'); if using wampp localhost need use this !!!***

define('BASE_URL', '');

/**
 * Where pool photos are served from.
 *
 * Local (WAMPP): leave this empty — images load from the local
 * /images folder via BASE_URL, same as before.
 *
 * On AWS: set the IMAGE_BASE_URL environment variable to your S3
 * bucket's public URL (with a trailing slash), e.g.
 *   https://splashpoint-pool-images-yourname.s3.us-east-1.amazonaws.com/
 * index.php automatically switches to loading images from there instead.
 */
define('IMAGE_BASE_URL', getenv('IMAGE_BASE_URL') ?: '');
