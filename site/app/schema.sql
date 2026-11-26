-- Монолит-Вест: схема базы (SQLite)

CREATE TABLE IF NOT EXISTS settings (
  key   TEXT PRIMARY KEY,
  value TEXT NOT NULL DEFAULT ''
);

-- разделы каталога: ЖБИ (kind=zhbi), газонная решётка (kind=lawn)
CREATE TABLE IF NOT EXISTS categories (
  id              INTEGER PRIMARY KEY,
  slug            TEXT NOT NULL UNIQUE,
  kind            TEXT NOT NULL DEFAULT 'zhbi',
  name            TEXT NOT NULL,
  h1              TEXT NOT NULL DEFAULT '',
  intro           TEXT NOT NULL DEFAULT '',
  seo_text        TEXT NOT NULL DEFAULT '',
  seo_title       TEXT NOT NULL DEFAULT '',
  seo_description TEXT NOT NULL DEFAULT '',
  image           TEXT NOT NULL DEFAULT '',
  sort            INTEGER NOT NULL DEFAULT 0,
  visible         INTEGER NOT NULL DEFAULT 1
);

-- товары ЖБИ, бордюры, газонная решётка
CREATE TABLE IF NOT EXISTS products (
  id               INTEGER PRIMARY KEY,
  category_id      INTEGER NOT NULL REFERENCES categories(id),
  subgroup         TEXT NOT NULL DEFAULT '',
  slug             TEXT NOT NULL UNIQUE,
  name             TEXT NOT NULL,
  short            TEXT NOT NULL DEFAULT '',
  description      TEXT NOT NULL DEFAULT '',
  price            INTEGER,                         -- NULL = цена по запросу
  price_unit       TEXT NOT NULL DEFAULT 'шт',
  in_stock         INTEGER NOT NULL DEFAULT 1,
  specs            TEXT NOT NULL DEFAULT '[]',      -- JSON [[название, значение], ...]
  images           TEXT NOT NULL DEFAULT '[]',      -- JSON ["uploads/products/xxx", ...] без размера и расширения
  seo_title        TEXT NOT NULL DEFAULT '',
  seo_description  TEXT NOT NULL DEFAULT '',
  sort             INTEGER NOT NULL DEFAULT 0,
  visible          INTEGER NOT NULL DEFAULT 1,
  old_url          TEXT NOT NULL DEFAULT '',
  note             TEXT NOT NULL DEFAULT '',        -- внутренняя заметка (что проверить у заказчика)
  updated_at       TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS products_cat ON products(category_id, sort);

-- брусчатка: формы, цвета и цены «форма × цвет × толщина»
CREATE TABLE IF NOT EXISTS paving_forms (
  id               INTEGER PRIMARY KEY,
  slug             TEXT NOT NULL UNIQUE,
  name             TEXT NOT NULL,
  short            TEXT NOT NULL DEFAULT '',
  description      TEXT NOT NULL DEFAULT '',
  elements         TEXT NOT NULL DEFAULT '[]',      -- JSON [[длина, ширина], ...] мм, для 3D и характеристик
  m2_per_pallet    REAL,
  pallet_kg        INTEGER,
  specs            TEXT NOT NULL DEFAULT '[]',
  images           TEXT NOT NULL DEFAULT '[]',
  seo_title        TEXT NOT NULL DEFAULT '',
  seo_description  TEXT NOT NULL DEFAULT '',
  seo_text         TEXT NOT NULL DEFAULT '',
  sort             INTEGER NOT NULL DEFAULT 0,
  visible          INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS paving_colors (
  id        INTEGER PRIMARY KEY,
  slug      TEXT NOT NULL UNIQUE,
  name      TEXT NOT NULL,
  grp       TEXT NOT NULL DEFAULT 'mono',           -- mono | mix (колормикс)
  palette   TEXT NOT NULL DEFAULT '[]',             -- JSON [["#hex", доля], ...] для 3D и образцов
  sort      INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS paving_prices (
  form_id    INTEGER NOT NULL REFERENCES paving_forms(id) ON DELETE CASCADE,
  color_id   INTEGER NOT NULL REFERENCES paving_colors(id) ON DELETE CASCADE,
  thickness  INTEGER NOT NULL DEFAULT 60,
  price      INTEGER,                               -- за м², NULL = по запросу
  available  INTEGER NOT NULL DEFAULT 1,
  forming    TEXT NOT NULL DEFAULT '',              -- однослойное / двухслойное
  image      TEXT NOT NULL DEFAULT '',
  old_url    TEXT NOT NULL DEFAULT '',
  PRIMARY KEY (form_id, color_id, thickness)
);

-- тексты страниц и блоки (JSON), вопросы-ответы
CREATE TABLE IF NOT EXISTS pages (
  slug             TEXT PRIMARY KEY,
  title            TEXT NOT NULL DEFAULT '',
  h1               TEXT NOT NULL DEFAULT '',
  body             TEXT NOT NULL DEFAULT '',
  seo_title        TEXT NOT NULL DEFAULT '',
  seo_description  TEXT NOT NULL DEFAULT '',
  updated_at       TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS faq (
  id     INTEGER PRIMARY KEY,
  scope  TEXT NOT NULL,                             -- home, paving, zhbi, lawn, delivery
  q      TEXT NOT NULL,
  a      TEXT NOT NULL,
  sort   INTEGER NOT NULL DEFAULT 0
);

-- заявки с сайта (дублируют письма: если почта не дошла, заявка не теряется)
CREATE TABLE IF NOT EXISTS leads (
  id          INTEGER PRIMARY KEY,
  created_at  TEXT NOT NULL,
  type        TEXT NOT NULL,
  name        TEXT NOT NULL DEFAULT '',
  phone       TEXT NOT NULL DEFAULT '',
  email       TEXT NOT NULL DEFAULT '',
  payload     TEXT NOT NULL DEFAULT '{}',
  page        TEXT NOT NULL DEFAULT '',
  source      TEXT NOT NULL DEFAULT '{}',           -- UTM и реферер
  ip          TEXT NOT NULL DEFAULT '',
  status      TEXT NOT NULL DEFAULT 'new',          -- new | work | done | spam
  mail_ok     INTEGER NOT NULL DEFAULT 0,
  mail_error  TEXT NOT NULL DEFAULT '',
  note        TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS leads_date ON leads(created_at);
CREATE INDEX IF NOT EXISTS leads_ip ON leads(ip, created_at);

CREATE TABLE IF NOT EXISTS users (
  id          INTEGER PRIMARY KEY,
  login       TEXT NOT NULL UNIQUE,
  name        TEXT NOT NULL DEFAULT '',
  pass_hash   TEXT NOT NULL,
  role        TEXT NOT NULL DEFAULT 'editor',       -- admin | editor
  created_at  TEXT NOT NULL,
  last_login  TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS login_attempts (
  ip  TEXT NOT NULL,
  at  TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS audit (
  id         INTEGER PRIMARY KEY,
  at         TEXT NOT NULL,
  user_id    INTEGER,
  action     TEXT NOT NULL,
  entity     TEXT NOT NULL,
  entity_id  TEXT,
  details    TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS redirects (
  from_path  TEXT PRIMARY KEY,
  to_path    TEXT NOT NULL,
  code       INTEGER NOT NULL DEFAULT 301,
  hits       INTEGER NOT NULL DEFAULT 0
);
