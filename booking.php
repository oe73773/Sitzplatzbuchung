<?php

function decodeBooking(&$booking)
# Convert all timestamp fields from strings to timestamps
{
  if ($booking != null)
  {
    $booking['insertTimestamp'] = date_time_to_timestamp($booking['insertTimestamp']);
    $booking['cancelTimestamp'] = date_time_to_timestamp($booking['cancelTimestamp']);
  }
}


function tryGetBookingById($itemId)
{
  if ($itemId == null)
    return;
  $item = db()->try_query_row_by_id('booking', $itemId);
  decodeBooking($item);
  return $item;
}


function getCurrentBookingByEventForClient()
{
  $bookings = db()->query_rows('SELECT * FROM booking WHERE insertClientId = ? AND insertedAsAdmin IS NULL ORDER BY id DESC LIMIT 100', [getClientValue('id')]);
  $bookings = array_reverse($bookings);
  $result = [];
  foreach ($bookings as $booking)
  {
    $result[$booking['eventId']] = $booking;
  }
  return $result;
}


function getActiveBookingForEventForClient($eventId)
{
  return db()->try_query_row('SELECT * FROM booking WHERE insertClientId = ? AND insertedAsAdmin IS NULL AND eventId = ? AND cancelTimestamp IS NULL', [getClientValue('id'), $eventId]);
}


function getAdminBookings()
{
  $items = db()->query_rows('SELECT * FROM booking ORDER BY id DESC LIMIT 100');

  foreach ($items as &$item)
  {
    decodeBooking($item);
  }

  return $items;
}


function handleSaveBookingAction()
# Parameters:
# - eventId
# - surname1..surname6
# - lastname1..lastname6
{
  $eventId = get_param_value('eventId');
  $event = tryGetEventById($eventId);
  if ($event == null)
  {
    echo 'showErrorMsg("Datensatz existiert nicht.");';
    echo 'location.reload();';
    return;
  }
  if (!isBookingOpen($event))
  {
    echo 'showErrorMsg("Der Buchungszeitraum ist abgelaufen.");';
    echo 'location.reload();';
    return;
  }

  $booking = getActiveBookingForEventForClient($eventId);
  if ($booking != null)
  {
    echo 'showErrorMsg("Die Veranstaltung wurde bereits von diesem Gerät gebucht.");';
    echo 'location.reload();';
    return;
  }

  $persons = [];
  for ($i = 1; $i <= 6; $i++)
  {
    $surname = trim(get_param_value('surname' . $i));
    $lastname = trim(get_param_value('lastname' . $i));
    if ($surname == '' && $lastname == '')
      continue;
    if (strlen($surname) == '')
    {
      echo 'showErrorMsg("Bitte den Vornamen aller Personen angeben.");';
      return;
    }
    if (strlen($lastname) == '')
    {
      echo 'showErrorMsg("Bitte den Nachnamen aller Personen angeben.");';
      return;
    }
    if (strlen($surname) < 3)
    {
      echo 'showErrorMsg("Vorname ist zu kurz.");';
      return;
    }
    if (strlen($lastname) < 3)
    {
      echo 'showErrorMsg("Nachname ist zu kurz.");';
      return;
    }
    if (contains($surname, ',') or contains($lastname, ','))
    {
      echo 'showErrorMsg("Der Name darf kein Komma enthalten.");';
      return;
    }
    if (contains($surname, ';') or contains($lastname, ';'))
    {
      echo 'showErrorMsg("Der Name darf kein Semikolon enthalten.");';
      return;
    }
    $person = $surname . ',' . $lastname;
    if (in_array($person, $persons))
    {
      echo 'showErrorMsg("Ein Name wurde doppelt eingegeben.");';
      return;
    }
    $persons[] = $person;
  }

  if (count($persons) == 0)
  {
    echo 'showErrorMsg("Bitte einen Namen eingeben.");';
    return;
  }

  $phoneNumber = trim(get_param_value('phoneNumber'));
  if ($phoneNumber == '')
  {
    echo 'showErrorMsg("Bitte eine Telefonnummer eingeben.");';
    return;
  }
  if (strlen($phoneNumber) < 7)
  {
    echo 'showErrorMsg("Telefonnummer ist zu kurz.");';
    return;
  }

  $listOfPersons = implode(';', $persons);

  # update client row
  $clientValues = [];
  $clientValues['lastListOfPersons'] = $listOfPersons;
  $clientValues['lastPhoneNumber'] = $phoneNumber;
  $clientValues['persistent'] = 1;
  db()->try_update_by_id('client', getClientValue('id'), $clientValues);

  # check free seat count
  $freeSeatCount = calculateFreeSeatCount($event, count($persons));
  if ($freeSeatCount == -1)
  {
    $freeSeatCountWithoutNew = calculateFreeSeatCount($event);
    if ($freeSeatCountWithoutNew > 0 && count($persons) > 1)
      echo 'showErrorMsg("Es sind nicht mehr genügend Plätze frei. Bitte weniger Personen eingeben.");';
    else
      echo 'showErrorMsg("Es sind keine Plätze mehr frei.");';
    echo 'location.reload();';
    return;
  }

  # insert booking row
  $booking = [];
  $booking['eventId'] = $event['id'];
  $booking['listOfPersons'] = $listOfPersons;
  $booking['personCount'] = count($persons);
  $booking['phoneNumber'] = $phoneNumber;
  $booking['insertTimestamp'] = format_timestamp(time());
  $booking['insertClientId'] = getClientValue('id');
  $bookingId = db()->insert('booking', $booking);

  echo 'location.reload();';
}


