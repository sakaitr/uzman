<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dir = data_dir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $dir . '/site.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function val(string $sql, array $params = [])
{
    $r = q($sql, $params)->fetchColumn();
    return $r === false ? null : $r;
}

function migrate(): void
{
    $pdo = db();
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT NOT NULL DEFAULT '');
    CREATE TABLE IF NOT EXISTS strings (k TEXT NOT NULL, lang TEXT NOT NULL, v TEXT NOT NULL DEFAULT '', PRIMARY KEY (k, lang));
    CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT NOT NULL UNIQUE, type TEXT NOT NULL DEFAULT 'custom',
        status INTEGER NOT NULL DEFAULT 1, in_nav INTEGER NOT NULL DEFAULT 0, in_footer INTEGER NOT NULL DEFAULT 0, sort INTEGER NOT NULL DEFAULT 100,
        title TEXT NOT NULL DEFAULT '{}', meta TEXT NOT NULL DEFAULT '{}', h1 TEXT NOT NULL DEFAULT '{}', lead TEXT NOT NULL DEFAULT '{}',
        cta INTEGER NOT NULL DEFAULT 1, blocks TEXT NOT NULL DEFAULT '[]', updated_at TEXT, noindex INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT NOT NULL UNIQUE, page TEXT NOT NULL, name TEXT NOT NULL DEFAULT '{}', sort INTEGER NOT NULL DEFAULT 0);
    CREATE TABLE IF NOT EXISTS subcats (
        id INTEGER PRIMARY KEY AUTOINCREMENT, cat_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE, parent_id INTEGER,
        slug TEXT NOT NULL DEFAULT '', name TEXT NOT NULL DEFAULT '{}', desc TEXT NOT NULL DEFAULT '{}', sizes TEXT NOT NULL DEFAULT '',
        art TEXT NOT NULL DEFAULT 'aerosol', sort INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT, sub_id INTEGER NOT NULL REFERENCES subcats(id) ON DELETE CASCADE,
        cap TEXT NOT NULL DEFAULT '', image TEXT NOT NULL DEFAULT '', sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS pl_models (id INTEGER PRIMARY KEY AUTOINCREMENT, grp TEXT NOT NULL, label TEXT NOT NULL DEFAULT '', dims TEXT NOT NULL DEFAULT '', image TEXT NOT NULL DEFAULT '', sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1);
    CREATE TABLE IF NOT EXISTS pl_groups (slug TEXT PRIMARY KEY, name TEXT NOT NULL DEFAULT '{}', sort INTEGER NOT NULL DEFAULT 0);
    CREATE TABLE IF NOT EXISTS hero_slides (id INTEGER PRIMARY KEY AUTOINCREMENT, image TEXT NOT NULL, label TEXT NOT NULL DEFAULT '', sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1);
    CREATE TABLE IF NOT EXISTS docs (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL DEFAULT '{}', note TEXT NOT NULL DEFAULT '{}', file TEXT NOT NULL DEFAULT '', image TEXT NOT NULL DEFAULT '', sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1);
    CREATE TABLE IF NOT EXISTS media (id INTEGER PRIMARY KEY AUTOINCREMENT, path TEXT NOT NULL UNIQUE, name TEXT NOT NULL DEFAULT '', mime TEXT NOT NULL DEFAULT '', w INTEGER DEFAULT 0, h INTEGER DEFAULT 0, size INTEGER DEFAULT 0, created_at TEXT);
    CREATE TABLE IF NOT EXISTS submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT, created_at TEXT NOT NULL, lang TEXT, name TEXT, company TEXT, email TEXT, phone TEXT,
        category TEXT, market TEXT, qty TEXT, brief TEXT, ip TEXT, ua TEXT, status TEXT NOT NULL DEFAULT 'new', mail_ok INTEGER NOT NULL DEFAULT 0, note TEXT NOT NULL DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, name TEXT NOT NULL DEFAULT '', pass_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'admin', created_at TEXT, last_login TEXT);
    CREATE TABLE IF NOT EXISTS throttle (id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL, ip TEXT NOT NULL, ts INTEGER NOT NULL);
    CREATE TABLE IF NOT EXISTS seo_audits (id INTEGER PRIMARY KEY AUTOINCREMENT, ts TEXT NOT NULL, score INTEGER NOT NULL, data TEXT NOT NULL);
    CREATE INDEX IF NOT EXISTS idx_throttle ON throttle(kind, ip, ts);
    CREATE INDEX IF NOT EXISTS idx_items_sub ON items(sub_id, sort);
    ");
    upgrade_steps();
}

