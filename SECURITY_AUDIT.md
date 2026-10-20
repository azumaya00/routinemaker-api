# Security Audit: routinemaker-api

実施日: 2026-10-21
対象: このリポジトリ（Laravel API）と、本番の mixhost 上の配置。フロント（routinemaker-web / Vercel）は、API 連携に関わる範囲だけを確認した。

## 1. プロジェクト構成

| 項目 | 内容 | 確認方法 |
|---|---|---|
| フレームワーク | Laravel 12.45.0（composer.lock） | 確認済み |
| 認証 | Sanctum 4.2.2（SPA の Cookie セッション）、Socialite 5.24.1（Google ログイン） | 確認済み |
| PHP | composer.json は `^8.2`。本番は ea-php84（2026-10-21 に ea-php82 から切り替え） | サーバーで確認済み |
| DB | 本番は MySQL、テストは sqlite（in-memory） | 確認済み |
| フロント | Next.js（Vercel）。`https://routinemaker.yuru-labo.com` | 確認済み |
| API | `https://routinemaker-api.yuru-labo.com`（mixhost 共用サーバー、LiteSpeed） | 引き継ぎ情報による |
| ファイルアップロード | なし | 確認済み |
| CI/CD | なし（`.github/` なし）。本番の `app/` は public リポジトリの HTTPS clone で、SSH から `git pull` と `composer install --no-dev --optimize-autoloader` を手作業で実行してデプロイしている | サーバーの `git remote` と bash_history で確認 |
| リポジトリ | GitHub の public リポジトリ（azumaya00/routinemaker-api） | ユーザー申告と remote で確認 |

本番サーバーの配置（引き継ぎ情報）:
- ドキュメントルートは `/home/anwwtear/public_html/routinemaker-api.yuru-labo.com/app/public`。
- `app/` 全体が public_html の中にある。songofwhisper.com のドキュメントルートが public_html 直下なので、`https://songofwhisper.com/routinemaker-api.yuru-labo.com/app/...` という形で `app/` 配下にアクセスできた。

## 2. 監査対象と監査方法

- ルート、コントローラ、モデル、ミドルウェア、Artisan コマンド、config、テストを全件読んだ。
- Git の追跡状況と全履歴（23 コミット）を調べ、秘密情報のパターンを検索した。値は出力していない。
- ローカル（PHP 8.5.5）で `php artisan test` を実行した。
- Codex（read-only）に同じ範囲を独立に監査させ、結果を突き合わせた。
- 実施していないこと: 本番への HTTP リクエスト、本番 env の閲覧、`composer audit`（外部通信が必要）、侵入テスト。

## 3. 発見した問題

### C-1 本番 env ファイルが外部から取得できた（Critical / 対応済み）
- 内容: `app/routinemaker-api-production.env`（サーバー上で `scp` を実行した結果できた `.env` のコピーとみられる）が songofwhisper.com 経由で HTTP 200 で取得できた（2026-02-14 頃から 2026-10-21 まで）。
- 根拠: 引き継ぎ情報（songofwhisper.com の監査中に発覚）。アクセスログに取得の記録はないが、ログの保持期間外の取得は否定できない。
- 影響: DB 接続情報、APP_KEY、Google OAuth のシークレット、メールの認証情報などが漏えいした可能性がある。APP_KEY が漏れると、暗号化 Cookie を偽造される恐れがある。
- 対応: chmod 600 で 403 にした。2026-10-21 に `GOOGLE_CLIENT_SECRET`、`MAIL_PASSWORD`（noreply@yuru-labo.com）、`DB_PASSWORD`（anwwtear_moto）、`APP_KEY` を再発行した（対応済み）。REDIS と AWS の値は空だったので対象外。

### C-2 退会済みアカウントを、登録 API からメールアドレスだけで復元できた（Critical / 修正済み）
- 場所: `app/Http/Controllers/Auth/RegisteredUserController.php`（修正前の 42〜53 行）
- 内容: 退会済みユーザーと同じメールアドレスで `POST /register` すると、`restore()` され、送ったパスワードで上書きされてログイン状態になった。
- 悪用条件: 退会済みユーザーのメールアドレスを知っていること。メールの受信は不要。旧データ、プラン、`is_admin` をそのまま引き継げる。
- 補足: リポジトリが public なので、この挙動は誰でも読める状態だった。

