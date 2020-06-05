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


function tryGetClientById($itemId)
{
  if ($itemId == null)
    return;
  $item = db()->try_query_row_by_id('client', $itemId);
  decodeClient($item);
  return $item;
}


function getClients()
{
  $items = db()->query_rows('SELECT * FROM client ORDER BY lastSeenTimestamp DESC LIMIT 100');

  foreach ($items as &$item)
  {
    decodeClient($item);
  }

  return $items;
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


function renderClients()
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }

  $itemId = get_param_value('itemId');
  if ($itemId == null)
    renderClientList();
  else
    renderClientDetails($itemId);
}


function renderClientList()
{
  writeMainHtmlBeforeContent('Geräte verwalten');

  echo html_open('div', ['class' => 'content']);

  renderItemTable(getClients(), getClientFields());

  echo html_close('div');
}


function renderClientDetails($itemId)
{
  $item = tryGetClientById($itemId);
  if ($item == null)
  {
    renderNotFoundError();
    return;
  }
  $title = 'Gerät ' . $item['id'];

  writeMainHtmlBeforeContent($title);

  echo html_open('div', ['class' => 'content']);

  renderItemDetails(false, $item, getClientFields(), [], 'saveClient');

  echo html_close('div');
}


function handleSaveClientAction()
{
  if (!isClientAdmin())
  {
    echo 'showErrorMsg("Dieses Gerät hat keine Berechtigung für die angeforderte Aktion.");';
    echo 'location.reload();';
    return;
  }

  $itemId = get_param_value('itemId');
  $item = tryGetClientById($itemId);
  if ($item == null)
  {
    echo 'showErrorMsg("Datensatz existiert nicht.");';
    echo 'location.reload();';
    return;
  }
  $values = getClientSaveValues();
  if ($values == null)
    return;
  db()->update_by_id('client', $itemId, $values);
  addAdminlogEntry('client', $itemId, 'edit', $values);
  echo js_redirect('?p=clients&itemId=' . $itemId);
}


function getClientSaveValues()
{
  $values = getSaveValues(getClientFields());
  if ($values == null)
    return;
  $values['editTimestamp'] = format_timestamp(time());
  $values['editClientId'] = getClientValue('id');
  $values['persistent'] = 1;
  return $values;
}


function getClientFields()
{
  $fields = [];

  $field = newIdField();
  $fields[] = $field;

  $field = newTextField('hash', 'Kennung');
  $field['editable'] = false;
  $fields[] = $field;

  $field = newBooleanField('persistent', 'Dauerhaft');
  $field['editable'] = false;
  $fields[] = $field;

  $field = newTextField('userName', 'Benutzername');
  $field['mandatory'] = true;
  $fields[] = $field;

  $field = newTextField('deviceName', 'Gerätebezeichnung');
  $fields[] = $field;

  $field = newBooleanField('userGroup', 'Administratorrechte');
  $fields[] = $field;

  $field = newTextField('userAgent_os', 'Betriebssystem');
  $field['editable'] = false;
  $fields[] = $field;

  $field = newTextField('userAgent', 'User Agent');
  $field['editable'] = false;
  $field['visibleInList'] = false;
  $fields[] = $field;

  $field = newTimestampField('lastSeenTimestamp', 'Zuletzt online');
  $field['editable'] = false;
  $fields[] = $field;

  $field = newTextField('lastListOfPersons', 'Zuletzt gebucht');
  $field['editable'] = false;
  $fields[] = $field;

  $field = newTextField('lastPhoneNumber', 'Telefon');
  $field['editable'] = false;
  $field['visibleInList'] = false;
  $fields[] = $field;

  $field = newTimestampField('editTimestamp', 'Bearbeitet am');
  $field['editable'] = false;
  $field['visibleInList'] = false;
  $fields[] = $field;

  $field = newIntegerField('editClientId', 'Bearbeitet durch');
  $field['editable'] = false;
  $field['visibleInList'] = false;
  $fields[] = $field;

  return $fields;
}