function handleCancelBookingAction()
# Parameters:
# - eventId
{
  $eventId = get_param_value('eventId');
  $event = tryGetEventById($eventId);
  if ($event == null)
  {
    echo 'showErrorMsg("Datensatz existiert nicht.");';
    echo 'location.reload();';
    return;
  }

  $booking = getActiveBookingForEventForClient($eventId);
  if ($booking == null)
  {
    echo 'showErrorMsg("Keine Buchung gefunden.");';
    echo 'location.reload();';
    return;
  }

  if (!isBookingOpen($event))
  {
    echo 'showErrorMsg("Der Buchungszeitraum ist abgelaufen.");';
    echo 'location.reload();';
    return;
  }

  # update booking row
  $values = [];
  $values['cancelTimestamp'] = format_timestamp(time());
  $values['cancelClientId'] = getClientValue('id');
  db()->update_by_id('booking', $booking['id'], $values);

  echo 'location.reload();';
}


function renderVisitorsSheet()
{
  $eventId = get_param_value('eventId');
  if ($eventId == null)
    renderVisitorsSheetList();
  else
    renderVisitorsSheetDetails($eventId);
}


function renderVisitorsSheetList()
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }
  writeMainHtmlBeforeContent('Anwesenheitslisten');

  echo html_open('div', ['class' => 'content visitorList']);

  $fields = [];

  renderItemTable(getVisitorListEvents(), getEventFieldsForVisitorList());

  echo html_close('div');
}


function renderVisitorsSheetDetails($eventId)
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }

  $event = tryGetEventById($eventId);
  if ($event == null)
  {
    renderNotFoundError();
    return;
  }

  $titleAndDate = $event['titleAndDate'];

  writeMainHtmlBeforeContent('Anwesenheitsliste ' . $titleAndDate);

  echo html_open('div', ['class' => 'content visitorList']);

  $rows = renderVisitorsSheetDetails_getRealRows($eventId);
  $visitorCount = count($rows);
  if ($visitorCount > 0)
    renderVisitorsSheetDetails_addNumberingAndEmptyRows($rows);

  $fields = [];

  $field = newIdField();
  $field['isTitle'] = false;
  $fields[] = $field;

  $field = newTextField('name', 'Name');
  $fields[] = $field;

  $field = newTextField('phoneNumber', 'Telefon');
  $fields[] = $field;

  $field = newTextField('bookingInfo', 'Buchung');
  $fields[] = $field;

  $field = newTextField('empty', 'Anwesend');
  $fields[] = $field;

  echo html_button('Drucken', ['onclick' => 'window.print();']);

  echo html_open('div', ['class' => 'subTitle']);
  echo $visitorCount;
  echo ' Teilnehmer gebucht';
  echo ', Buchung ';
  if (time() > $event['bookingClosingTimestamp'])
    echo 'abgeschlossen';
  else
  {
    echo 'noch offen bis ';
    echo formatTimestampLocalLong($event['bookingClosingTimestamp'], 'minute', false);
  }
  echo html_close('div');

  echo html_open('div', ['class' => 'subTitle currentDate']);
  echo 'Stand: ';
  echo formatTimestampLocalLong(time(), 'minute');
  echo html_close('div');

  renderItemTable($rows, $fields);

  echo html_close('div');
}