### H-1 `app/` が public_html の中にあった（High / 対応済み）
- 内容: ドキュメントルートは `app/public` だが、`app/` 自体が public_html の中にある。songofwhisper.com のホスト経由で `app/` 配下のファイルに届く。
- 影響: `.env` 系は 403（ドットファイル既定、chmod 600）。一方で `storage/logs/laravel.log`（中身はスタックトレースとサーバーのパス）、`app/error_log`、`vendor/`、`composer.lock` などは見えていた可能性がある。`laravel.log` と `error_log` は 2026-10-21 に chmod 600 にした。
- 対応: songofwhisper.com 側に RewriteRule を追加した。2026-10-21 に、`songofwhisper.com/routinemaker-api.yuru-labo.com/app/composer.json` と `.../app/DEPLOY.md`、`songofwhisper.com/catparty.net/` が 403 になり、`songofwhisper.com/` と API のヘルスチェックが 200 のままであることを確認した。2026-10-21 に、アプリ本体を `mv` で `/home/anwwtear/apps/routinemaker-api` に移した。`public_html/routinemaker-api.yuru-labo.com/app/public` はそこへのシンボリックリンクにした（hample.yuru-labo.com と同じ方式）。移動後、`songofwhisper.com/routinemaker-api.yuru-labo.com/app/composer.json` は 403、API のヘルスチェックは 200、ブラウザでのログインも問題ないことを確認した。
- 補足: 移動後は、`songofwhisper.com/routinemaker-api.yuru-labo.com/app/public/index.php` が 200 を返すようになった（Laravel の入口に別ホスト名で届く）。リンク先の実体が public_html の外にあるため、songofwhisper.com 側の RewriteRule が適用されないとみられる。届くのは API として公開しているものと同じで、URL は `APP_URL` と `FRONTEND_URL` から作るので Host ヘッダーには依存しない。Cookie のドメインも `.yuru-labo.com` なので送られない。このため Info として許容した。hample.yuru-labo.com も同じ構成。

### H-2 Google OAuth で state を検証していなかった（High / 修正済み）
- 場所: `app/Http/Controllers/Auth/GoogleAuthController.php:21,30`
- 内容: `stateless()` によって state の照合が無効になっていた。
- 影響: 攻撃者が用意した認可コードのコールバックに利用者を誘導し、攻撃者のアカウントでログインさせる login CSRF が成立しうる。

### M-1 Google ログインで、メールが一致するだけで退会済みユーザーを復元していた（Medium / 修正済み）
- 場所: `GoogleAuthController.php`（修正前の 79〜108 行）
- 内容: `provider_id` が一致しなくても、退会済みの Google ユーザーとメールが一致すれば復元し、`provider_id` を上書きしていた。

### M-2 認証系エンドポイントにレート制限がなかった（Medium / 修正済み）
- 場所: `routes/web.php`（login / register / Google）、`routes/api.php`（forgot / reset）
- 影響: パスワードの総当たり、メール送信の濫用。

### M-3 パスワード再設定後も、他の端末のセッションが有効なままだった（Medium / 修正済み）
- 場所: `app/Http/Controllers/Auth/ResetPasswordController.php`
- 影響: セッションを盗まれていた場合、パスワードを変えてもアクセスが続く。

### M-4 本番の `MAIL_MAILER=log` だと、リセット URL がログに残る（該当なし）
- 場所: `config/mail.php:17`（既定値が `log`）
- 本番の値を確認したところ `MAIL_MAILER=smtp` だったので、該当しない。

### M-5 Cookie ドメインを共有している（Medium / 仕様として許容）
- `ENV_MATRIX.md` では本番の `SESSION_DOMAIN=.yuru-labo.com` を推奨している。同じサーバーにある `yuru-labo.com` と `hample.yuru-labo.com` にも、routinemaker のセッション Cookie と XSRF-TOKEN が送られる。
- 悪用条件: どちらかのサイトが侵害されること。本番は `SESSION_DOMAIN=.yuru-labo.com`。フロントが XSRF-TOKEN を読むには、API と共通の親ドメインが必要なので、これより狭くできない。同じドメインのサイトはどれも自分で管理しているので、許容した。

### M-6 Cookie に Secure 属性が付いていなかった（Medium / 対応済み）
- 内容: 本番 `.env` に `SESSION_SECURE_COOKIE` がなく、セッション Cookie に Secure 属性が付いていなかった。
- 対応: 2026-10-21 に `SESSION_SECURE_COOKIE=true` を追加し、ブラウザで Secure 属性が付くことを確認した。あわせて `LOG_LEVEL` を `debug` から `warning` に変えた。

### L-1 タスク文字列に長さの上限がなかった（Low / 修正済み）
- 場所: `app/Http/Controllers/Api/RoutineController.php:96`

### L-2 tinker の出力ファイル `er` がコミットされていた（Low / 対応済み・履歴には残る）
- 内容: ローカル開発用ユーザー（`you@example.com`）の bcrypt ハッシュ。本番に同じアカウントがなければ実害はない。

### L-3 `.env.docker` を Git で追跡している（Low / 未対応）
- 内容: ローカルの Docker 用のダミーパスワード。本番で同じ値を使っていなければ実害はない。

