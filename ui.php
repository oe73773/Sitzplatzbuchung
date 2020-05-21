<?php

function writeMainHtmlBeforeContent($pageTitle = null)
{
  echo '<!DOCTYPE html>';
  echo html_node('meta', null, ['charset' => 'utf-8']);
  echo html_node('meta', null, ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);

  $title = getConfigValue('instanceTitle', 'instanceTitle');
  if ($pageTitle != null)
    $title = $pageTitle . ' – ' . $title;
  echo html_node('title', $title);

  $faviconPath = getConfigValue('faviconPath');
  if ($faviconPath != null)
    echo html_node('link', null, ['rel' => 'icon', 'href' => $faviconPath]);

  write_css_include_tag('public/stylesheet.css');
  write_script_include_tag('public/common.js');

  # header
  echo html_open('div', ['class' => 'headerOuter']);
  echo html_open('div', ['class' => 'header']);
  $classes = 'title';
  if (getConfigValue('isProductionInstance') !== true)
    $classes .= ' nonProdInstance';
  $titleSpan = html_node('span', getConfigValue('instanceHeadline', 'instanceHeadline'), ['class' => $classes]);
  echo html_a('.', $titleSpan, ['title' => 'Zur Startseite']);
  echo getConfigValue('textAfterTitle');
  echo html_node('span', 'Live', ['id' => 'autoReloadIndicator']);
  echo html_close('div');
  echo html_close('div');

  # navBar
  echo html_open('div', ['class' => 'navBar']);
  if (isClientAdmin() || $pageTitle != null)
    echo html_a('.', 'Startseite');
  if (isClientAdmin())
    echo html_a('?p=admin', 'Administration');
  echo html_close('div');

  # pageTitle
  if ($pageTitle != null)
  {
    echo html_open('div', ['class' => 'pageTitleOuter']);
    echo html_node('div', $pageTitle, ['class' => 'pageTitle']);
    echo html_close('div');
  }
}


function writeMainHtmlAfterContent()
{
  $footerText = getConfigValue('footerText');
  if ($footerText != null)
    echo html_node('footer', $footerText);
}


function renderPageErrorBox($msg)
{
  echo html_open('div', ['class' => 'content']);
  echo html_open('div');
  echo html_node('div', 'Fehler:<br>' . $msg, ['class' => 'textBlock errorBox']);
  echo html_close('div');
  echo html_close('div');
}


function renderMainPage()
{
  $events = getMainPageEvents(true, true);
  $currentBookingByEvent = getCurrentBookingByEventForClient();

  writeMainHtmlBeforeContent();

  echo html_open('div', ['class' => 'content mainPage']);

  renderMainPageNotice();

  if (count($events) == 0)
  {
    echo html_open('div');
    echo html_open('div', ['class' => 'textBlock framedBox']);
    echo html_node('span', 'Veranstaltungen', ['class' => 'framedBoxTitle']);
    echo 'Aktuell steht keine Veranstaltung zur Buchung bereit.';
    echo html_close('div');
    echo html_close('div');
  }
  else
  {
    foreach ($events as $event)
    {
      renderMainPageEvent($event, array_value($currentBookingByEvent, $event['id']));
    }
  }

  echo html_close('div');

  # Auto reload
  echo html_input('hidden', null, calculateAutoReloadHash($events), ['id' => 'autoReloadHash']);
  echo html_node('script', 'enableAutoReload()');
}


function calculateAutoReloadHash($events)
{
  $items = [];
  foreach ($events as $event)
  {
    $items[] = $event['id'];
    $items[] = $event['editTimestamp'];
    $items[] = $event['visitorCount'];
  }
  return substr(md5(implode('.', $items)), 0, 8);
}


function handleAutoReloadCheckAction()
{
  $events = getMainPageEvents(true);
  if (get_param_value('autoReloadHash') != calculateAutoReloadHash($events))
    echo 'location.reload();';
}


function renderMainPageNotice()
{
  echo html_open('div');
  echo html_open('div', ['class' => 'textBlock framedBox']);
  echo html_node('span', 'Hinweise', ['class' => 'framedBoxTitle']);
  echo getConfigValue('mainPageText');
  echo html_close('div');
  echo html_close('div');
}


