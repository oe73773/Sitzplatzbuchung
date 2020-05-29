<?php

function decodeEvent(&$event, $withVisitorCount = false, $withFreeSeatCount = false)
# Convert all timestamp fields from strings to timestamps
{
  if ($event != null)
  {
    $event['releaseTimestamp'] = date_time_to_timestamp($event['releaseTimestamp']);
    $event['startTimestamp'] = date_time_to_timestamp($event['startTimestamp']);
    $event['bookingOpeningTimestamp'] = date_time_to_timestamp($event['bookingOpeningTimestamp']);
    $event['bookingClosingTimestamp'] = date_time_to_timestamp($event['bookingClosingTimestamp']);
    $event['insertTimestamp'] = date_time_to_timestamp($event['insertTimestamp']);
    $event['editTimestamp'] = date_time_to_timestamp($event['editTimestamp']);

    if ($withVisitorCount)
      $event['visitorCount'] = getEventVisitorCount($event['id']);
    if ($withFreeSeatCount)
      $event['freeSeatCount'] = calculateFreeSeatCount($event);
  }
}


function tryGetEventById($eventId, $withVisitorCount = false, $withFreeSeatCount = false)
{
  if ($eventId == null)
    return;
  $event = db()->try_query_row_by_id('event', $eventId);
  decodeEvent($event, $withVisitorCount, $withFreeSeatCount);
  return $event;
}


function getMainPageEvents($withVisitorCount = false, $withFreeSeatCount = false)
{
  $now = format_timestamp(time());
  $nowWithOffset = format_timestamp(time() - 60 * 30); # 30 minutes overrun after start of event
  $events = db()->query_rows('SELECT * FROM event WHERE releaseTimestamp < ? AND startTimestamp > ? ORDER BY startTimestamp LIMIT 50', [$now, $nowWithOffset]);
  foreach ($events as &$event)
  {
    decodeEvent($event, $withVisitorCount, $withFreeSeatCount);
  }
  return $events;
}


function getAdminEvents()
{
  $nowWithOffset = format_timestamp(time() - 60 * 60 * 24 * 15);
  $events = db()->query_rows('SELECT * FROM event WHERE startTimestamp > ? ORDER BY startTimestamp LIMIT 100', [$nowWithOffset]);
  foreach ($events as &$event)
  {
    decodeEvent($event, true, true);
  }
  return $events;
}


function getVisitorListEvents()
{
  $now = format_timestamp(time());
  $nowWithOffset = format_timestamp(time() - 60 * 60 * 24 * 30);
  $events = db()->query_rows('SELECT * FROM event WHERE bookingOpeningTimestamp < ? AND startTimestamp > ? ORDER BY startTimestamp LIMIT 100', [$now, $nowWithOffset]);
  foreach ($events as &$event)
  {
    decodeEvent($event, true, true);
  }
  return $events;
}


function isBookingOpen($event)
{
  return time() > $event['bookingOpeningTimestamp'] && time() < $event['bookingClosingTimestamp'];
}


function getEventVisitorCount($eventId)
{
  $rows = db()->query_rows('SELECT personCount, COUNT(*) FROM booking WHERE eventId = ? AND cancelTimestamp IS NULL GROUP BY personCount', [$eventId]);
  $result = 0;
  foreach ($rows as $row)
  {
    $result += intval($row['personCount']) * intval($row['COUNT(*)']);
  }
  return $result;
}


function calculateFreeSeatCount($event, $additionalPersonCount = null, $debug = false)
{
  $eventId = $event['id'];

  $rows = db()->query_rows('SELECT personCount, COUNT(*) as count FROM booking WHERE eventId = ? AND cancelTimestamp IS NULL GROUP BY personCount ORDER BY personCount DESC', [$eventId]);
  if ($additionalPersonCount != null)
    $rows[] = ['personCount' => $additionalPersonCount, 'count' => 1];

  return calculateFreeSeatsInner($event, $rows, $debug);
}


