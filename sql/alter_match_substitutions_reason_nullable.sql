-- Make reason nullable on match_substitutions
ALTER TABLE `match_substitutions`
    MODIFY `reason` VARCHAR(255) NULL;
