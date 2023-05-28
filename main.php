<?php

$data = ['tr', 'abc', 'bca', 'rt', 'rtr', 'op', 'cba', 'po', 'acacb'];


// TASK: Write the function which is able to group $data array of strings by similar characters, see $expected array below (You have to write native PHP function)
//$expected = [
//    ['tr', 'rt', 'rtr'],
//    ['abc','bca', 'cba', 'acacb'],
//    ['op', 'po'],
//];

function groupBySimilarCharacters($data)
{
  $result = [];
  foreach ($data as $string) {
    $found = false;
    foreach ($result as &$group) {
      if (hasSimilarCharacters($string, $group[0])) {
        $group[] = $string;
        $found = true;
        break;
      }
    }
    if (!$found) {
      $result[] = [$string];
    }
  }
  return $result;
}

function hasSimilarCharacters($str1, $str2)
{
  $chars1 = str_split($str1);
  $chars2 = str_split($str2);
  sort($chars1);
  sort($chars2);
  return implode($chars1) === implode($chars2);
}

$expected = groupBySimilarCharacters($data);
print_r($expected);
