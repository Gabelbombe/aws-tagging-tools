<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/data/costcenter.php';

  USE Aws\S3\S3Client;

  $account  = str_replace("\n",'',shell_exec("aws ec2 describe-security-groups --group-names 'Default' --query 'SecurityGroups[0].OwnerId' --output text"));
  $location = dirname(__DIR__) . "/output/$account-describe-s3buckets.csv";
  $profile  = shell_exec('echo $AWS_SECTION |xargs echo -n');

if (! isset($ownermap)) Throw New \RuntimeException('$ownermap needs to be defined, but isn\'t');

$client = S3Client::factory([
    'profile' => $profile,
    'region'  => 'us-east-1',
    'version' => 'latest',
]);


$output = [];
foreach ($client->listBuckets() ['Buckets'] AS $bucket) {
  $tags = []; // reset

  try {
    $tags = $client->getBucketTagging([
      'Bucket' => $bucket['Name'],
    ]) ['TagSet'];

  } catch (\Exception $e) {
    $tags[] = [
      'Key' => 'CostCenter',
      'Value' => false
    ];
  }

  $cc = false;
  foreach ($tags AS $tag) {
    if ('costcenter' === strtolower($tag['Key'])) $cc = $tag['Value'];
  }

  $owner = false;
  foreach ($ownermap AS $entity => $arr) {
    if (in_array($cc, $arr)) {
      $owner = $entity;
      break;
    }
  }

  $output[] = [
    'Owner'      => "{$owner}",
    'CostCenter' => "{$cc}",
    'BucketName' => "{$bucket['Name']}",
  ];
}

$csv = "Owner, CostCenter, BucketName\n";
foreach ($output AS $key => $val) {
  $csv .= implode(', ', $val) . "\n";
}

file_put_contents($location, $csv);
