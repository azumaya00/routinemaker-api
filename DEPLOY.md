# デプロイ手順（mixhost）

本番の API は `https://routinemaker-api.yuru-labo.com` です。GitHub Actions はテストだけを回し、デプロイは SSH で手作業で行います。

## 前提

| 項目 | 値 |
|---|---|
| サーバー | mixhost（cPanel / LiteSpeed）、ホームは `/home/anwwtear` |
| アプリ | `/home/anwwtear/apps/routinemaker-api`（GitHub の public リポジトリを HTTPS で clone したもの。public_html の外） |
| ドキュメントルート | cPanel の設定は `public_html/routinemaker-api.yuru-labo.com/app/public`。これは `/home/anwwtear/apps/routinemaker-api/public` へのシンボリックリンク |
| PHP | Web は ea-php84（cPanel の MultiPHP マネージャーで設定し、`public/.htaccess` に反映される）、CLI も 8.4 |
| composer | `composer`（`/usr/local/bin/composer`） |

サーバー上で変更していて、Git にはないもの（消さないこと）:
- `public/.htaccess`：PHP ハンドラの指定を追記している
- `.env`（600）、`.htaccess`、`error_log`（600）
- `storage/` と `bootstrap/cache/` 配下の `.gitignore`：権限変更（mode change）だけ

上記があるので、`git reset --hard`、`git checkout .`、`git clean` は使わないでください。`public/.htaccess` の PHP 指定が消えます。

## 1. デプロイ前（手元）

1. main に push し、GitHub Actions の `tests` が成功していることを確認する。
2. 今回の変更に、次の 3 つが含まれるか確認する。
   - `database/migrations/` の追加：あれば手順 2-5 を実行する
   - `composer.lock` の変更
   - env キーの追加：あればサーバーの `.env` に先に追加する

## 2. デプロイ（サーバー）

```bash
cd /home/anwwtear/apps/routinemaker-api
```

2-1. 状態を確認する。上の「サーバー上で変更していて、Git にはないもの」以外が出たら止まる。

```bash
git status --short
```

2-2. ロールバック用に、今のコミットを記録する。

```bash
git rev-parse HEAD > ~/routinemaker-api.prev && cat ~/routinemaker-api.prev
```

2-3. 取り込む。fast-forward できないときは失敗するので、そこで止まる。

```bash
git pull --ff-only
```

2-4. 依存関係を入れる。

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

2-5. マイグレーションを実行する（マイグレーションが追加されたときだけ）。

```bash
php artisan migrate --force
```

2-6. 設定キャッシュを作り直す。

```bash
php artisan config:cache
```

## 3. デプロイ後の確認

1. `https://routinemaker-api.yuru-labo.com/api/health` が `{"status":"ok"}` を返す。
2. `https://routinemaker.yuru-labo.com` で次を確認する。
   - メールとパスワードでログイン、ログアウトできる
   - Google でログインできる
   - ルーティンの一覧と作成ができる
3. エラーが出ていないか確認する。

```bash
tail -n 50 storage/logs/laravel.log
```

## 4. ロールバック

```bash
cd /home/anwwtear/apps/routinemaker-api && git checkout "$(cat ~/routinemaker-api.prev)"
```

```bash
composer install --no-dev --optimize-autoloader --no-interaction && php artisan config:cache
```

- マイグレーションを実行していた場合、`migrate:rollback` はデータが消えることがあるので、実行する前に内容を確認してください。
- ロールバックすると HEAD が detached になります。直したあとは `git checkout main && git pull --ff-only` で戻します。

## 5. `.env` を変えたとき（秘密情報の再発行を含む）

```bash
cd /home/anwwtear/apps/routinemaker-api && chmod 600 .env && php artisan config:cache
```

- `.env` の値はチャットやログに出さないでください。確認はキー名だけで行います（`cut -d= -f1 .env`）。
- `APP_KEY` を変えると、全ユーザーが一度ログアウトされます。`APP_PREVIOUS_KEYS` に旧キーは入れません。
- `.env` のコピーを public_html の中に作らないでください（`scp` は手元の Mac で実行します）。

## 6. PHP のバージョンを変えるとき

cPanel の「MultiPHP マネージャー」で、`routinemaker-api.yuru-labo.com` の 1 行だけにチェックを入れてバージョンを選びます（他のドメインにチェックを入れないこと）。`public/.htaccess` の cPanel-generated handler ブロックが書き換わります。このブロックは手で編集しないでください。動かなければ元のバージョンに戻します。バージョンを上げる前に、`.github/workflows/tests.yml` の matrix に新しいバージョンを追加してテストを通しておきます。

## 任意: mode change を表示しないようにする

`git status` に権限変更が出ないようにするには、サーバーの `/home/anwwtear/apps/routinemaker-api` で一度だけ次を実行します（このリポジトリにだけ効きます）。

```bash
git config core.fileMode false
```

## 配置について（触らないこと）

- `public_html/routinemaker-api.yuru-labo.com/app/` には、`public` へのシンボリックリンクしか置かない（フォルダは 711）。アプリ本体を public_html に戻さないでください。
- `/home/anwwtear/apps/routinemaker-api` と、その中の `public` は `anwwtear:nobody` の 750 です。Web サーバーは `nobody` グループとしてたどるので、この 2 つを作り直したりコピーで置き換えたりしないでください（一般ユーザーは `chgrp nobody` ができません）。
