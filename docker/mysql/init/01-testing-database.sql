-- Runs once, when the MySQL volume is created.
-- The app database (urban_nest_api) is created by MYSQL_DATABASE in compose.yaml.
CREATE DATABASE IF NOT EXISTS urban_nest_api_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
