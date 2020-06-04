<?php

function addAdminlogEntry($itemType, $itemId, $action, $newData= null)
{
  $values = [];
  $values['clientId'] = getClientValue('id');
  $values['insertTimestamp'] = format_timestamp(time());
  $values['itemType'] = $itemType;
  $values['itemId'] = $itemId;
  $values['action'] = $action;
  unset($newData['insertTimestamp']);
  unset($newData['editClientId']);
  unset($newData['editTimestamp']);
  $values['newData'] = json_encode($newData);
  db()->insert('adminlog', $values);
}


function decodeAdminlog(&$item)
# Convert all timestamp fields from strings to timestamps
{
  if ($item != null)
  {
    $item['insertTimestamp'] = date_time_to_timestamp($item['insertTimestamp']);
  }
}


function tryGetAdminlogById($itemId)
{
  if ($itemId == null)
    return;
  $item = db()->try_query_row_by_id('adminlog', $itemId);
  decodeAdminlog($item);
  return $item;
}


function getAdminlogs()
{
  $items = db()->query_rows('SELECT * FROM adminlog ORDER BY id DESC LIMIT 100');

  foreach ($items as &$item)
  {
    decodeAdminlog($item);
  }

  return $items;
}


function renderAdminlogs()
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }

  $itemId = get_param_value('itemId');
  if ($itemId == null)
    renderAdminlogList();
  else
    renderAdminlogDetails($itemId);
}


function renderAdminlogList()
{
  writeMainHtmlBeforeContent('Admin-Protokoll');

  echo html_open('div', ['class' => 'content']);

  renderItemTable(getAdminlogs(), getAdminlogFields());

  echo html_close('div');
}


function renderAdminlogDetails($itemId)
{
  $item = tryGetAdminlogById($itemId);
  if ($item == null)
  {
    renderNotFoundError();
    return;
  }
  $title = 'Admin-Protokoll-Eintrag ' . $item['id'];

  writeMainHtmlBeforeContent($title);

  echo html_open('div', ['class' => 'content']);

  renderItemDetails(false, $item, getAdminlogFields());

  echo html_close('div');
}


function getAdminlogFields()
{
  $fields = [];

  $field = newIdField();
  $fields[] = $field;

  $field = newTimestampField('insertTimestamp', 'Datum');
  $fields[] = $field;

  $field = newIntegerField('clientId', 'Gerät');
  $fields[] = $field;

  $field = newTextField('itemType', 'Datensatz-Typ');
  $fields[] = $field;

  $field = newIntegerField('itemId', 'Datensatz-Nr.');
  $fields[] = $field;

  $field = newTextField('action', 'Aktion');
  $fields[] = $field;

  return $fields;
}
