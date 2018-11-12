<?php

return [
    'access_key' => env('QINIU_ACCESS_KEY'),
    'access_secret' => env('QINIU_SECRET_KEY'),
    'bucket' => env('QINIU_BUCKET'),
    'domain' => env('QINIU_DOMAIN'),
    'upload_url' => env('QINIU_UPLOAD_URL'),
    'file_size' => env('QINIU_FILE_SIZE', 40),
];