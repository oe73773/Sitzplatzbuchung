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


function renderAdminlogList()
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }
  writeMainHtmlBeforeContent('Admin-Protokoll');

  echo html_open('div', ['class' => 'content']);

  $items = db()->query_rows('SELECT * FROM adminlog ORDER BY id DESC LIMIT 100');

  foreach ($items as &$item)
  {
    decodeAdminlog($item);
  }

  $fields = [];

  $field = newIdField();
  $fields[] = $field;

  $field = newTimestampField('insertTimestamp', 'Datum');
  $fields[] = $field;

  $field = newIntegerField('clientId', 'Benutzer');
  $fields[] = $field;

  $field = newTextField('itemType', 'Datensatz-Typ');
  $fields[] = $field;

  $field = newIntegerField('itemId', 'Datensatz-Nr.');
  $fields[] = $field;

  $field = newTextField('action', 'Aktion');
  $fields[] = $field;

  renderItemTable($items, $fields);

  echo html_close('div');
}
