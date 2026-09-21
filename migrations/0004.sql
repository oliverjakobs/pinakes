
CREATE TABLE IF NOT EXISTS boardgame_publisher (
  id    INTEGER PRIMARY KEY AUTOINCREMENT,
  name  TEXT    NOT NULL UNIQUE
);

CREATE TABLE boardgame (
  id            INTEGER   PRIMARY KEY AUTOINCREMENT,
  publisher_id  INTEGER   DEFAULT NULL,
  base_game_id  INTEGER   DEFAULT NULL,
  name          TEXT      NOT NULL,
  min_player    INTEGER   NOT NULL,
  max_player    INTEGER   DEFAULT NULL,
  created_at    DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (publisher_id) REFERENCES boardgame_publisher (id),
  FOREIGN KEY (base_game_id) REFERENCES boardgame (id)
);