function renderMainPageEvent($event, $booking)
{
  $boxTitle = '';
  $divClass = '';
  if ($event['canceled'] == 1)
  {
    $boxTitle = 'Abgesagte Veranstaltung';
    $divClass = 'canceled';
  }
  else if (time() < $event['bookingOpeningTimestamp'])
  {
    $boxTitle = 'Vorschau';
    $divClass = 'preview';
  }
  else if (time() < $event['bookingClosingTimestamp'])
  {
    $boxTitle = 'Demnächst stattfindend';
    $divClass = 'bookingOpen';
  }
  else
  {
    $boxTitle = 'Buchung abgeschlossen';
    $divClass = 'bookingClosed';
  }

  echo html_open('div');
  echo html_open('div', ['class' => 'framedBox event ' . $divClass]);
  echo html_node('span', $boxTitle, ['class' => 'framedBoxTitle']);

  $persons = null;
  $phoneNumber = null;
  $bookingCanceled = false;
  if ($booking != null)
  {
    $persons = explode(';', $booking['listOfPersons']);
    $phoneNumber = $booking['phoneNumber'];
    $bookingCanceled = $booking['cancelTimestamp'] != null;
  }
  $hasActiveBooking = $persons != null && !$bookingCanceled;
  $freeSeatCount = $event['freeSeatCount'];

  renderMainPageEventBasicInfo($event, $hasActiveBooking, $freeSeatCount);

  if ($event['canceled'] != 1)
  {
    if (time() >= $event['bookingOpeningTimestamp'])
      renderMainPageEventSeatInfo($event, $hasActiveBooking, $freeSeatCount);

    if ($persons != null)
      renderMainPageBookingStatus($persons, $bookingCanceled);

    if (isBookingOpen($event))
    {
      if ($hasActiveBooking)
        renderMainPageCancelBookingForm($event);
      else if ($freeSeatCount > 0)
        renderMainPageSaveBookingForm($event, $persons, $phoneNumber, $bookingCanceled);
    }
  }

  echo html_close('div');
  echo html_close('div');
}


function renderMainPageEventBasicInfo($event, $hasActiveBooking, $freeSeatCount)
{
  echo html_open('div', ['class' => 'textBlock']);

  # title and date
  echo html_open('div', ['class' => 'titleAndDate']);
  echo $event['title'];
  echo ' am ';
  echo formatTimestampLocalLong($event['startTimestamp'], 'minute', false);
  echo html_close('div');

  # notice
  if ($event['notice'] != null)
    echo html_node('div', $event['notice'], ['class' => 'notice preWrapped']);

  # booking period
  echo html_open('div', ['class' => 'bookingPeriod']);
  if (time() < $event['bookingOpeningTimestamp'])
  {
    echo 'Buchung: ';
    echo formatTimestampLocalLong($event['bookingOpeningTimestamp'], 'minute', false);
    echo ' – ';
    echo formatTimestampLocalLong($event['bookingClosingTimestamp'], 'minute', false);
  }
  else if (time() < $event['bookingClosingTimestamp'])
  {
    if ($hasActiveBooking)
      echo 'Stornierung bis ';
    else
      echo 'Buchung bis ';
    echo formatTimestampLocalLong($event['bookingClosingTimestamp'], 'minute', false);
  }
  echo html_close('div');

  echo html_close('div');
}


function renderMainPageEventSeatInfo($event, $hasActiveBooking, $freeSeatCount)
{
  echo html_open('div', ['class' => 'seatsInfo']);

  $visitorCount = $event['visitorCount'];
  $maxVisitorCount = $visitorCount + max($freeSeatCount, 0);

  if ($visitorCount > 0)
  {
    echo $visitorCount;
    echo ' Teilnehmer, ';
  }

  if ($freeSeatCount > 0)
  {
    echo html_open('span', ['class' => 'freeSeats']);
    echo $freeSeatCount;
    if ($freeSeatCount == 1)
      echo ' freier Platz';
    else
      echo ' freie Plätze';
    echo html_close('span');
  }
  else
  {
    $class = '';
    if (!$hasActiveBooking && time() < $event['bookingClosingTimestamp'])
      $class = 'noFreeSeats';
    echo html_node('span', 'ausgebucht', ['class' => $class]);
  }

  # bar
  $barWidth = 100;
  $usedWidth = round($visitorCount / $maxVisitorCount * $barWidth);
  echo html_open('div',  ['class' => 'capacityBar']);
  echo html_open('div',  ['style' => 'width: ' . $barWidth . 'px']);
  echo html_node('div', '', ['style' => 'width: ' . $usedWidth . 'px']);
  echo html_close('div');
  echo html_close('div');

  echo html_close('div');
}


