#!/usr/bin/env python3
import re
import sys

def extract_and_transform():
    """Extract match_id=3 data and transform to match_id=19"""
    
    with open('project_1 (11).sql', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find INSERT INTO sections for each table
    tables = {
        'events': r'INSERT INTO `events`.*?VALUES\n(.*?);',
        'clips': r'INSERT INTO `clips`.*?VALUES\n(.*?);',
        'clip_jobs': r'INSERT INTO `clip_jobs`.*?VALUES\n(.*?);',
        'match_players': r'INSERT INTO `match_players`.*?VALUES\n(.*?);',
        'match_formations': r'INSERT INTO `match_formations`.*?VALUES\n(.*?);',
        'match_periods': r'INSERT INTO `match_periods`.*?VALUES\n(.*?);',
        'match_videos': r'INSERT INTO `match_videos`.*?VALUES\n(.*?);',
        'derived_stats': r'INSERT INTO `derived_stats`.*?VALUES\n(.*?);',
        'playlists': r'INSERT INTO `playlists`.*?VALUES\n(.*?);',
    }
    
    results = {}
    
    for table, pattern in tables.items():
        match = re.search(pattern, content, re.DOTALL)
        if match:
            rows_text = match.group(1)
            rows = []
            
            # Split by lines and find rows with match_id=3
            for line in rows_text.split('\n'):
                line = line.strip()
                if not line or line == ';':
                    continue
                    
                # Check if this row contains match_id=3
                if table in ['events', 'clips', 'clip_jobs', 'match_players', 'match_formations', 'match_periods', 'match_videos', 'derived_stats', 'playlists']:
                    # Different tables have match_id in different positions
                    if table == 'events':
                        # (id, match_id, ...)
                        if re.search(r'^\(\d+,\s*3,\s', line):
                            # Replace match_id 3 with 19
                            line = re.sub(r'^(\(\d+,)\s*3,\s', r'\1 19, ', line)
                            # Remove trailing comma if present
                            line = line.rstrip(',')
                            rows.append(line)
                    elif table in ['clips', 'match_players', 'match_formations', 'match_periods', 'match_videos', 'derived_stats', 'playlists']:
                        # (id, match_id, ...)
                        if re.search(r'^\(\d+,\s*3,\s', line):
                            line = re.sub(r'^(\(\d+,)\s*3,\s', r'\1 19, ', line)
                            line = line.rstrip(',')
                            rows.append(line)
                    elif table == 'clip_jobs':
                        # (id, match_id, ...) AND check JSON payload
                        if re.search(r'^\(\d+,\s*3,\s', line) or '"match_id": 3' in line:
                            line = re.sub(r'^(\(\d+,)\s*3,\s', r'\1 19, ', line)
                            line = line.replace('"match_id": 3', '"match_id": 19')
                            line = line.rstrip(',')
                            rows.append(line)
            
            results[table] = rows
        else:
            results[table] = []
    
    # Generate SQL output
    output = []
    output.append("-- Restore match_id=3 as match_id=19")
    output.append("SET FOREIGN_KEY_CHECKS=0;")
    output.append("")
    
    # Get column definitions from CREATE TABLE statements
    columns = {
        'events': '`id`, `match_id`, `period_id`, `match_second`, `minute`, `minute_extra`, `team_side`, `event_type_id`, `importance`, `phase`, `match_player_id`, `player_id`, `opponent_detail`, `outcome`, `zone`, `notes`, `created_by`, `created_at`, `updated_by`, `updated_at`, `match_period_id`, `clip_id`, `clip_start_second`, `clip_end_second`',
        'clips': '`id`, `match_id`, `event_id`, `clip_id`, `clip_name`, `start_second`, `end_second`, `duration_seconds`, `created_by`, `created_at`, `updated_by`, `updated_at`, `generation_source`, `generation_version`, `is_valid`, `deleted_at`',
        'clip_jobs': '`id`, `match_id`, `event_id`, `clip_id`, `status`, `payload`, `error_message`, `completed_note`, `created_at`, `updated_at`',
        'match_players': '`id`, `match_id`, `team_side`, `player_id`, `display_name`, `shirt_number`, `position_label`, `is_starting`, `created_at`, `is_captain`',
        'match_formations': '`id`, `match_id`, `team_side`, `formation_id`, `created_at`',
        'match_periods': '`id`, `match_id`, `period_id`, `start_second`, `end_second`, `created_at`',
        'match_videos': '`id`, `match_id`, `video_label`, `source_type`, `source_path`, `uploaded_path`, `fps`, `duration_seconds`, `uploaded_filename`, `metadata_json`, `created_at`, `updated_at`',
        'derived_stats': '`id`, `match_id`, `team_side`, `passes`, `tackles`, `shots_on_target`, `shots_off_target`, `corners`, `fouls_committed`, `chances`, `offsides`, `saves`, `blocks`, `interceptions`, `clearances`, `yellow_cards`, `red_cards`, `possession_percentage`, `created_at`, `updated_at`',
        'playlists': '`id`, `match_id`, `name`, `created_by`, `created_at`, `updated_at`, `deleted_at`',
    }
    
    for table in ['events', 'clips', 'clip_jobs', 'match_players', 'match_formations', 'match_periods', 'match_videos', 'derived_stats', 'playlists']:
        output.append(f"-- {table} table")
        if results[table]:
            output.append(f"INSERT INTO `{table}` ({columns[table]}) VALUES")
            output.append(',\n'.join(results[table]) + ';')
            print(f"{table}: {len(results[table])} rows", file=sys.stderr)
        else:
            output.append(f"-- No {table} records found")
        output.append("")
    
    output.append("SET FOREIGN_KEY_CHECKS=1;")
    
    return '\n'.join(output)

if __name__ == '__main__':
    sql = extract_and_transform()
    with open('restore_match_19_final.sql', 'w', encoding='utf-8') as f:
        f.write(sql)
    print("Restoration SQL generated: restore_match_19_final.sql", file=sys.stderr)