function calculateFreeSeatsInner($event, $rows, $debug = false)
# Returns the number of free seats or -1 if the capacity/limit is exceeded
{
  $fiveSeatsFree = intval($event['capacity5Seats']);
  $sixSeatsFree = intval($event['capacity6Seats']);
  $visitorLimit = intval($event['visitorLimit']);
  $fiveSeatsWith1Person = 0;
  $sixSeatsWith1Person = 0;
  $sixSeatsWith2Persons = 0;
  $visitorSum = 0;

  if ($debug)
  {
    echo 'Teilnehmer-Limit: ' . $visitorLimit . '<br>';
    echo '5er-Stuhlreihen: ' . $fiveSeatsFree . '<br>';
    echo '6er-Stuhlreihen: ' . $sixSeatsFree . '<br>';
    echo 'Buchungen: ' . count($rows) . '<br>';
    echo '<br>';
  }

  foreach ($rows as $row)
  {
    $personCount = intval($row['personCount']);
    $count = intval($row['count']);
    $visitorSum += $personCount * $count;

    if ($debug)
    {
      echo '<b>';
      if ($personCount == 1)
        echo 'Anzahl Einzelpersonen: ';
      else
      {
        echo 'Anzahl ';
        echo $personCount;
        echo 'er-Gruppen: ';
      }
      echo $count;
      echo '</b>';
      echo '<br>';
    }

    if ($personCount == 6)
    {
      if ($count > 0)
      {
        $dec = min($count, $sixSeatsFree);
        $sixSeatsFree -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        return -1;
      }
    }
    else if ($personCount == 5 || $personCount == 4 || $personCount == 3)
    {
      if ($count > 0)
      {
        $dec = min($count, $fiveSeatsFree);
        $fiveSeatsFree -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, $sixSeatsFree);
        $sixSeatsFree -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        return -1;
      }
    }
    else if ($personCount == 2)
    {
      if ($count > 0)
      {
        $dec = min($count, $sixSeatsWith1Person);
        $sixSeatsWith1Person -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, $sixSeatsFree);
        $sixSeatsFree -= $dec;
        $sixSeatsWith2Persons += $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, $fiveSeatsFree);
        $fiveSeatsFree -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, floor($fiveSeatsWith1Person / 2));
        $fiveSeatsWith1Person -= $dec * 2;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, floor($sixSeatsWith2Persons / 2));
        $sixSeatsWith2Persons -= $dec * 2;
        $count -= $dec;
      }
      if ($count > 0)
      {
        return -1;
      }
    }
    else if ($personCount == 1)
    {
      if ($count > 0)
      {
        $dec = min($count, $sixSeatsWith2Persons);
        $sixSeatsWith2Persons -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, $fiveSeatsFree);
        $fiveSeatsFree -= $dec;
        $fiveSeatsWith1Person += $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, $sixSeatsFree);
        $sixSeatsFree -= $dec;
        $sixSeatsWith1Person += $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, $fiveSeatsWith1Person);
        $fiveSeatsWith1Person -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        $dec = min($count, $sixSeatsWith1Person);
        $sixSeatsWith1Person -= $dec;
        $count -= $dec;
      }
      if ($count > 0)
      {
        return -1;
      }
    }

    if ($debug)
    {
      if ($fiveSeatsFree > 0)
        echo 'freie 5er-Stuhlreihen: ' . $fiveSeatsFree . '<br>';
      if ($sixSeatsFree > 0)
        echo 'freie 6er-Stuhlreihen: ' . $sixSeatsFree . '<br>';
      if ($fiveSeatsWith1Person > 0)
        echo '5er-Stuhlreihen mit 1 Person: ' . $fiveSeatsWith1Person . '<br>';
      if ($sixSeatsWith1Person > 0)
        echo '6er-Stuhlreihen mit 1 Person: ' . $sixSeatsWith1Person . '<br>';
      if ($sixSeatsWith2Persons > 0)
        echo '6er-Stuhlreihen mit 2 Personen: ' . $sixSeatsWith2Persons . '<br>';
      echo '<br>';
    }
  }

  if ($debug)
    echo 'Anzahl Teilnehmer: ' . $visitorSum . '<br>';

  $freeSeats = $fiveSeatsFree * 5 + $sixSeatsFree * 6 + $fiveSeatsWith1Person + $sixSeatsWith1Person * 2 + $sixSeatsWith2Persons;

  if ($visitorLimit > 0)
  {
    if ($visitorSum > $visitorLimit)
      return -1;
    $freeSeats = min($freeSeats, $visitorLimit - $visitorSum);
  }

  return $freeSeats;
}


function renderEvents()
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }

  $eventId = get_param_value('eventId');
  if ($eventId == null)
    renderEventList();
  else
    renderEventDetails($eventId);
}


function renderEventList()
{
  writeMainHtmlBeforeContent('Veranstaltungen verwalten');

  echo html_open('div', ['class' => 'content']);

  renderItemTable(getAdminEvents(), getEventFields(), getEventActions(), 'eventId');

  echo html_close('div');
}


function renderEventDetails($eventId)
{
  $event = null;
  $title = null;
  $creatingItem = $eventId == 'new';
  if ($creatingItem)
  {
    $title = 'Neue Veranstaltung';
    $originalEventId = get_param_value('originalEventId');
    if ($originalEventId != null)
    {
      $event = tryGetEventById($originalEventId);
      if ($event == null)
      {
        renderNotFoundError();
        return;
      }
      modifyEventOnClone($event);
    }
  }
  else
  {
    $event = tryGetEventById($eventId, true, true);
    if ($event == null)
    {
      renderNotFoundError();
      return;
    }
    $title = $event['title'] . ' am '. formatTimestampLocalLong($event['startTimestamp'], 'minute');
  }

  writeMainHtmlBeforeContent($title);

  echo html_open('div', ['class' => 'content']);

  renderItemDetails($creatingItem, $event, getEventFields(), getEventActions(), 'eventId', 'saveEvent');

  echo html_close('div');
}