function renderMainPageBookingStatus($persons, $bookingCanceled)
{
  echo html_open('div', ['class' => 'textBlock bookingStatus']);
  echo html_open('div');
  echo html_node('span', 'Meine Buchung:', ['class' => 'myBookingTitle']);

  if ($bookingCanceled)
    echo html_node('div', ' storniert', ['class' => 'redText']);
  else
  {
    echo html_open('div');
    if (count($persons) > 1)
      echo html_open('ol');
    foreach ($persons as $personStr)
    {
      if (count($persons) > 1)
        echo html_open('li');
      $person = explode(',', $personStr);
      $surname = array_value($person, 0);
      $lastname = array_value($person, 1);

      echo html_encode($surname);
      echo ' ';
      echo html_encode($lastname);
      if (count($persons) > 1)
        echo html_close('li');
    }
    if (count($persons) > 1)
      echo html_close('ol');
    echo html_close('div');

    $showFormScript = "event.target.parentNode.parentNode.parentNode.parentNode.classList.add('cancelBookingFormOpened');disableAutoReload();";
    $button = html_form_button('Stornieren', ['class' => 'linkButton', 'onclick' => $showFormScript]);
    echo html_node('div', $button, ['class' => 'cancelBookingFormPlaceholder']);
  }

  echo html_close('div');
  echo html_close('div');
}


function renderMainPageCancelBookingForm($event)
{
  $hideFormScript = "event.target.parentNode.parentNode.parentNode.classList.remove('cancelBookingFormOpened');enableAutoReload();";

  echo html_open('div', ['class' => 'cancelBookingForm']);
  echo html_open('form', ['action' => '?a=cancelBooking', 'onsubmit' => 'postForm(event)']);
  echo html_input('hidden', 'eventId', $event['id']);
  echo html_form_submit_button('Stornierung bestätigen', ['class' => 'deleteButton']);
  echo html_form_button('Abbrechen', ['class' => 'linkButton', 'onclick' => $hideFormScript]);
  writeFormToken();
  echo html_close('form');

  echo html_close('div');
}


function renderMainPageSaveBookingForm($event, $persons, $phoneNumber, $bookingCanceled)
{
  $showFormScript = "event.target.parentNode.parentNode.classList.add('saveBookingFormOpened'); focusFirstChildInputNode(event.target.parentNode.parentNode);disableAutoReload();";
  $hideFormScript = "event.target.parentNode.parentNode.parentNode.classList.remove('saveBookingFormOpened');enableAutoReload();";

  if ($bookingCanceled)
    $buttonText = 'Erneut buchen';
  else
    $buttonText = 'Teilnehmen';
  $button = html_form_button($buttonText, ['class' => 'saveButton', 'onclick' => $showFormScript]);
  echo html_node('div', $button, ['class' => 'saveBookingFormPlaceholder']);

  echo html_open('div', ['class' => 'saveBookingForm']);
  echo html_node('div', 'Teilnehmer aus meinem Haushalt:', ['class' => 'formTitle']);
  echo html_open('form', ['action' => '?a=saveBooking', 'onsubmit' => 'postForm(event)']);

  if ($persons == null)
  {
    $persons = explode(';', getClientValue('lastListOfPersons'));
    $phoneNumber = getClientValue('lastPhoneNumber');
  }

  for ($i = 0; $i < 6; $i++)
  {
    echo html_open('div');

    $person = explode(',', array_value($persons, $i));
    $surname = array_value($person, 0);
    $lastname = array_value($person, 1);

    echo html_input('text', 'surname' . ($i + 1), $surname, ['placeholder' => 'Vorname', 'autocomplete' => 'chrome-off']);
    echo html_input('text', 'lastname' . ($i + 1), $lastname, ['placeholder' => 'Nachname', 'autocomplete' => 'chrome-off']);

    echo html_close('div');
  }
  echo html_open('div');
  echo html_node('div', 'Telefon (erforderlich) ', ['class' => 'phoneNumberLabel']);
  echo html_input('tel', 'phoneNumber', $phoneNumber, ['placeholder' => 'Telefonnummer']);
  echo html_close('div');

  if (getClientValue('persistent') != 1)
    echo html_node('div', 'Hinweis: Es wird ein Browser-Cookie gespeichert. <br>Eine Stornierung ist vom selben Gerät möglich.', ['class' => 'cookieInfo']);

  echo html_form_submit_button('Buchung speichern', ['class' => 'saveButton']);
  echo html_form_button('Abbrechen', ['class' => 'linkButton', 'onclick' => $hideFormScript]);

  echo html_input('hidden', 'eventId', $event['id']);
  writeFormToken();
  echo html_close('form');
  echo html_close('div');
}


