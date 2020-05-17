<?php
/**
 * For Travis CI
 */

require 'vendor/autoload.php';

$bucket = getenv('S3_BUCKET') ? getenv('S3_BUCKET') : 'test-bucket';
$s3 = new Origin\Storage\Engine\S3Engine([
    'credentials' => [
        'key' => getenv('S3_KEY') ? getenv('S3_KEY') : 'minioadmin',
        'secret' => getenv('S3_SECRET') ? getenv('S3_SECRET') : 'minioadmin',
    ],
    'region' => 'us-east-1',
    'version' => 'latest',
    'endpoint' => getenv('S3_ENDPOINT') ?  getenv('S3_ENDPOINT') : 'http://127.0.0.1:9000', // for S3 comptabile protocols
    'bucket' => $bucket //$this->env('S3_BUCKET')
]);

if ($s3->createBucket($bucket)) {
    echo 'Bucket Created';
} else {
    throw new Exception('Error Creating Bucket');
}
