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

function formatTimestampLocalDateLong($timestamp)
{
  return format_timestamp($timestamp, '%d. %B %Y');
}

function formatTimestampLocalDateLongNoYear($timestamp)
{
  return format_timestamp($timestamp, '%d. %B');
}

function formatTimestampLocalTime($timestamp)
{
  return format_timestamp($timestamp, '%H:%M');
}

function formatTimestampLocalDateTime($timestamp)
{
  return format_timestamp($timestamp, '%d. %B %Y, %H:%M Uhr');
}

function formatTimestampLocalDateTimeLongNoYear($timestamp)
{
  return format_timestamp($timestamp, '%d. %B, %H:%M Uhr');
}