function renderHelpPage()
{
  writeMainHtmlBeforeContent('Hilfe');

  echo html_open('div', ['class' => 'content helpPage']);
  echo html_open('div', ['class' => 'textBlock']);

  echo getConfigValue('helpPageText');

  echo html_open('div', ['class' => 'debugInfo']);
  echo 'Gerätenummer: ';
  echo getClientValue('id');
  echo ' (';
  if (getClientValue('persistent') == 1)
    echo 'beständig';
  else
  {
    echo html_open('form', ['action' => '?a=makeClientPersistent', 'onsubmit' => 'postForm(event)']);
    echo html_form_submit_button('Auf diesem Gerät merken', ['class' => 'inlineLinkButton']);
    writeFormToken();
    echo html_close('form');
  }
  echo ')';

  echo '<br>';
  echo 'Softwareversion: ';
  echo getAppVersion();
  echo html_close('div');

  echo html_close('div');
  echo html_close('div');
}


function renderAdminPage()
{
  writeMainHtmlBeforeContent('Administration');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content adminPage']);

  echo html_open('ul');
  echo html_node('li', html_a('?p=visitorList', 'Anwesenheitsliste'));
  echo html_node('li', html_a('?p=events', 'Veranstaltungen verwalten'));
  echo html_node('li', html_a('?p=bookings', 'Buchungen verwalten'));
  echo html_node('li', html_a('?p=clients', 'Geräte verwalten'));
  echo html_node('li', html_a('?p=bookingSimulator', 'Buchungs-Simulator'));
  echo html_close('ul');

  echo html_close('div');
}


function renderBookingSimulator()
{
  writeMainHtmlBeforeContent('Buchungs-Simulator');
  echo html_open('div', ['class' => 'content']);

  echo html_open('form', ['action' => '?a=bookingSimulator', 'onsubmit' => 'postForm(event)', 'class' => 'itemDetails']);

  echo html_open('div');
  echo html_node('span', 'Anzahl 5er-Stuhlreihen');
  echo html_input('number', 'capacity5Seats', 5);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl 6er-Stuhlreihen');
  echo html_input('number', 'capacity6Seats', 5);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Besucher-Limit');
  echo html_input('number', 'visitorLimit', 50);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl 6er-Gruppen');
  echo html_input('number', 'groupCount6Persons', 2);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl 5er-Gruppen');
  echo html_input('number', 'groupCount5Persons', 2);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl 4er-Gruppen');
  echo html_input('number', 'groupCount4Persons', 2);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl 3er-Gruppen');
  echo html_input('number', 'groupCount3Persons', 2);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl 2er-Gruppen');
  echo html_input('number', 'groupCount2Persons', 2);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl Einzelpersonen');
  echo html_input('number', 'groupCount1Person', 2);
  echo html_close('div');

  echo html_open('div');
  echo html_node('span', 'Anzahl Personen neue Buchung');
  echo html_input('number', 'personCountNewBooking', 0);
  echo html_close('div');

  echo html_open('div');
  echo html_form_submit_button('Berechnen', ['class' => 'saveButton']);
  echo html_close('div');

  writeFormToken();
  echo html_close('form');

  echo html_node('div', '', ['id' => 'simulatorResult', 'class' => 'textBlock', 'style' => 'font-size: 90%']);

  echo html_close('div');
}


