<?php

function can_manage_matches(array $user, array $roles): bool
{
          if (in_array('platform_admin', $roles, true)) {
                    return true;
          }

          return in_array('club_admin', $roles, true) && !empty($user['club_id']);
}

function can_manage_match_for_club(array $user, array $roles, int $clubId): bool
{
          if (in_array('platform_admin', $roles, true)) {
                    return true;
          }

          return in_array('club_admin', $roles, true) && !empty($user['club_id']) && (int)$user['club_id'] === (int)$clubId;
}

function can_view_match(array $user, array $roles, int $clubId): bool
{
    if (in_array('platform_admin', $roles, true)) {
        // Platform admin: allow if clubId is valid (nonzero)
        return $clubId > 0;
    }
    return !empty($user['club_id']) && (int)$user['club_id'] === (int)$clubId;
}

function can_tag_match(array $user, array $roles, int $clubId): bool
{
          if (in_array('platform_admin', $roles, true)) {
                    return true;
          }

          $isClubMember = !empty($user['club_id']) && (int)$user['club_id'] === (int)$clubId;

          return $isClubMember && (in_array('club_admin', $roles, true) || in_array('analyst', $roles, true));
}
