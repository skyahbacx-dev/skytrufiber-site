-- 002_add_survey_rating.sql
-- Additive, non-destructive migration.
-- Adds a real 1-5 rating scale to survey_responses so Analytics can show
-- an actual average rating / rating distribution instead of keyword-guessed
-- sentiment. No existing columns or rows are touched or removed.

ALTER TABLE survey_responses
  ADD COLUMN IF NOT EXISTS rating SMALLINT NULL;

-- Optional but recommended: keep ratings within range at the DB level.
-- (Safe to run even if some historical rows are NULL - NULL passes the check.)
-- Postgres doesn't support "ADD CONSTRAINT IF NOT EXISTS", so guard it manually.
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'survey_responses_rating_range'
  ) THEN
    ALTER TABLE survey_responses
      ADD CONSTRAINT survey_responses_rating_range
      CHECK (rating IS NULL OR (rating BETWEEN 1 AND 5));
  END IF;
END $$;

-- Nothing is dropped. Existing rows simply have rating = NULL until
-- customers start submitting through the updated form.
