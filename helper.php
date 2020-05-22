<?php

function connectToDb()
{
  global $config;
  global $db;
  $db = new db_writer($config);
}


function db()
{
  global $db;
  return $db;
}


function getConfigValue($key, $defaultValue = null)
{
  global $config;
  return array_value($config, $key, $defaultValue);
}


function formatTimestampLocalLong($timestamp, $precision = 'minute', $showYear = true)
{
  if ($showYear)
  {
    if ($precision == 'day')
      $format = 'X%d. %B %Y';
    else if ($precision == 'hour')
      $format = 'X%d. %B %Y, %H:00 Uhr';
    else if ($precision == 'minute')
      $format = 'X%d. %B %Y, %H:%M Uhr';
    else
      $format = 'X%d. %B %Y, %H:%M:%S Uhr';
  }
  else
  {
    if ($precision == 'day')
      $format = 'X%d. %B';
    else if ($precision == 'hour')
      $format = 'X%d. %B, %H:00 Uhr';
    else if ($precision == 'minute')
      $format = 'X%d. %B, %H:%M Uhr';
    else
      $format = 'X%d. %B, %H:%M:%S Uhr';
  }
  return format_timestamp($timestamp, $format);
}


function formatTimestampLocalShort($timestamp, $precision = 'minute')
{
  if ($precision == 'day')
    $format = '%d.%m.%Y';
  else if ($precision == 'hour')
    $format = '%d.%m.%Y %H:00';
  else if ($precision == 'minute')
    $format = '%d.%m.%Y %H:%M';
  else
    $format = '%d.%m.%Y %H:%M:%S';
  return format_timestamp($timestamp, $format);
}