function renderVisitorsSheetDetails_getRealRows($eventId)
{
  $bookings = db()->query_rows('SELECT * FROM booking WHERE eventId = ? AND cancelTimestamp IS NULL', [$eventId]);

  $rows = [];
  foreach($bookings as $booking)
  {
    $persons = explode(';', $booking['listOfPersons']);
    if (count($persons) == 1)
      $bookingInfo = count($persons) . ' Person';
    else
      $bookingInfo = count($persons) . ' Personen, #' . $booking['id'];
    foreach($persons as $personStr)
    {
      $person = explode(',', $personStr);
      $surname = array_value($person, 0);
      $lastname = array_value($person, 1);

      $row = [];
      $row['bookingInfo'] =  $bookingInfo;
      $row['phoneNumber'] = $booking['phoneNumber'];
      $row['name'] = $lastname . ', ' . $surname;
      $row['empty'] = '';
      $key = implode(' ', [$lastname, $surname, $booking['id']]);
      $rows[$key] = $row;
    }
  }

  ksort($rows);

  return $rows;
}


function renderVisitorsSheetDetails_addNumberingAndEmptyRows(&$rows)
{
  $i = 1;
  foreach($rows as &$row)
  {
    $row['id'] = $i;
    $i++;
  }
  unset($row);

  $emptyRowCount = 15;
  for($j = 0; $j < $emptyRowCount; $j++)
  {
    $row = [];
    $row['id'] = $i;
    $row['bookingInfo'] = null;
    $row['phoneNumber'] = '';
    $row['name'] = '';
    $row['empty'] = '';
    $row['class'] = 'empty';
    $rows[] = $row;
    $i++;
  }
}


function renderBookings()
{
  if (!isClientAdmin())
  {
    renderForbiddenError();
    return;
  }

  $itemId = get_param_value('itemId');
  if ($itemId == null)
    renderBookingList();
  else
    renderBookingDetails($itemId);
}


function renderBookingList()
{
  writeMainHtmlBeforeContent('Buchungen verwalten');

  echo html_open('div', ['class' => 'content']);

  renderItemTable(getAdminBookings(), getBookingFields());

  echo html_close('div');
}


function renderBookingDetails($itemId)
{
  $item = null;
  $title = null;
  $creatingItem = $itemId == 'new';
  if ($creatingItem)
  {
    $title = 'Neue Buchung';
  }
  else
  {
    $item = tryGetBookingById($itemId);
    if ($item == null)
    {
      renderNotFoundError();
      return;
    }
    $title = 'Buchung ' . $item['id'];
  }

  writeMainHtmlBeforeContent($title);

  echo html_open('div', ['class' => 'content']);

  renderItemDetails($creatingItem, $item, getBookingFields());

  echo html_close('div');
}


function getBookingFields()
{
  $fields = [];

  $field = newIdField();
  $fields[] = $field;

  $field = newTextField('eventId', 'Veranstaltung');
  $fields[] = $field;

  $field = newTextField('listOfPersons', 'Personen');
  $fields[] = $field;

  $field = newTextField('phoneNumber', 'Telefon');
  $field['visibleInList'] = false;
  $fields[] = $field;

  $field = newTimestampField('insertTimestamp', 'Gebucht am');
  $fields[] = $field;

  $field = newTextField('insertClientId', 'Gebucht durch');
  $fields[] = $field;

  $field = newTimestampField('cancelTimestamp', 'Storniert am');
  $fields[] = $field;

  $field = newIntegerField('cancelClientId', 'Storniert durch');
  $field['visibleInList'] = false;
  $fields[] = $field;

  return $fields;
}
