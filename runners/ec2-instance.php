<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/data/costcenter.php';

  USE Aws\Ec2\Ec2Client                 AS EC2Client,
      Aws\CloudWatch\CloudWatchClient   AS ACWClient;

$account  = str_replace("\n",'',shell_exec("aws ec2 describe-security-groups --group-names 'Default' --query 'SecurityGroups[0].OwnerId' --output text"));
$location = dirname(__DIR__) . "/output/$account-describe-ec2instances.csv";
$profile  = shell_exec('echo $AWS_SECTION |xargs echo -n');

if (! isset($ownermap)) Throw New \RuntimeException('$ownermap needs to be defined, but isn\'t');

$client = EC2Client::factory([
    'profile' => $profile,
    'region'  => 'us-east-1',
    'version' => 'latest',
]);

$output   = [];
foreach($client->describeInstances()->get('Reservations') AS $instance) {
  $instance = array_pop($instance['Instances']);

  $tags     = []; // reset
  $env      = false;
  $cc       = false;

  if (isset($instance['Tags'])) {
    foreach ($instance['Tags'] AS $key) {
      $val = strtolower($key['Value']); //wasted operations but stfu....
      if ('environment' == strtolower($key['Key'])) $env = $val;
      if ('costcenter'  == strtolower($key['Key'])) $cc  = $val;
    }
  }

  $owner = false;
  foreach ($ownermap AS $entity => $arr) {
    if (in_array($cc, $arr)) {
      $owner = $entity;
      break;
    }
  }

  $output[] = [
    'Owner'       => $owner,
    'CostCenter'  => $cc,
    'InstanceId'  => $instance['InstanceId'],
    'Environment' => $env,
    'State'       => $instance['State']['Name'],
    'Type'        => $instance['InstanceType'],
    'Zone'        => $instance['Placement']['AvailabilityZone'],
    'Monitoring'  => $instance['Monitoring']['State'],
  ];
}

$csv = "Owner, Costcenter, InstanceId, Environment, State, TYpe, Zone, Monitoring\n";
foreach ($output AS $key => $val) {
  $csv .= implode(', ', $val) . "\n";
}

file_put_contents($location, $csv);