function handleBookingSimulatorAction()
{
  $event = [];
  $event['capacity5Seats'] = intval(get_param_value('capacity5Seats'));
  $event['capacity6Seats'] = intval(get_param_value('capacity6Seats'));
  $event['visitorLimit'] = intval(get_param_value('visitorLimit'));

  $rows = [];
  $rows[] = ['personCount' => 6, 'count' => intval(get_param_value('groupCount6Persons'))];
  $rows[] = ['personCount' => 5, 'count' => intval(get_param_value('groupCount5Persons'))];
  $rows[] = ['personCount' => 4, 'count' => intval(get_param_value('groupCount4Persons'))];
  $rows[] = ['personCount' => 3, 'count' => intval(get_param_value('groupCount3Persons'))];
  $rows[] = ['personCount' => 2, 'count' => intval(get_param_value('groupCount2Persons'))];
  $rows[] = ['personCount' => 1, 'count' => intval(get_param_value('groupCount1Person'))];

  $additionalPersonCount = intval(get_param_value('personCountNewBooking'));
  if ($additionalPersonCount != null)
    $rows[] = ['personCount' => $additionalPersonCount, 'count' => 1];

  echo 'byId("simulatorResult").innerHTML = "';
  $freeSeats = calculateFreeSeatsInner($event, $rows, true);
  echo '<br>freie Sitzplätze: ';
  echo $freeSeats;
  echo '"';
}


function renderClientList()
{
  writeMainHtmlBeforeContent('Geräte verwalten');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content']);

  $items = db()->query_rows('SELECT * FROM client ORDER BY lastSeenTimestamp DESC LIMIT 100');

  foreach ($items as &$client)
  {
    decodeClient($client);
  }

  $fields = [];
  $fields[] = newIdField();
  $fields[] = newTextField('hash', 'Kennung');
  $fields[] = newTextField('persistent', 'Persistent');
  $fields[] = newTextField('userName', 'Benutzername');
  $fields[] = newTextField('userGroup', 'Benutzergruppe');
  $fields[] = newTimestampField('lastSeenTimestamp', 'Zuletzt online', false);

  renderItemTable($items, $fields);

  echo html_close('div');
}


function renderEventList()
{
  writeMainHtmlBeforeContent('Veranstaltungen verwalten');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content']);

  $fields = [];
  $fields[] = newIdField();
  $fields[] = newTextField('title', 'Titel');
  $fields[] = newTimestampField('startTimestamp', 'Beginn');
  $fields[] = newTimestampField('releaseTimestamp', 'Veröffentlichung');
  $fields[] = newTimestampField('bookingOpeningTimestamp', 'Buchungs ab');
  $fields[] = newTimestampField('bookingClosingTimestamp', 'Buchungs bis');
  $fields[] = newIntegerField('visitorCount', 'Teilnehmer', false);
  $fields[] = newIntegerField('freeSeatCount', 'Freie Sitzplätze', false);

  renderItemTable(getAdminEvents(), $fields);

  echo html_close('div');
}


function renderVisitorList()
{
  $eventId = get_param_value('eventId');
  if ($eventId == null)
    renderVisitorList_eventList();
  else
    renderVisitorList_forEvent($eventId);
}


function renderVisitorList_eventList()
{
  writeMainHtmlBeforeContent('Anwesenheitsliste');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content visitorList']);

  $events = getVisitorListEvents();
  foreach ($events as &$event)
  {
    $event['titleAndDate'] = $event['title'] . ' am ' . formatTimestampLocalLong($event['startTimestamp']);
    if (time() > $event['bookingClosingTimestamp'])
      $event['bookingState'] = 'abgeschlossen';
    else
      $event['bookingState'] = 'noch offen bis ' . formatTimestampLocalLong($event['bookingClosingTimestamp'], 'minute', false);
  }

  $fields = [];
  $fields[] = newTextField('titleAndDate', 'Veranstaltung');
  $fields[] = newTextField('bookingState', 'Buchung');
  $fields[] = newIntegerField('visitorCount', 'Teilnehmer', false);
  $fields[] = newIntegerField('freeSeatCount', 'Freie Sitzplätze', false);

  $actions = [];
  $actions[] = newLinkAction('visitorList', 'Anzeigen', 'eventId');

  renderItemTable($events, $fields, $actions);

  echo html_close('div');
}


