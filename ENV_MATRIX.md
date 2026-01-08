# ENV Matrix (local vs production)

## routinemaker-api (Laravel)

### 必須設定（本番で必ず設定）

| Key | local (.env / .env.docker) | production (mixhost) | Used by | Notes |
|---|---|---|---|---|
| APP_ENV | local | production | Laravel | 本番では必ず production に設定 |
| APP_DEBUG | true | false | Laravel | 本番では必ず false に設定 |
| APP_URL | http://localhost:8001 | https://routinemaker-api.yuru-labo.com | Laravel | API のベース URL |
| APP_KEY | (ランダム文字列) | (ランダム文字列) | Laravel | 本番では必ず再生成 |
| SESSION_DOMAIN | (未設定 or localhost) | .yuru-labo.com | Cookie | サブドメイン間で Cookie 共有するため必須 |
| SESSION_SECURE_COOKIE | false | true | Cookie | HTTPS 前提のため必須 |
| SESSION_SAME_SITE | lax | lax | Cookie | クロスサイト Cookie の動作制御 |
| SANCTUM_STATEFUL_DOMAINS | (未設定可、デフォルトで localhost 系) | routinemaker.yuru-labo.com | Sanctum | 本番では必ず設定（複数指定時はカンマ区切り） |
| CORS_ALLOWED_ORIGINS | (未設定可、デフォルトで localhost:3000) | https://routinemaker.yuru-labo.com | CORS | 本番では必ず設定（複数指定時はカンマ区切り） |

### データベース設定

| Key | local (.env / .env.docker) | production (mixhost) | Used by | Notes |
|---|---|---|---|---|
| DB_CONNECTION | mysql | mysql | DB |  |
| DB_HOST | db | (mixhost の DB host) | DB | mixhost 管理画面で確認 |
| DB_PORT | 3306 | (mixhost の DB port) | DB | 通常は 3306 |
| DB_DATABASE | routinemaker | (本番 DB 名) | DB |  |
| DB_USERNAME | rm | (本番 DB ユーザー名) | DB |  |
| DB_PASSWORD | rm | (本番 DB パスワード) | DB |  |

### ログ設定

| Key | local (.env / .env.docker) | production (mixhost) | Used by | Notes |
|---|---|---|---|---|
| LOG_CHANNEL | stack | stack or single | Logging | mixhost 運用方針に合わせて設定 |
| LOG_LEVEL | debug | info or warning | Logging | 本番では info 以上を推奨 |

### その他（未使用の可能性あり）

| Key | local (.env / .env.docker) | production (mixhost) | Used by | Notes |
|---|---|---|---|---|
| FRONTEND_URL | http://localhost:3000 | https://routinemaker.yuru-labo.com | (未使用の可能性) | 使っていなければ削除可 |

## routinemaker-web (Next.js)

| Key | local | production (Vercel) | Used by | Notes |
|---|---|---|---|---|
| NEXT_PUBLIC_API_BASE_URL | http://localhost:8001 | https://routinemaker-api.yuru-labo.com | src/lib/api.ts | 本番では必ず設定（Vercel 環境変数で設定） |

## 設定ファイルの動作

### config/cors.php
- `APP_ENV=production` かつ `CORS_ALLOWED_ORIGINS` 未設定の場合、デフォルトで `https://routinemaker.yuru-labo.com` を使用
- 本番では `CORS_ALLOWED_ORIGINS` を明示的に設定することを推奨

### config/session.php
- `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE` は env から読み取る
- 本番では必ず設定すること

### config/sanctum.php
- `APP_ENV=production` かつ `SANCTUM_STATEFUL_DOMAINS` 未設定の場合、デフォルトで `routinemaker.yuru-labo.com` を使用
- 本番では `SANCTUM_STATEFUL_DOMAINS` を明示的に設定することを推奨