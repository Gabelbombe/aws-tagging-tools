<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require dirname(__DIR__) . '/vendor/autoload.php';

  USE Aws\Ec2\Ec2Client;

$account  = str_replace("\n",'',shell_exec("aws ec2 describe-security-groups --group-names 'Default' --query 'SecurityGroups[0].OwnerId' --output text"));
$location = dirname(__DIR__) . "/output/$account-describe-ec2volumes.csv";
$profile  = shell_exec('echo $AWS_SECTION |xargs echo -n');

if (! isset($ownermap)) Throw New \RuntimeException('$ownermap needs to be defined, but isn\'t');

$client = EC2Client::factory([
    'profile' => $profile,
    'region'  => 'us-east-1',
    'version' => 'latest',
]);

$tags = [

];

foreach($client->describeInstances()->get('Reservations') AS $instance) {
  $instance   = array_pop($instance['Instances']);
  $instanceId = $instance['InstanceId'];

  try {

  } Catch (\Aws\Ec2\Exception\Ec2Exception $ec2Exception) {

  }
}