const SCHEMA_VERSION = 3;

/** Default SEO/GEO values for a fresh install of THIS site (client-specific; replace in seed for another client). */
function seo_seed_defaults(): array
{
    return [
        'seo_org_name' => 'Uzman Cosmetic', 'seo_legal' => 'Uzman Kozmetik Kimya San. ve Dış Tic. Ltd. Şti.', 'seo_logo' => 'assets/img/apple-touch-icon.png', 'seo_founded' => '1978',
        'seo_street' => 'Şekerpınar Mh. Özbek Sk. No:4', 'seo_city' => 'Çayırova', 'seo_region' => 'Kocaeli', 'seo_country' => 'TR',
        'seo_knows' => "Private label cosmetics manufacturing\nAerosol deodorant\nBody mist\nEau de parfum\nRoom freshener\nReed diffuser\nAluminium aerosol bottles",
        'seo_llms_lang' => 'en',
    ];
}

/** All idempotent schema steps (fresh installs and upgrades share them). */
function upgrade_steps(): void
{
    $pdo = db();
    $has = function (string $table, string $col) use ($pdo): bool {
        return in_array($col, array_column($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(), 'name'), true);
    };
    if (!$has('pages', 'noindex')) {
        $pdo->exec('ALTER TABLE pages ADD COLUMN noindex INTEGER NOT NULL DEFAULT 0');
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS seo_audits (id INTEGER PRIMARY KEY AUTOINCREMENT, ts TEXT NOT NULL, score INTEGER NOT NULL, data TEXT NOT NULL)');
    // v3: growth hub (actions, ads, first-party traffic + attribution)
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'click_id', 'ref_host', 'landing', 'channel'] as $c) {
        if (!$has('submissions', $c)) {
            $pdo->exec('ALTER TABLE submissions ADD COLUMN ' . $c . " TEXT NOT NULL DEFAULT ''");
        }
    }
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS growth_actions (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, channel TEXT NOT NULL DEFAULT 'seo', priority TEXT NOT NULL DEFAULT 'med', impact TEXT NOT NULL DEFAULT 'med', effort TEXT NOT NULL DEFAULT 'med',
        status TEXT NOT NULL DEFAULT 'todo', due TEXT NOT NULL DEFAULT '', owner TEXT NOT NULL DEFAULT '', why TEXT NOT NULL DEFAULT '', steps TEXT NOT NULL DEFAULT '', notes TEXT NOT NULL DEFAULT '', result TEXT NOT NULL DEFAULT '',
        source TEXT NOT NULL DEFAULT '', campaign_id INTEGER, created_at TEXT, updated_at TEXT, done_at TEXT
    );
    CREATE TABLE IF NOT EXISTS growth_log (id INTEGER PRIMARY KEY AUTOINCREMENT, action_id INTEGER NOT NULL REFERENCES growth_actions(id) ON DELETE CASCADE, ts TEXT NOT NULL, who TEXT NOT NULL DEFAULT '', text TEXT NOT NULL DEFAULT '');
    CREATE TABLE IF NOT EXISTS campaigns (
        id INTEGER PRIMARY KEY AUTOINCREMENT, platform TEXT NOT NULL DEFAULT 'google', name TEXT NOT NULL, slug TEXT NOT NULL UNIQUE, objective TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'draft',
        start TEXT NOT NULL DEFAULT '', end TEXT NOT NULL DEFAULT '', budget REAL NOT NULL DEFAULT 0, landing TEXT NOT NULL DEFAULT 'index', notes TEXT NOT NULL DEFAULT '', created_at TEXT
    );
    CREATE TABLE IF NOT EXISTS campaign_metrics (id INTEGER PRIMARY KEY AUTOINCREMENT, campaign_id INTEGER NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE, from_date TEXT NOT NULL, to_date TEXT NOT NULL,
        spend REAL NOT NULL DEFAULT 0, impressions INTEGER NOT NULL DEFAULT 0, clicks INTEGER NOT NULL DEFAULT 0, conversions INTEGER NOT NULL DEFAULT 0, note TEXT NOT NULL DEFAULT '');
    CREATE TABLE IF NOT EXISTS visits (day TEXT NOT NULL, slug TEXT NOT NULL, lang TEXT NOT NULL, channel TEXT NOT NULL, source TEXT NOT NULL DEFAULT '', sessions INTEGER NOT NULL DEFAULT 0, views INTEGER NOT NULL DEFAULT 0, PRIMARY KEY (day, slug, lang, channel, source));
    CREATE INDEX IF NOT EXISTS idx_visits_day ON visits(day);
    CREATE INDEX IF NOT EXISTS idx_sub_created ON submissions(created_at);
    ");
}