### L-4 登録 API で、メールアドレスが登録済みかどうか分かる（Low / 未対応・仕様として許容）
- 場所: `RegisteredUserController.php:36`
- UX との兼ね合いで現状のままとした。

### Info
- IDOR: ルーティン、履歴、設定はすべて `user_id` で絞っている。問題なし。
- Mass Assignment: `is_admin` は `$fillable` に含まれていない。
- CORS: 対象パスを限定し、許可オリジンは本番ドメインを既定値にしている。
- 生 SQL: なし。
- オープンリダイレクト: リダイレクト先は `FRONTEND_URL` で固定。
- 管理者の付与・剥奪は Artisan コマンドからのみ。HTTP 経由では変更できない。
- APP_KEY の用途: 暗号化 Cookie（セッション、XSRF-TOKEN）だけ。`encrypted` キャスト、`Crypt::`、remember me は使っていない。

### Not Applicable
- ファイルアップロード、GitHub Actions、Astro / Gatsby の項目。

### Unverified
- 本番の HTTP セキュリティヘッダー
- `composer audit --locked` の結果
- DB ユーザーの権限と、外部からの接続可否
- `app/.git/` に songofwhisper.com 経由で届くか（リポジトリは public なので、中身が見えても影響は小さい）
- `~/apps/routinemaker-api` と `~/tmp/routinemaker-api-pre` の用途（どちらも本番のドキュメントルートではない）

## 4. リスク評価

最優先は C-1（秘密情報の漏えい）と C-2（アカウント乗っ取り）。C-2 はコード修正済みだが、本番に反映するまでは悪用できる状態が続く。H-1 は対応済み。

## 5. 推奨する対策

1. 修正版を本番へデプロイする（C-2、H-2、M-1〜M-3、L-1）。2026-10-21 に完了。
2. 秘密情報を再発行する（C-1）。2026-10-21 に完了。
3. `app/` を public_html の外へ移す（H-1）。2026-10-21 に完了。
4. PHP を ea-php84 に切り替える（8.2 のセキュリティサポートは 2026-12-31 に終了）。2026-10-21 に完了。
5. 本番 env の設定値を確認する。2026-10-21 に完了（M-4 は該当なし、M-6 は対応済み）。
6. `.env.docker` をサンプル化する（L-3）。`er` は削除済み。

## 6. 修正済み項目（未コミット）

| 項目 | 変更 |
|---|---|
| C-2 | 退会済みのメールアドレスでの登録は、422「このメールアドレスは退会済みです。パスワード再設定から復元してください。」を返す。復元しない |
| C-2 関連 | `POST /api/forgot-password` は、退会済みユーザーにもリセットメールを送る（再送の間隔は auth.php の throttle に従う。応答は常に同じ 200）。`POST /api/reset-password` は、有効なトークンなら退会済みユーザーを復元してパスワードを設定する。メールの所有をトークンで確認するので、本人だけが復元できる |
| H-2 | `stateless()` を削除。Socialite 標準の state 検証を使う |
| M-1 | メール一致による復元を廃止し、422 を返す。`provider_id` 一致による復元は維持 |
| M-2 | login / register / Google の redirect と callback に `throttle:10,1`、forgot / reset に `throttle:5,1` |
| M-3 | リセット成功時に remember_token を更新し、Sanctum トークンを削除する。DB セッションドライバのときは、そのユーザーのセッション行も削除する |
| L-1 | `tasks.*` に `max:255` |
| テスト | `tests/Feature/Api/AuthSecurityTest.php` を追加（9 件）。`RoutineTest` にタスク長のテストを追加。`MeTest` の退会テストを実装（204）に合わせて修正 |

既存機能への影響:
- 退会済みのメールアドレスで登録すると、エラーになる。復元はパスワード再設定から行う。
- 短時間に試行を繰り返すと 429 が返る。フロントは 429 専用の表示を持っていない。
- Google コールバックのエラー（422 / 400）は、従来どおり API ドメイン上に JSON で表示される。

## 7. 未対応項目

- L-3、L-4
- 退会時に他の端末のセッションを失効させる処理（リセット時だけ対応した）
- パスワードを持たない Google ユーザーの退会時の再認証
- HTTP セキュリティヘッダー（本番の実際のヘッダーを確認してから判断する）
- フロントでの 429 表示

## 8. 手動対応が必要な項目

1. **秘密情報の再発行**（Google Cloud Console、mixhost の cPanel、メールサービス）
   - `APP_KEY`: `php artisan key:generate --show` で新しい値を作り、本番 env を書き換える。`APP_PREVIOUS_KEYS` には旧キーを入れない（漏えいしたキーが有効なまま残るため）。影響は全ユーザーが一度ログアウトされることだけ。
   - `DB_PASSWORD`: cPanel の MySQL ユーザーのパスワードを変更し、env を更新する。
   - `GOOGLE_CLIENT_SECRET`: Google Cloud Console でシークレットを再発行し、env を更新する。
   - `MAIL_PASSWORD` など、env にある外部サービスの認証情報。
   - 更新後に `php artisan config:clear`（`config:cache` を使っているなら `config:cache`）を実行する。
