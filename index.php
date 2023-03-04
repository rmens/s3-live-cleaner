<?php

require_once __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$bucketName = 'live'; // Replace with your bucket name
$endpointUrl = 'https://9834cfb59e6c8c60a1ca5d7ec4654439.r2.cloudflarestorage.com/'; // Replace with your custom endpoint URL

$s3Config = [
    'version' => 'latest',
    'region'  => 'auto', // Replace with your bucket's region
    'endpoint' => $endpointUrl, // Set the custom endpoint URL
];

$s3 = new S3Client($s3Config);

$objects = $s3->listObjects([
    'Bucket' => $bucketName,
]);

$currentTime = time();
$objectsToDelete = [];

foreach ($objects['Contents'] as $object) {
    $key = $object['Key'];
    $lastModified = strtotime($object['LastModified']);

    if (strpos($key, 'm3u8') !== false || strpos($key, 'offline.ts') !== false) {
        continue;
    }

    if (($currentTime - $lastModified) > 3600) {
        $objectsToDelete[] = ['Key' => $key];
    }
}

if (!empty($objectsToDelete)) {
    $chunks = array_chunk($objectsToDelete, 1000);
    foreach ($chunks as $chunk) {
        try {
            $result = $s3->deleteObjects([
                'Bucket' => $bucketName,
                'Delete' => ['Objects' => $chunk],
            ]);
            echo "Deleted " . count($chunk) . " objects\n";
        } catch (AwsException $e) {
            echo $e->getMessage() . "\n";
        }
    }
} else {
    echo "No objects to delete\n";
}

?>
