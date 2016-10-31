<?php // REQ: composer require aws/aws-sdk-php
date_default_timezone_set('America/Los_Angeles');

require 'vendor/autoload.php';

  USE Aws\Rds\RdsClient;

  $account = shell_exec("aws ec2 describe-security-groups --group-names 'Default' --query 'SecurityGroups[0].OwnerId' --output text")

// Map of CostCenter owners to their appr CostID's
$ownermap = [
  'Tegrity'           => ['91412022111113'],
  'MHCampus'          => ['91412022111115'],
  'Learning Objects'  => ['91502168111390'],
  'Analytics'         => ['91502168111391'],
  'DevOps'            => ['91502168112600'],
  'Jira/Confluence'   => ['91502168603179'],
  'Create'            => ['91502746111098'],
  'Connect'           => ['91502746111100'],
  'EZTest'            => ['91502746111107'],
  'Ecommerce'         => ['91502746111110'],
  'ConnectEd'         => ['91602013111260'],
  'Datapipe Charges'  => ['91502168112600'],
  'DLE Charges'       => ['91502168112745'],
  'Engrade Charges'   => ['91742044112200'],
  'TVS Charges'       => ['91502022112200'],
  'LSP Charges'       => ['91502129112200'],
  'LST - Learn Smart' => ['91502746111101',
                          '91502168112755',
                          '91502614603442',
                          '91502614603457',
                          '91602013112200',
                         ],
  'Prod'               => ['652911051897'],
  'Non-Prod'           => ['490928256831'],
  'PCI'                => ['352304727167'],
  'Keys'               => ['413525480853'],
  'LSTECH'             => ['area 9'],
];

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
