-- The client's spreadsheet columns that were still missing on a trip entry: Grand, Job Beg, Depo, Addio, Total to BH.
-- Their meaning and any formula between them are NOT confirmed, so each is stored exactly as typed and nothing is calculated:
-- Grand, Depo, Addio and Total to BH are amounts; Job Beg is free text (it may be an amount or a date). Only adds empty columns.

ALTER TABLE trip_jobs ADD COLUMN grand DECIMAL(12,2) NULL;
ALTER TABLE trip_jobs ADD COLUMN job_beg VARCHAR(100) NULL;
ALTER TABLE trip_jobs ADD COLUMN depo DECIMAL(12,2) NULL;
ALTER TABLE trip_jobs ADD COLUMN addio DECIMAL(12,2) NULL;
ALTER TABLE trip_jobs ADD COLUMN total_to_bh DECIMAL(12,2) NULL;
