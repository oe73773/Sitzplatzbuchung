<?php

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
    echo 'showErrorMsg("Parameter eventId ist ungültig.");';
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

  $personen = [];
  for ($i = 1; $i <= 6; $i++)
  {
    $surname = trim(get_param_value('surname' . $i));
    $lastname = trim(get_param_value('lastname' . $i));
    if ($surname == '' && $lastname == '')
      continue;
    if (strlen($surname) == '')
    {
      echo 'showErrorMsg("Bitte Vorname aller Personen angeben.");';
      return;
    }
    if (strlen($lastname) == '')
    {
      echo 'showErrorMsg("Bitte Nachname aller Personen angeben.");';
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
    $personen[] = $surname . ',' . $lastname;
  }

  if (count($personen) == 0)
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

  $freeSeatCount = calculateFreeSeatCount($event, count($personen));
  if ($freeSeatCount == -1)
  {
    $freeSeatCountWithoutNew = calculateFreeSeatCount($event);
    if ($freeSeatCountWithoutNew > 0 && count($personen) > 1)
      echo 'showErrorMsg("Es sind nicht mehr genügend Plätze frei. Bitte weniger Personen eingeben.");';
    else
      echo 'showErrorMsg("Es sind keine Plätze mehr frei.");';
    echo 'location.reload();';
    return;
  }

  $listOfPersons = implode(';', $personen);

  # insert booking row
  $booking = [];
  $booking['eventId'] = $event['id'];
  $booking['listOfPersons'] = $listOfPersons;
  $booking['personCount'] = count($personen);
  $booking['phoneNumber'] = $phoneNumber;
  $booking['insertTimestamp'] = format_timestamp(time());
  $booking['insertClientId'] = getClientValue('id');
  $bookingId = db()->insert('booking', $booking);

  # update client row
  $clientValues = [];
  $clientValues['lastListOfPersons'] = $listOfPersons;
  $clientValues['lastPhoneNumber'] = $phoneNumber;
  $clientValues['persistent'] = 1;
  db()->try_update_by_id('client', getClientValue('id'), $clientValues);

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
    echo 'showErrorMsg("Parameter eventId ist ungültig.");';
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
