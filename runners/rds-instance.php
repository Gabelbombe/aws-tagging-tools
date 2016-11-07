<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/data/costcenter.php';

    USE Aws\Rds\RdsClient;

$account = shell_exec("aws ec2 describe-security-groups --group-names 'Default' --query 'SecurityGroups[0].OwnerId' --output text");

if (! isset($ownermap)) Throw New \RuntimeException('$ownermap needs to be defined, but isn\'t');

$client = RdsClient::factory([
    'profile' => 'non',
    'region'  => 'us-east-1',
    'version' => 'latest',
]);

$output = [];
foreach ($client->describeDBInstances() ['DBInstances'] AS $instance) {
  $tags     = []; // reset

  $tags = $client->ListTagsForResource([
    'ResourceName' => trim($instance['DBInstanceArn']),
    'Filters'      => [],
  ]) ['TagList'];

  $cc = false;
  foreach($tags AS $key) {
    if ('costcenter' == strtolower($key['Key'])) {
       $cc = $key['Value']; break;
    }
  }

  $owner = false;
  foreach ($ownermap AS $entity => $arr) {
    if (in_array($cc, $arr)) {
      $owner = $entity; break;
    }
  }

  $output[] = [
    'Owner'       => "{$owner}",
    'Costcenter'  => "'{$cc}'",
    'InstanceID'  => "{$instance['DBInstanceIdentifier']}",
    'InstanceARN' => "{$instance['DBInstanceArn']}",

    'ClusterID'   => isset($instance['DBClusterIdentifier'])
      ? "{$instance['DBClusterIdentifier']}"
      : false,
  ];
}

$csv    = "Owner, Costcenter, InstanceID, InstanceARN, ClusterID\n";

foreach ($output AS $key => $val) {
  $csv .= implode(', ', $val) . "\n";
}

file_put_contents("output/{$account}-describe-rds.csv", $csv);
