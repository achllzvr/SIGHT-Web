-- Hostinger phpMyAdmin: verify eye_health_metrics after a Watch Area sync.
-- Replace EVERY occurrence of 12345 with the real child_profile.child_id.

-- Lifetime row counts + zero-value risk (legacy fake 0.0 uploads vs true NULLs)
SELECT child_id,
       COUNT(*) AS n,
       MIN(`timestamp`) AS first_ts,
       MAX(`timestamp`) AS last_ts,
       SUM(avg_blink_rate = 0) AS blink_zero,
       SUM(avg_distance = 0) AS distance_zero,
       SUM(avg_blink_rate IS NULL) AS blink_null,
       SUM(avg_distance IS NULL) AS distance_null
FROM eye_health_metrics
WHERE child_id = 12345
GROUP BY child_id;

-- Last 7 calendar days (matches default portal 7d window)
SELECT COUNT(*) AS rows_last_7d,
       MIN(`timestamp`) AS first_ts,
       MAX(`timestamp`) AS last_ts,
       ROUND(AVG(avg_blink_rate), 1) AS mean_blink,
       ROUND(AVG(avg_distance), 1) AS mean_distance
FROM eye_health_metrics
WHERE child_id = 12345
  AND `timestamp` BETWEEN (NOW() - INTERVAL 6 DAY) AND NOW();

-- Lifetime vs 7d: if lifetime >> 7d, switch portal range to Lifetime
SELECT
  (SELECT COUNT(*) FROM eye_health_metrics WHERE child_id = 12345) AS lifetime_rows,
  (SELECT COUNT(*) FROM eye_health_metrics
   WHERE child_id = 12345
     AND `timestamp` BETWEEN (NOW() - INTERVAL 6 DAY) AND NOW()) AS rows_7d;

-- last_sync advances only on metrics insert (not device ping alone)
SELECT child_id, last_sync, user_id
FROM child_profile
WHERE child_id = 12345;
