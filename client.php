<?php

function setClient($newClient)
{
  global $client;
  return $client = $newClient;
}


function getClientValue($key, $defaultValue = null)
{
  global $client;
  return array_value($client, $key, $defaultValue);
}


function isClientAdmin()
{
  return getClientValue('userGroup') == 1;
}


function initClientIdentification()
{
  $cookieExpire = null;

  $a = explode(':', get_cookie('seatbooking_client'));
  $clientId = $a[0];
  $clientToken = array_value($a, 1);
  $isValid = false;
  if ($clientId != null)
  {
    $client = db()->try_query_row_by_id('client', $clientId);
    if (array_value($client, 'token') == $clientToken)
    {
      $isValid = true;
      setClient($client);
    }
  }

  if ($isValid)
  {
    if ($client['persistent'] == 1)
      $cookieExpire = time() + 365 * 24 * 60 * 60;

    # update client row
    $client = [];
    fillClientInfo($client);
    db()->try_update_by_id('client', $clientId, $client);
  }
  else
  {
    # insert client row
    $clientCount = db()->query_value('SELECT COUNT(*) FROM client');

    $clientToken = generate_token();

    $client = [];
    $client['token'] = $clientToken;
    if ($clientCount == 0)
    {
      # make first client admin
      $client['userGroup'] = 1;
      $client['persistent'] = 1;
    }
    $client['insertTimestamp'] = format_timestamp(time());
    fillClientInfo($client);
    $clientId = db()->insert('client', $client);
    $client['id'] = $clientId;
    setClient($client);
  }

  set_cookie('seatbooking_client', $clientId . ':' . $clientToken, $cookieExpire);
}


function fillClientInfo(&$client)
{
  $client['lastSeenTimestamp'] = format_timestamp(time());
  $client['ip'] = get_client_ip_address();
  $client['userAgent'] = array_value($_SERVER, 'HTTP_USER_AGENT');
  $hashItems = [];
  $hashItems[] = $client['ip'];
  $hashItems[] = $client['userAgent'];
  $hashItems[] = array_value($_SERVER, 'HTTP_ACCEPT_LANGUAGE');
  $client['hash'] = substr(md5(implode('.', $hashItems)), 0, 8);
}


function decodeClient(&$client)
# Convert all timestamp fields from strings to timestamps
{
  if ($client != null)
  {
    $client['insertTimestamp'] = date_time_to_timestamp($client['insertTimestamp']);
    $client['editTimestamp'] = date_time_to_timestamp($client['editTimestamp']);
    $client['lastSeenTimestamp'] = date_time_to_timestamp($client['lastSeenTimestamp']);
    $client['userAgent_os'] = get_os_from_user_agent($client['userAgent']);
  }
}


function getFormToken()
{
  return substr(getClientValue('token'), -6);
}

function writeFormToken()
{
  echo html_input('hidden', 'formToken', getFormToken());
}

function verifyFormTokenOrExit()
{
  if (get_param_value('formToken') != getFormToken())
  {
    echo 'showErrorMsg("Die Sitzung ist abgelaufen.");';
    echo 'location.reload();';
    exit();
  }
}


function handleMakeClientPersistentAction()
{
  db()->try_update_by_id('client', getClientValue('id'), ['persistent' => 1]);
  echo 'location.reload();';
}


function renderClientList()
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }
  writeMainHtmlBeforeContent('Geräte verwalten');

  echo html_open('div', ['class' => 'content']);

  $items = db()->query_rows('SELECT * FROM client ORDER BY lastSeenTimestamp DESC LIMIT 100');

  foreach ($items as &$client)
  {
    decodeClient($client);
  }

  $fields = [];

  $field = newIdField();
  $fields[] = $field;

  $field = newTextField('hash', 'Kennung');
  $fields[] = $field;

  $field = newTextField('persistent', 'Persistent');
  $fields[] = $field;

  $field = newTextField('userName', 'Benutzername');
  $fields[] = $field;

  $field = newTextField('userGroup', 'Benutzergruppe');
  $fields[] = $field;

  $field = newTextField('userAgent_os', 'Betriebssystem');
  $fields[] = $field;

  $field = newTimestampField('lastSeenTimestamp', 'Zuletzt online');
  $field['editable'] = false;
  $fields[] = $field;

  renderItemTable($items, $fields);

  echo html_close('div');
}
