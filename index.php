<?php

require_once('version.php');
require_once('baseutils.php');
require_once('config.php');
require_once('helper.php');
require_once('client.php');
require_once('event.php');
require_once('booking.php');
require_once('adminlog.php');
require_once('ui.php');
require_once('manager.php');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

connectToDb();
initClientIdentification();

$action = get_param_value('a');
if ($action != null)
{
  if ($action == 'saveBooking')
  {
    verifyFormTokenOrExit();
    handleSaveBookingAction();
  }
  else if ($action == 'cancelBooking')
  {
    verifyFormTokenOrExit();
    handleCancelBookingAction();
  }
  else if ($action == 'makeClientPersistent')
  {
    verifyFormTokenOrExit();
    handleMakeClientPersistentAction();
  }
  else if ($action == 'bookingSimulator')
    handleBookingSimulatorAction();
  else if ($action == 'autoReloadCheck')
    handleAutoReloadCheckAction();
  else if ($action == 'saveEvent')
    handleSaveEventAction();
  else if ($action == 'saveClient')
    handleSaveClientAction();
  else
  {
    echo 'alert("Diese Aktion ist nicht (mehr) gültig.");';
    echo 'location.reload();';
  }
}
else
{
  $page = get_param_value('p', 'main');
  if ($page == 'main')
    renderMainPage();
  else if ($page == 'help')
    renderHelpPage();
  else if ($page == 'admin')
    renderAdminPage();
  else if ($page == 'clients')
    renderClients();
  else if ($page == 'events')
    renderEvents();
  else if ($page == 'bookings')
    renderBookings();
  else if ($page == 'adminlog')
    renderAdminlogs();
  else if ($page == 'debugFreeSeatsCalculation')
    renderDebugFreeSeatsCalculation();
  else if ($page == 'bookingSimulator')
    renderBookingSimulator();
  else if ($page == 'visitorList')
    renderVisitorsSheet();
  else
    renderNotFoundError();

  writeMainHtmlAfterContent();
}
