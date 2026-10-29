-- Runs once, when the MySQL container starts with an empty data volume.
-- The main database and the application user are created by the image from
-- MYSQL_DATABASE, MYSQL_USER and MYSQL_PASSWORD in compose.yaml.
-- This script adds a separate database for the test suite.

CREATE DATABASE IF NOT EXISTS firma_finder_testing CHARACTER SET utf8mb4;

GRANT ALL PRIVILEGES ON firma_finder_testing.* TO 'firma_finder'@'%';