2. **`storage/logs` の確認**: songofwhisper.com 経由で見えていた可能性がある。中にリセット URL や個人情報がないか確認する。
3. **配置の見直し**: 2026-10-21 に完了（H-1 を参照）。
4. **PHP 8.4 への切り替え**: `app/public/.htaccess` のハンドラを ea-php84 に変える。lock 内の全パッケージの PHP 制約は 8.4 を許可している。ローカルの PHP 8.5 でテストは成功した。
5. **`composer audit --locked` の実行**（外部通信が許可された環境で）。
6. **バックアップ等の扱い**: 2026-10-21 に削除した（`app.bak_20260214014904/` の中身は cPanel が作った初期の `.htaccess` と空の `.well-known/` だけ。`routinemaker-api-production.env` は `.env` と同じ内容のコピー）。
7. **Google Cloud Console**: リダイレクト URI が本番のコールバック URL だけになっているか確認する。

## 9. 実施した検証と結果

| 検証 | 結果 |
|---|---|
| 修正前の `php artisan test`（PHP 8.5.5） | 26 件成功、1 件失敗（MeTest の退会テスト。もともとの不整合） |
| 修正後の `php artisan test`（PHP 8.5.5） | 37 件すべて成功（131 assertions） |
| Git 履歴の秘密情報検索 | 実値は検出されなかった（`.env.example` の `MAIL_PASSWORD` は既定値の `null`） |
| 本番での動作確認 | 未実施（デプロイ前） |
| Google ログインの実ブラウザ確認 | 未実施。フロントはページ遷移で開始するので、state 検証はセッション Cookie で動く想定 |
| PHP 8.2 / 8.4 でのテスト | 未実施（ローカルは 8.5） |

## 10. 今後の保守で確認すべき項目

- デプロイ後、Google ログイン、退会、再設定による復元を本番で一通り確認する。
- 公開範囲: 新しいドメインを public_html に追加するたびに、songofwhisper.com 経由で見えないか確認する。
- `composer audit` を定期的に実行する。Laravel 12 のセキュリティサポート期限を確認する。
- PHP のサポート期限: 8.4 のセキュリティサポートは 2028-12-31 まで。
- 本番では `APP_DEBUG=false` を維持し、`MAIL_MAILER` は配送用のものにする。
- `storage/logs` の保持期間と、中身に秘密情報が入っていないか。
- public リポジトリに、env、tinker 出力、ダンプをコミットしない。

## 11. 対応記録（2026-10-21）

| 対応 | 結果 |
|---|---|
| コード修正のデプロイ（`e1a328d`） | `git pull --ff-only` と `config:cache` を実行。ヘルスチェック、メールと Google でのログイン、ルーティン一覧を確認した |
| GitHub Actions `tests` | PHP 8.2 / 8.4 で成功 |
| `GOOGLE_CLIENT_SECRET` | 新しいシークレットを追加して差し替え、旧シークレットを削除。Google ログインを確認した |
| `MAIL_PASSWORD` | cPanel でパスワードを変更。リセットメールが届くことを確認した |
| `DB_PASSWORD` | `anwwtear_moto`（routinemaker の DB 専用）のパスワードを変更。`migrate:status` とブラウザで確認した |
| `APP_KEY` | `key:generate --force` で再生成。全ユーザーが一度ログアウトされた。再ログインを確認した |
| `SESSION_SECURE_COOKIE=true`、`LOG_LEVEL=warning` | 追加・変更した。Cookie の Secure 属性を確認した |
| `storage/logs/laravel.log`、`app/error_log` | chmod 600 |
| PHP | MultiPHP マネージャーで ea-php82 から ea-php84 に切り替え。ヘルスチェック、ログイン、ルーティン一覧を確認した。error_log に `session.gc_divisor must be greater than 0` という起動時の警告が出るが、Web 側の PHP（サーバー既定の設定）によるもので、Laravel は PHP 標準のセッションを使わないので影響はない |
| 配置の変更 | アプリ本体を `/home/anwwtear/apps/routinemaker-api` に移し、ドキュメントルートをシンボリックリンクにした。最初にコピーで試したところ、コピー先のグループが `nobody` にならず 404 になったのでロールバックした。そのあと `mv` で移し直した |
| 古いファイルの削除 | `routinemaker-api-production.env`、`~/routinemaker-api.env.bak`、`app.bak_20260214014904/` |
