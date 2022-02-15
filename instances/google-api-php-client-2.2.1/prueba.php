<?php
/*
 * BEFORE RUNNING:
 * ---------------
 * 1. If not already done, enable the Compute Engine API
 *    and check the quota for your project at
 *    https://console.developers.google.com/apis/api/compute
 * 2. This sample uses Application Default Credentials for authentication.
 *    If not already done, install the gcloud CLI from
 *    https://cloud.google.com/sdk and run
 *    `gcloud beta auth application-default login`.
 *    For more information, see
 *    https://developers.google.com/identity/protocols/application-default-credentials
 * 3. Install the PHP client library with Composer. Check installation
 *    instructions at https://github.com/google/google-api-php-client.
 */

// Autoload Composer.
require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setApplicationName('Google-ComputeSample/0.1');
$client->useApplicationDefaultCredentials();
$client->addScope('https://www.googleapis.com/auth/cloud-platform');

$service = new Google_Service_Compute($client);

// Project ID for this request.
$project = 'my-project';  // TODO: Update placeholder value.

// The name of the zone for this request.
$zone = 'my-zone';  // TODO: Update placeholder value.

// Name of the instance resource to start.
$instance = 'my-instance';  // TODO: Update placeholder value.

$response = $service->instances->start($project, $zone, $instance);

// TODO: Change code below to process the `response` object:
echo '<pre>', var_export($response, true), '</pre>', "\n";
?>
