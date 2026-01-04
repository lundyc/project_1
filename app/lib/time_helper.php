<?php
declare(strict_types=1);

/**
 * Encapsulates formatting for match_second so we only have one canonical place for MM:SS logic.
 */
class MatchSecondFormat
{
          private int $totalSeconds;
          private int $minutes;
          private int $seconds;

          public function __construct(int $seconds)
          {
                    $this->totalSeconds = max(0, $seconds);
                    $this->minutes = (int)floor($this->totalSeconds / 60);
                    $this->seconds = $this->totalSeconds % 60;
          }

          public function minutes(): int
          {
                    return $this->minutes;
          }

          public function seconds(): int
          {
                    return $this->seconds;
          }

          public function totalSeconds(): int
          {
                    return $this->totalSeconds;
          }

          public function formatted(): string
          {
                    return sprintf('%02d:%02d', $this->minutes, $this->seconds);
          }

          public function toArray(): array
          {
                    return [
                              'formatted' => $this->formatted(),
                              'minutes' => $this->minutes,
                              'seconds' => $this->seconds,
                              'total' => $this->totalSeconds,
                    ];
          }

          public function __toString(): string
          {
                    return $this->formatted();
          }
}

/**
 * Format match_second as MM:SS. match_second is the canonical source of the event time.
 */
function formatMatchSecond(?int $seconds): MatchSecondFormat
{
          return new MatchSecondFormat((int)max(0, $seconds ?? 0));
}

/**
 * Convenience helper for just the formatted string.
 */
function formatMatchSecondText(?int $seconds): string
{
          return formatMatchSecond($seconds)->formatted();
}