/** Add strings that exist in seed.json but not yet in the database (never overwrites edited text). */
function sync_new_strings(): void
{
    $f = UZ_APP . '/seed/seed.json';
    if (!is_file($f)) {
        return;
    }
    $d = json_decode((string)file_get_contents($f), true);
    $st = db()->prepare('INSERT OR IGNORE INTO strings(k, lang, v) VALUES(?,?,?)');
    foreach ($d['strings'] ?? [] as $k => $langs) {
        foreach ($langs as $l => $v) {
            $st->execute([$k, $l, $v]);
        }
    }
}

/** Schema upgrades for already-installed sites (run on every request, cheap when current). */
function maybe_upgrade(): void
{
    if ((int)setting('schema_version', '1') >= SCHEMA_VERSION) {
        return;
    }
    upgrade_steps();
    sync_new_strings();
    $st = db()->prepare('INSERT OR IGNORE INTO settings(k, v) VALUES(?, ?)');
    foreach (seo_seed_defaults() as $k => $v) {
        $st->execute([$k, $v]);
    }
    set_setting('schema_version', (string)SCHEMA_VERSION);
    cache_clear();
}

/** Import design content from app/seed/seed.json (only into empty tables). */
function seed_import(): void
{
    $f = UZ_APP . '/seed/seed.json';
    if (!is_file($f)) {
        throw new RuntimeException('seed.json missing');
    }
    $d = json_decode((string)file_get_contents($f), true);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if (!val('SELECT COUNT(*) FROM strings')) {
            $st = $pdo->prepare('INSERT OR IGNORE INTO strings(k, lang, v) VALUES(?,?,?)');
            foreach ($d['strings'] as $k => $langs) {
                foreach ($langs as $l => $v) {
                    $st->execute([$k, $l, $v]);
                }
            }
        }
        if (!val('SELECT COUNT(*) FROM pages')) {
            $st = $pdo->prepare('INSERT INTO pages(slug,type,status,in_nav,in_footer,sort,title,meta,h1,lead,cta,blocks,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
            foreach ($d['pages'] as $p) {
                $st->execute([$p['slug'], $p['type'], $p['status'], $p['in_nav'], $p['in_footer'], $p['sort'], je($p['title'] ?? new stdClass()), je($p['meta'] ?? new stdClass()),
                    je($p['h1'] ?? new stdClass()), je($p['lead'] ?? new stdClass()), $p['cta'] ?? 1, je($p['blocks'] ?? []), date('Y-m-d H:i:s')]);
            }
        }
        if (!val('SELECT COUNT(*) FROM categories')) {
            $cs = $pdo->prepare('INSERT INTO categories(slug,page,name,sort) VALUES(?,?,?,?)');
            $ss = $pdo->prepare('INSERT INTO subcats(cat_id,parent_id,slug,name,desc,sizes,art,sort) VALUES(?,?,?,?,?,?,?,?)');
            $is = $pdo->prepare('INSERT INTO items(sub_id,cap,image,sort) VALUES(?,?,?,?)');
            foreach ($d['categories'] as $c) {
                $cs->execute([$c['slug'], $c['page'], je($c['name']), $c['sort']]);
                $cid = (int)$pdo->lastInsertId();
                foreach ($c['subs'] as $s) {
                    $ss->execute([$cid, null, $s['slug'], je($s['name']), je($s['desc']), implode(',', $s['sizes']), $s['art'], $s['sort']]);
                    $sid = (int)$pdo->lastInsertId();
                    $n = 0;
                    foreach ($s['items'] ?? [] as $it) {
                        $is->execute([$sid, is_array($it['cap']) ? je($it['cap']) : $it['cap'], $it['image'], $n++]);
                    }
                    foreach ($s['children'] ?? [] as $ci => $ch) {
                        $ss->execute([$cid, $sid, '', je($ch['name']), '{}', '', $s['art'], $ci]);
                        $chid = (int)$pdo->lastInsertId();
                        $n = 0;
                        foreach ($ch['items'] as $it) {
                            $is->execute([$chid, is_array($it['cap']) ? je($it['cap']) : $it['cap'], $it['image'], $n++]);
                        }
                    }
                }
            }
        }
        if (!val('SELECT COUNT(*) FROM pl_groups')) {
            $st = $pdo->prepare('INSERT INTO pl_groups(slug,name,sort) VALUES(?,?,?)');
            $i = 0;
            foreach ($d['pl_groups'] as $slug => $name) {
                $st->execute([$slug, je($name), $i++]);
            }
            $st = $pdo->prepare('INSERT INTO pl_models(grp,label,dims,image,sort) VALUES(?,?,?,?,?)');
            foreach ($d['pl_models'] as $m) {
                $st->execute([$m['grp'], $m['label'], $m['dims'], $m['image'], $m['sort']]);
            }
        }
        if (!val('SELECT COUNT(*) FROM hero_slides')) {
            $st = $pdo->prepare('INSERT INTO hero_slides(image,label,sort) VALUES(?,?,?)');
            foreach ($d['hero'] as $h) {
                $st->execute([$h['image'], $h['label'], $h['sort']]);
            }
        }
        $defaults = [
            'site_name' => 'Uzman Cosmetic', 'company_legal' => 'Uzman Kozmetik Kimya San. ve Dış Tic. Ltd. Şti.',
            'phone1' => '+90 262 658 00 99', 'phone2' => '+90 262 658 03 99', 'fax' => '+90 262 658 03 20',
            'email' => 'info@uzmancosmetic.com', 'instagram' => 'https://www.instagram.com/uzmancosmetic/', 'whatsapp' => '',
            'site_url' => '', 'theme' => 'noir', 'theme_switcher' => '0', 'notify_email' => 'info@uzmancosmetic.com',
            'smtp_host' => '', 'smtp_port' => '587', 'smtp_user' => '', 'smtp_pass' => '', 'smtp_secure' => 'tls', 'mail_from' => '',
            'fan_body' => je($d['fan']['body']), 'fan_home' => je($d['fan']['home']), 'fan_pw' => je($d['pw_fan']), 'fan_about' => je($d['about_fan']),
            'hero_video' => '1', 'robots_index' => '1', 'schema_version' => (string)SCHEMA_VERSION,
        ] + seo_seed_defaults();
        $st = $pdo->prepare('INSERT OR IGNORE INTO settings(k,v) VALUES(?,?)');
        foreach ($defaults as $k => $v) {
            $st->execute([$k, (string)$v]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
