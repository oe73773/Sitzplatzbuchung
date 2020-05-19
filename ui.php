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
  $events = getCurrentEvents();
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
  $freeSeatCount = calculateFreeSeats($event);

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
  echo formatTimestampLocalDateTimeLongNoYear($event['startTimestamp']);
  echo html_close('div');

  # notice
  if ($event['notice'] != null)
    echo html_node('div', $event['notice'], ['class' => 'notice preWrapped']);

  # booking period
  echo html_open('div', ['class' => 'bookingPeriod']);
  if (time() < $event['bookingOpeningTimestamp'])
  {
    echo 'Buchung: ';
    echo formatTimestampLocalDateTimeLongNoYear($event['bookingOpeningTimestamp']);
    echo ' – ';
    echo formatTimestampLocalDateTimeLongNoYear($event['bookingClosingTimestamp']);
  }
  else if (time() < $event['bookingClosingTimestamp'])
  {
    if ($hasActiveBooking)
      echo 'Stornierung bis ';
    else
      echo 'Buchung bis ';
    echo formatTimestampLocalDateTimeLongNoYear($event['bookingClosingTimestamp']);
  }
  echo html_close('div');

  echo html_close('div');
}


function renderMainPageEventSeatInfo($event, $hasActiveBooking, $freeSeatCount)
{
  echo html_open('div', ['class' => 'seatsInfo']);

  $visitorCount = getEventVisitorCount($event['id']);
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
    # TODO
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

    $showFormScript = "event.target.parentNode.parentNode.parentNode.parentNode.classList.add('cancelBookingFormOpened');";
    $button = html_form_button('Stornieren', ['class' => 'linkButton', 'onclick' => $showFormScript]);
    echo html_node('div', $button, ['class' => 'cancelBookingFormPlaceholder']);
  }

  echo html_close('div');
  echo html_close('div');
}


function renderMainPageCancelBookingForm($event)
{
  $hideFormScript = "event.target.parentNode.parentNode.parentNode.classList.remove('cancelBookingFormOpened')";

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
  $showFormScript = "event.target.parentNode.parentNode.classList.add('saveBookingFormOpened'); focusFirstChildInputNode(event.target.parentNode.parentNode);";
  $hideFormScript = "event.target.parentNode.parentNode.parentNode.classList.remove('saveBookingFormOpened')";

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

  # renderAdminActiveEvents();

  echo html_open('ul');
  echo html_node('li', html_a('?p=bookings', 'Buchungen'));
  echo html_node('li', html_a('?p=events', 'Veranstaltungen'));
  echo html_node('li', html_a('?p=clients', 'Geräte'));
  echo html_node('li', html_a('?p=bookingSimulator', 'Buchungs-Simulator'));
  echo html_close('ul');

  echo html_close('div');
}


function renderAdminActiveEvents()
{
  $events = getCurrentEvents();

  $columns = [];
  $columns['id'] = 'Nummer';
  $columns['title'] = 'Titel';
  $columns['startTimestamp'] = 'Beginn';
  $columns['bookingOpeningTimestamp'] = 'Buchungs-Beginn';
  $columns['bookingClosingTimestamp'] = 'Buchungs-Ende';
  renderItemTable($events, $columns);
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

  echo html_form_submit_button('Berechnen', ['class' => 'saveButton']);
  writeFormToken();
  echo html_close('form');

  echo html_node('div', '', ['id' => 'simulatorResult']);

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
  echo '<br>freeSeats: ';
  echo $freeSeats;
  echo '"';
}


function renderClientList()
{
  writeMainHtmlBeforeContent('Geräte');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content']);

  $items = db()->query_rows('SELECT * FROM client ORDER BY lastSeenTimestamp DESC LIMIT 100');
  $columns = [];
  $columns['id'] = 'Nummer';
  $columns['lastSeenTimestamp'] = 'Zuletzt gesehen';
  $columns['userName'] = 'Benutzername';
  $columns['userGroup'] = 'Benutzergruppe';
  renderItemTable($items, $columns);

  echo html_close('div');
}


function renderEventList()
{
  writeMainHtmlBeforeContent('Veranstaltungen');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content']);

  $items = db()->query_rows('SELECT * FROM event ORDER BY startTimestamp DESC LIMIT 100');
  $columns = [];
  $columns['id'] = 'Nummer';
  $columns['title'] = 'Titel';
  $columns['startTimestamp'] = 'Beginn';
  $columns['bookingOpeningTimestamp'] = 'Buchungs-Beginn';
  $columns['bookingClosingTimestamp'] = 'Buchungs-Ende';
  renderItemTable($items, $columns);

  echo html_close('div');
}


function renderBookingList()
{
  writeMainHtmlBeforeContent('Buchungen');
  if (!ensureClientIsAdmin())
    return;

  echo html_open('div', ['class' => 'content']);

  $items = db()->query_rows('SELECT * FROM booking ORDER BY id DESC LIMIT 100');
  $columns = [];
  $columns['id'] = 'Nummer';
  $columns['eventId'] = 'Veranstaltung';
  $columns['listOfPersons'] = 'Personen';
  $columns['insertTimestamp'] = 'Gebucht am';
  $columns['cancelTimestamp'] = 'Storniert am';

  renderItemTable($items, $columns);

  echo html_close('div');
}


function renderItemTable($items, $columns)
{
  echo '<table class="border">';
  echo '<tr>';
  foreach ($columns as $key => $title)
  {
    echo '<th>';
    echo $title;
    echo '</th>';
  }
  echo '</tr>';
  foreach ($items as $item)
  {
    echo '<tr>';
    foreach ($columns as $key => $title)
    {
      echo '<td>';
      echo $item[$key];
      echo '</td>';
    }
    echo '</tr>';
  }
  echo '</table>';
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

  $freeSeats = calculateFreeSeats($event, null, true);
  echo '<br>freeSeats: ';
  echo $freeSeats;

  echo html_close('div');
  echo html_close('div');
}
