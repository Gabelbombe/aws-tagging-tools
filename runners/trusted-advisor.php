<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/data/costcenter.php';

  USE Aws\Support\SupportClient;
  USE Aws\Ec2\Ec2Client;

$account  = str_replace("\n",'',shell_exec("aws ec2 describe-security-groups --group-names 'Default' --query 'SecurityGroups[0].OwnerId' --output text"));
$location = dirname(__DIR__) . "/output/$account-describe-s3buckets.csv";
$profile  = shell_exec('echo $AWS_SECTION |xargs echo -n');

$types    = [];
$trusted  = New \stdClass();

$supportClient = SupportClient::factory([
  'profile' => $profile,
  'region'  => 'us-east-1',
  'version' => 'latest',
]);

$results = $supportClient->describeTrustedAdvisorChecks([
  'language' => 'en',
]) ['checks'];

foreach ($results AS $result) {
  $typeKey = null; //clear

  $types[preg_replace_callback("/(?:^|_)([a-z])/", function($matches) {
    return strtoupper($matches[1]);
  }, $result['category'])][$result['id']] = $result['name'];
}

foreach ($types AS $type => $value) { $trusted->$type = $value; } //sure there's a saner way to change type....

  unset($types); //clear

//get criteria key
foreach ($trusted->CostOptimizing AS $optKey => $optCriteria) {
  if (preg_match('/ebs/i', $optCriteria)) {
    $typeKey = $optKey;
    break;
  }
}

$result = $supportClient->describeTrustedAdvisorCheckResult([
  'checkId'  => $typeKey,
  'language' => 'en',
]) ['result'];


$metaKeys = [
  'Region',
  'VolumeId',
  'Name',
  'VolumeType',
  'Size',
  'Savings',
  '0',
  '1',
  '2',
];


//get summaries
$flagged      = $result['flaggedResources'];
$summary      = $result['resourcesSummary'];
$optimization = $result['categorySpecificSummary']['costOptimizing'];

foreach ($flagged AS $pos => $resource) {
  if (isset($resource['metadata']) && !empty($resource['metadata'])) {

//echo isset($resource['metadata'][1]); die;
    $metadata[( //is the VolumeId really present? It is the array key || default to arrs position
        isset($resource['metadata'][1]) && preg_match('/^vol\-[a-z0-9]{8}/', $resource['metadata'][1])
          ? $resource['metadata'][1]
          : $pos
      )] = array_combine($metaKeys, $resource['metadata']);

  }
}

  unset($supportClient); //clear buffer

/**
 * Effed up here, i need cloudwatch instead of ec2 client i think.....
 */


foreach ($metadata AS $resource) {
  $ec2Client = Ec2Client::factory([
    'profile' => $profile,
    'region'  => $resource['Region'],
    'version' => 'latest',
  ]);



  unset($ec2Client);
}




print_r($metadata); exit;