function renderVisitorList_forEvent($eventId)
{
  writeMainHtmlBeforeContent('Anwesenheitsliste');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content visitorList']);

  $event = tryGetEventById($eventId);
  if ($event == null)
  {
    renderPageErrorBox('eventId ist ungültig.');
    return;
  }

  $rows = renderVisitorList_forEvent_getRealRows($eventId);
  $visitorCount = count($rows);
  renderVisitorList_forEvent_addNumberingAndEmptyRows($rows);

  $fields = [];
  $fields[] = newIdField();
  $fields[] = newTextField('lastname', 'Nachname');
  $fields[] = newTextField('surname', 'Vorname');
  $fields[] = newTextField('bookingInfo', 'Buchung');
  $fields[] = newTextField('phoneNumber', 'Telefon');
  $fields[] = newTextField('empty', 'Anwesend');

  echo html_form_button('Drucken', ['onclick' => 'window.print();']);

  echo html_open('div', ['class' => 'titleAndDate']);
  echo $event['title'];
  echo ' am ';
  echo formatTimestampLocalLong($event['startTimestamp'], 'minute');
  echo html_close('div');

  echo html_open('div', ['class' => 'subTitle']);
  echo $visitorCount;
  echo ' Teilnehmer';
  echo html_close('div');

  echo html_open('div', ['class' => 'subTitle']);
  echo 'Buchung ';
  if (time() > $event['bookingClosingTimestamp'])
    echo 'abgeschlossen';
  else
  {
    echo 'noch offen bis ';
    echo formatTimestampLocalLong($event['bookingClosingTimestamp'], 'minute', false);
  }
  echo html_close('div');

  echo html_open('div', ['class' => 'subTitle']);
  echo 'Stand: ';
  echo formatTimestampLocalLong(time(), 'minute');
  echo html_close('div');

  renderItemTable($rows, $fields);

  echo html_close('div');
}


function renderVisitorList_forEvent_getRealRows($eventId)
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
      $row['surname'] = $surname;
      $row['lastname'] = $lastname;
      $row['empty'] = '';
      $key = implode(' ', [$lastname, $surname, $booking['id']]);
      $rows[$key] = $row;
    }
  }

  ksort($rows);

  return $rows;
}


function renderVisitorList_forEvent_addNumberingAndEmptyRows(&$rows)
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
    $row['bookingInfo'] = '';
    $row['phoneNumber'] = '';
    $row['surname'] = '';
    $row['lastname'] = '';
    $row['empty'] = '';
    $rows[] = $row;
    $i++;
  }
}


function renderBookingList()
{
  writeMainHtmlBeforeContent('Buchungen verwalten');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content']);

  $items = db()->query_rows('SELECT * FROM booking ORDER BY id DESC LIMIT 100');

  foreach ($items as &$booking)
  {
    decodeBooking($booking);
  }

  $fields = [];
  $fields[] = newIdField();
  $fields[] = newTextField('eventId', 'Veranstaltung');
  $fields[] = newTextField('insertClientId', 'Erstellt durch');
  $fields[] = newTextField('listOfPersons', 'Personen');
  $fields[] = newTimestampField('insertTimestamp', 'Gebucht am');
  $fields[] = newTimestampField('cancelTimestamp', 'Storniert am');

  renderItemTable($items, $fields);

  echo html_close('div');
}


function renderDebugFreeSeatsCalculation()
{
  writeMainHtmlBeforeContent('Untersuchung der Freie-Sitze-Berechung');
  if (!ensureClientIsAdmin())
    return;
  $eventId = get_param_value('eventId');
  $event = tryGetEventById($eventId);
  if ($event == null)
  {
    renderPageErrorBox('eventId ist ungültig.');
    return;
  }

  echo html_open('div', ['class' => 'content']);
  echo html_open('div', ['class' => 'textBlock']);

  $freeSeats = calculateFreeSeatCount($event, null, true);
  echo '<br>freeSeats: ';
  echo $freeSeats;

  echo html_close('div');
  echo html_close('div');
}
