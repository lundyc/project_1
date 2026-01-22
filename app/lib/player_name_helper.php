<?php

/**
 * Build full name from first and last name
 * 
 * @param string|null $firstName
 * @param string|null $lastName
 * @return string Full name or "Unknown Player"
 */
function build_full_name(?string $firstName, ?string $lastName): string
{
    $first = trim((string)($firstName ?? ''));
    $last = trim((string)($lastName ?? ''));
    
    if ($first && $last) {
        return "{$first} {$last}";
    }
    if ($first) {
        return $first;
    }
    if ($last) {
        return $last;
    }
    return 'Unknown Player';
}
