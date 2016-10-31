<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/data/costcenter.php';

  USE Aws\Ec2\Ec2Client;

  $account = shell_exec("aws ec2 describe-security-groups --group-names 'Default' --query 'SecurityGroups[0].OwnerId' --output text");

if (! isset($ownermap)) Throw New \RuntimeException('$ownermap needs to be defined, but isn\'t');

$client = Ec2Client::factory([
    'profile' => 'non',
    'region'  => 'us-east-1',
    'version' => 'latest',
]);

$output   = [];
$untagged = [];
foreach($client->describeVolumes()->get('Volumes') AS $volume) {
  $tags   = []; // reset

  $cc = false;
  if (isset($volume['Tags'])) {
    foreach($tags AS $key) {
      if ('costcenter' == strtolower($key['Key'])) {
         $cc = $key['Value']; break;
      }
  }

  if (! isset($volume['Tags'])) $untagged[] = json_encode($volume);
}

$owner = false;
foreach ($ownermap AS $entity => $arr) {
  if (in_array($cc, $arr)) {
    $owner = $entity; break;
  }
}

$output[] = [
'Owner'       => "{$owner}",
'Costcenter'  => "{$cc}",
'VolumeID'    => "{$volume['VolumeId']}",
'Encrypted'   => "{$volume['Encrypted']}",
];

$csv = "VolumeId, InstanceId, Costcenter\n";

foreach ($output AS $key => $val) {
  $csv .= implode(', ', $val) . "\n";
}

file_put_contents(dirname("output/{$account}-describe-volumes.csv"), $csv);
