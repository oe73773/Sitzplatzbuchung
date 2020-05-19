DROP DATABASE IF EXISTS seatbooking;
CREATE DATABASE seatbooking;
USE seatbooking;

CREATE TABLE client (
  id INT NOT NULL AUTO_INCREMENT,
  token VARCHAR(255),
  persistent TINYINT,
  userName TEXT,
  deviceName TEXT,
  userGroup INT,
  remark TEXT,
  insertTimestamp TIMESTAMP NULL,
  editTimestamp TIMESTAMP NULL,
  editClientId INT,
  lastSeenTimestamp TIMESTAMP NULL,
  lastListOfPersons TEXT,
  lastPhoneNumber TEXT,
  ip TEXT,
  userAgent TEXT,
  PRIMARY KEY (id),
  INDEX Index_token(token),
  INDEX Index_lastSeenTimestamp(lastSeenTimestamp)
);

CREATE TABLE adminlog (
  id INT NOT NULL AUTO_INCREMENT,
  insertTimestamp TIMESTAMP NULL,
  clientId INT,
  itemType TEXT,
  itemId INT,
  action TEXT,
  newData TEXT,
  PRIMARY KEY (id)
);

CREATE TABLE event (
  id INT NOT NULL AUTO_INCREMENT,
  startTimestamp TIMESTAMP NULL,
  title TEXT,
  notice TEXT,
  visitorLimit INT,
  capacity5Seats INT,
  capacity6Seats INT,
  releaseTimestamp TIMESTAMP NULL,
  bookingOpeningTimestamp TIMESTAMP NULL,
  bookingClosingTimestamp TIMESTAMP NULL,
  canceled TINYINT,
  remark TEXT,
  insertTimestamp TIMESTAMP NULL,
  editClientId INT,
  editTimestamp TIMESTAMP NULL,
  PRIMARY KEY (id),
  INDEX Index_startTimestamp(startTimestamp)
);

CREATE TABLE booking (
  id INT NOT NULL AUTO_INCREMENT,
  eventId INT,
  listOfPersons TEXT,
  personCount INT,
  phoneNumber TEXT,
  insertedAsAdmin TINYINT,
  insertTimestamp TIMESTAMP NULL,
  insertClientId INT,
  cancelTimestamp TIMESTAMP NULL,
  cancelClientId INT,
  PRIMARY KEY (id),
  INDEX Index_eventId(eventId),
  INDEX Index_insertClientId(insertClientId),
  INDEX Index_cancelTimestamp(cancelTimestamp)
);
