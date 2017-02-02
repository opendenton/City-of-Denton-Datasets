<?php

require_once 'vendor/autoload.php';

$token = 'd95e9973e43753fc786b3535d4828f37b4e3b115'; // Add valid Github token here.

// Github
$client = new \Github\Client();
$client->authenticate($token, Github\Client::AUTH_URL_TOKEN);

// CKAN / City of Denton
$url = 'http://data.cityofdenton.com/api/3/action/package_search?rows=200&start=0';
$json = file_get_contents($url);
$obj = json_decode($json);
// Convert to array for simplicity.
$array = json_decode(json_encode($obj), true);

// If response is good, start looping.
if (!empty($array['result']['results'])) {
	$results = $array['result']['results'];
//	k($results);
  $count = 0;
	foreach ($results as $result) {
	  // First loop is the actual dataset.
    $title = $result['title'] . ' : ' . $result['id'];
    $labels = array();
    // Start assembling the body.
    $body = array();
    $body[] = "**Title:** ${result['title']}";
    $body[] = "**ID:** ${result['id']}";
    $body[] = "**Description:** " . truncate(strip_tags($result['notes'], '<a>'), 300); // leave links

    $created = $result['metadata_created'];
    $body[] =  '**Created:** ' . date("m / d / Y", strtotime($created));
    $updated = $result['metadata_modified'];
    $body[] =  '**Updated:** ' . date("m / d / Y", strtotime($created));
    $body[] =  "**State:** ${result['state']}";
    $body[] =  "**Organization:** " . $result['organization']['title'];
    $url = "http://data.cityofdenton.com/dataset/" . $result['name'];
    $body[] =  "**URL:** [${url}](${url})";
    // Tags
    if ($tags = $result['tags']) {
      $newTags = array();
      foreach ($tags as $tag) {
        $newTags[] = $tag['name'];
      }
      $body[] =  "**Tags:** " . implode(', ', $newTags);
    }
    // Groups
    if ($groups = $result['groups']) {
      $newGroups = array();
      foreach ($groups as $group) {
        $newGroups[] = $group['title'];
      }
      $body[] =  "**Groups:** " . implode(', ', $newGroups);
    }

    // Resources
    if ($resources = $result['resources']) {
      $body[] = "\n### Resources\n\n";
      foreach ($resources as $resource) {
        $body[] = '- ['. $resource['name'] .'](' . $resource['url'] . '), ' . $resource['format'];
        $labels[] = $resource['format'];
      }
    } else {
      $labels[] = 'missing-resources';
    }

    array_filter($labels);

    $issue_template = array(
      'title' => $title,
      'body' => implode("\n", $body),
    );

    // Clean labels
    foreach ($labels as $k => $v) {
      if (empty($v)) {
        unset($labels[$k]);
      }
    }
    if (!empty($labels)) {
      $issue_template['labels'] = $labels;
    }

    if ($count > 135) {

      $client->api('issue')->create('opendenton', 'City-of-Denton-Datasets', $issue_template);
      k("Issue submitted: ${title}");
      // Slow it down so Github won't notice.
      sleep(rand(1,10));
    }
    $count++;
  }
}

/**
 * Truncate string to special char with limit.
 *
 * @param $string
 * @param $limit
 * @param string $break
 * @param string $pad
 * @return string
 */
function truncate($string, $limit, $break=".", $pad="...") {
  // return with no change if string is shorter than $limit
  if(strlen($string) <= $limit) return $string;

  // is $break present between $limit and the end of the string?
  if(false !== ($breakpoint = strpos($string, $break, $limit))) {
    if($breakpoint < strlen($string) - 1) {
      $string = substr($string, 0, $breakpoint) . $pad;
    }
  }

  return $string;
}