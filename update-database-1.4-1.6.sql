ALTER TABLE client
ADD (lastAddressLine1 TEXT,
     lastAddressLine2 TEXT);

ALTER TABLE booking
ADD (addressLine1 TEXT,
     addressLine2 TEXT);
