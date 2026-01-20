#!/bin/bash

SQL_FILE="project_1 (11).sql"
OUTPUT_FILE="restore_match_19.sql"

echo "-- Restore match_id=3 as match_id=19" > "$OUTPUT_FILE"
echo "SET FOREIGN_KEY_CHECKS=0;" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract match record
echo "-- matches table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`matches\` (\`id\`, \`club_id\`, \`season_id\`, \`competition_id\`, \`home_team_id\`, \`away_team_id\`, \`kickoff_at\`, \`match_video\`, \`venue\`, \`referee\`, \`attendance\`, \`status\`, \`notes\`, \`events_version\`, \`clips_version\`, \`derived_version\`, \`created_by\`, \`created_at\`, \`updated_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `matches`/,/;$/' "$SQL_FILE" | grep "^(3, " | sed 's/^(3, /(19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract events
echo "-- events table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`events\` (\`id\`, \`match_id\`, \`period_id\`, \`match_second\`, \`minute\`, \`minute_extra\`, \`team_side\`, \`event_type_id\`, \`importance\`, \`phase\`, \`match_player_id\`, \`player_id\`, \`opponent_detail\`, \`outcome\`, \`zone\`, \`notes\`, \`created_by\`, \`created_at\`, \`updated_by\`, \`updated_at\`, \`match_period_id\`, \`clip_id\`, \`clip_start_second\`, \`clip_end_second\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `events`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract clips
echo "-- clips table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`clips\` (\`id\`, \`match_id\`, \`event_id\`, \`clip_id\`, \`clip_name\`, \`start_second\`, \`end_second\`, \`duration_seconds\`, \`created_by\`, \`created_at\`, \`updated_by\`, \`updated_at\`, \`generation_source\`, \`generation_version\`, \`is_valid\`, \`deleted_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `clips`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract clip_jobs - need to find by clip_id reference
echo "-- clip_jobs table" >> "$OUTPUT_FILE"
# First, get clip IDs from match 3
CLIP_IDS=$(awk '/INSERT INTO `clips`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/^(\([0-9]*\),.*/\1/' | tr '\n' '|' | sed 's/|$//')
if [ ! -z "$CLIP_IDS" ]; then
  echo "INSERT INTO \`clip_jobs\` (\`id\`, \`clip_id\`, \`status\`, \`job_data\`, \`error_message\`, \`attempts\`, \`created_at\`, \`updated_at\`) VALUES" >> "$OUTPUT_FILE"
  awk '/INSERT INTO `clip_jobs`/,/;$/' "$SQL_FILE" | grep -E "^\(.*\), ($CLIP_IDS), " >> "$OUTPUT_FILE"
else
  echo "-- No clip jobs found" >> "$OUTPUT_FILE"
fi
echo "" >> "$OUTPUT_FILE"

# Extract match_players
echo "-- match_players table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`match_players\` (\`id\`, \`match_id\`, \`player_id\`, \`team_side\`, \`shirt_number\`, \`position\`, \`is_starting\`, \`created_by\`, \`created_at\`, \`updated_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `match_players`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract match_formations
echo "-- match_formations table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`match_formations\` (\`id\`, \`match_id\`, \`team_side\`, \`formation_id\`, \`created_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `match_formations`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract match_periods
echo "-- match_periods table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`match_periods\` (\`id\`, \`match_id\`, \`period_id\`, \`start_second\`, \`end_second\`, \`created_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `match_periods`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract match_videos
echo "-- match_videos table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`match_videos\` (\`id\`, \`match_id\`, \`video_label\`, \`source_type\`, \`source_path\`, \`uploaded_path\`, \`fps\`, \`duration_seconds\`, \`uploaded_filename\`, \`metadata_json\`, \`created_at\`, \`updated_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `match_videos`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract derived_stats
echo "-- derived_stats table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`derived_stats\` (\`id\`, \`match_id\`, \`team_side\`, \`passes\`, \`tackles\`, \`shots_on_target\`, \`shots_off_target\`, \`corners\`, \`fouls_committed\`, \`chances\`, \`offsides\`, \`saves\`, \`blocks\`, \`interceptions\`, \`clearances\`, \`yellow_cards\`, \`red_cards\`, \`possession_percentage\`, \`created_at\`, \`updated_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `derived_stats`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract playlists
echo "-- playlists table" >> "$OUTPUT_FILE"
echo "INSERT INTO \`playlists\` (\`id\`, \`match_id\`, \`name\`, \`created_by\`, \`created_at\`, \`updated_at\`, \`deleted_at\`) VALUES" >> "$OUTPUT_FILE"
awk '/INSERT INTO `playlists`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extract match_locks - may not exist
echo "-- match_locks table" >> "$OUTPUT_FILE"
LOCK_COUNT=$(awk '/INSERT INTO `match_locks`/,/;$/' "$SQL_FILE" | grep -c "^(.*), 3, ")
if [ "$LOCK_COUNT" -gt 0 ]; then
  echo "INSERT INTO \`match_locks\` (\`id\`, \`match_id\`, \`locked_by\`, \`locked_at\`, \`expires_at\`) VALUES" >> "$OUTPUT_FILE"
  awk '/INSERT INTO `match_locks`/,/;$/' "$SQL_FILE" | grep "^(.*), 3, " | sed 's/, 3, /, 19, /' >> "$OUTPUT_FILE"
else
  echo "-- No active match locks" >> "$OUTPUT_FILE"
fi
echo "" >> "$OUTPUT_FILE"

echo "SET FOREIGN_KEY_CHECKS=1;" >> "$OUTPUT_FILE"

echo "SQL restoration script created: $OUTPUT_FILE"
