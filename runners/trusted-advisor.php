<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require dirname(__DIR__) . '/vendor/autoload.php';  //composer + autoloader
require dirname(__DIR__) . '/data/costcenter.php';  //OwnerMap array var

  USE Aws\Support\SupportClient;
  USE Aws\CloudWatch\CloudWatchClient;


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
  '0',            //idk?
  '1',            //idk?
  '2',            //idk?
];


//get summaries
$flagged      = $result['flaggedResources'];
$summary      = $result['resourcesSummary'];
$optimization = $result['categorySpecificSummary']['costOptimizing'];


foreach ($flagged AS $pos => $resource) {
  if (isset($resource['metadata']) && !empty($resource['metadata'])) {

    $metadata[( //is the VolumeId really present? It is the array key || default to arrs position
        isset($resource['metadata'][1]) && preg_match('/^vol\-[a-z0-9]{8}/', $resource['metadata'][1])
          ? $resource['metadata'][1]
          : $pos
      )] = array_combine($metaKeys, $resource['metadata']);

  }
}


  unset($supportClient); //clear buffer


//iterate meta
foreach ($metadata AS $resource) {

    $cloudClient = CloudWatchClient::factory([
      'profile' => $profile,
      'region'  => $resource['Region'],
      'version' => 'latest',
    ]);


  //TODO: Reference from, http://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.CloudWatch.CloudWatchClient.html#_getMetricStatistics
  $metrics = $cloudClient->getMetricStatistics([
    'Namespace'  => 'AWS/EBS;',
    'MetricName' => 'VolumeIdleTime',
    'Dimensions' => [[
      'Name' => 'VolumeId', 'Value' => $resource['VolumeId']    //do to the way that this is constructed im pretty sure that you can flood it with values....
    ]],
    'StartTime'  => strtotime('-14 days'),
    'EndTime'    => strtotime('now'),
    'Period'     => 3000,
    'Statistics' => ['Maximum', 'Minimum'],
  ]);


  print_r(json_encode($metrics, JSON_PRETTY_PRINT));
  unset($cloudClient);
}


  //fuck-the-police...
  __halt_compiler();