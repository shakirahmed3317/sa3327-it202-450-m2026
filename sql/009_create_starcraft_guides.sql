-- sql/009_create_table_guides.sql
CREATE TABLE IF NOT EXISTS Guides (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    api_id VARCHAR(100) NULL,
    excerpt TEXT NULL,
    game VARCHAR(20) NOT NULL,
    primary_category VARCHAR(50) NULL,
    slug VARCHAR(160) NULL,
    source_author VARCHAR(100) NULL,
    source_url VARCHAR(500) NULL,
    status VARCHAR(30) NULL,
    summary TEXT NULL,
    title VARCHAR(150) NOT NULL,
    video VARCHAR(500) NULL,
    opponent_race VARCHAR(20) NULL,
    player_race VARCHAR(20) NULL,
    matchup VARCHAR(20) NULL,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (api_id, title)
);