function modifyEventOnClone(&$event)
# Fills in the suggested values
{
  $oneWeek = 60 * 60 * 24 * 7;
  $event['releaseTimestamp'] = $event['releaseTimestamp'] + $oneWeek;
  $event['startTimestamp'] = $event['startTimestamp'] + $oneWeek;
  $event['bookingOpeningTimestamp'] = $event['bookingOpeningTimestamp'] + $oneWeek;
  $event['bookingClosingTimestamp'] = $event['bookingClosingTimestamp'] + $oneWeek;
  $event['canceled'] = null;
}


function handleSaveEventAction()
{
  if (!isClientAdmin())
  {
    echo 'showErrorMsg("Dieses Gerät hat keine Berechtigung für die angeforderte Aktion.");';
    echo 'location.reload();';
    return;
  }

  $itemId = get_param_value('eventId');
  if ($itemId != null)
  {
    $item = tryGetEventById($itemId);
    if ($item == null)
    {
      echo 'showErrorMsg("Datensatz existiert nicht.");';
      echo 'location.reload();';
      return;
    }
  }
  $values = getEventSaveValues($itemId == null);
  if ($values == null)
    return;
  if ($itemId == null)
  {
    $itemId = db()->insert('event', $values);
    addAdminlogEntry('event', $itemId, 'create', $values);
  }
  else
  {
    db()->update_by_id('event', $itemId, $values);
    addAdminlogEntry('event', $itemId, 'edit', $values);
  }
  echo 'location.href = "?p=events&eventId=';
  echo $itemId;
  echo '";';
}


function getEventSaveValues($creatingNewItem)
{
  $values = getSaveValues(getEventFields());
  if ($values == null)
    return;
  $values['editTimestamp'] = format_timestamp(time());
  $values['editClientId'] = getClientValue('id');
  if ($creatingNewItem)
    $values['insertTimestamp'] = format_timestamp(time());
  return $values;
}


function getEventFields()
{
  $fields = [];

  $field = newIdField();
  $fields[] = $field;

  $field = newTextField('title', 'Titel');
  $field['mandatory'] = true;
  $field['isTitle'] = true;
  $fields[] = $field;

  $field = newTimestampField('startTimestamp', 'Beginn');
  $field['mandatory'] = true;
  $fields[] = $field;

  $field = newTimestampField('releaseTimestamp', 'Veröffentlichung');
  $fields[] = $field;

  $field = newTimestampField('bookingOpeningTimestamp', 'Buchung ab');
  $field['mandatory'] = true;
  $fields[] = $field;

  $field = newTimestampField('bookingClosingTimestamp', 'Buchung bis');
  $field['mandatory'] = true;
  $fields[] = $field;

  $field = newIntegerField('visitorLimit', 'Teilnehmerlimit');
  $field['visibleInList'] = false;
  $fields[] = $field;

  $field = newIntegerField('capacity5Seats', 'Anzahl 5er-Stuhlreihen');
  $field['visibleInList'] = false;
  $field['mandatory'] = true;
  $fields[] = $field;

  $field = newIntegerField('capacity6Seats', 'Anzahl 6er-Stuhlreihen');
  $field['visibleInList'] = false;
  $field['mandatory'] = true;
  $fields[] = $field;

  $field = newBooleanField('canceled', 'Abgesagt');
  $fields[] = $field;

  $field = newTextAreaField('notice', 'Hinweise');
  $field['visibleInList'] = false;
  $field['allowHtml'] = true;
  $fields[] = $field;

  $field = newTextAreaField('remark', 'Bemerkung');
  $field['visibleInList'] = false;
  $fields[] = $field;

  $field = newIntegerField('visitorCount', 'Teilnehmer');
  $field['editable'] = false;
  $fields[] = $field;

  $field = newIntegerField('freeSeatCount', 'Freie Sitzplätze');
  $field['editable'] = false;
  $fields[] = $field;

  $field = newTimestampField('insertTimestamp', 'Erstellt am');
  $field['editable'] = false;
  $field['visibleInList'] = false;
  $fields[] = $field;

  $field = newTimestampField('editTimestamp', 'Bearbeitet am');
  $field['editable'] = false;
  $field['visibleInList'] = false;
  $fields[] = $field;

  return $fields;
}


function getEventActions()
{
  $actions = [];

  $action = newLinkPerItemAction('?p=events', 'Anzeigen', 'eventId');
  $action['visibleInDetails'] = false;
  $actions[] = $action;

  $action = newLinkAction('?p=events&eventId=new', 'Neue Veranstaltung');
  $action['cssClass'] = 'saveButton';
  $action['visibleInDetails'] = false;
  $actions[] = $action;

  $action = newLinkPerItemAction('?p=events&eventId=new', 'Neue Folge-Veranstaltung', 'originalEventId');
  $action['cssClass'] = 'saveButton';
  $action['visibleInList'] = false;
  $actions[] = $action;

  return $actions;
}
