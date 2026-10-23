# Laravel 版からの移行記録

routinemaker の API を、mixhost 上の Laravel から Vercel 上の Hono（TypeScript）へ移す作業の記録。
mixhost で PHP の緊急脆弱性対応が続いたことをきっかけに、routinemaker は PHP をやめる方針にした（2026-10）。
Laravel の実績ははんぷる（hample.yuru-labo.com）で残す。

## 決めたこと

- 移行先の API: Hono を Vercel Functions で動かす（詳細は設計書で決める）
- ORM: Drizzle
- DB: Neon（PostgreSQL）
  - TiDB Starter と比較した。移すデータが少なく型変換の手間が小さいこと、無料枠でも過去6時間の任意の時点に戻せることから Neon にした
- 既存の利用者は、本人以外は Google ログインなので、パスワードハッシュの引き継ぎは本人のアカウントだけが対象になる

## 戻すための記録（2026-10-23 時点）

### コード

API と画面の両リポジトリに、Laravel 版の最終状態として `laravel-final` タグを付けて push した。

| リポジトリ | タグが指すコミット |
|---|---|
| azumaya00/routinemaker-api | e28f241 |
| azumaya00/routinemaker-web | f50b2f8 |

### データ

- phpMyAdmin のエクスポート（SQL）で旧 DB をまるごと保存した
- 保存先はリポジトリの外（作業用 Mac の `~/mockcode/app/routinemaker-backup/`）。個人情報を含むので、リポジトリには入れない

### 環境

- DB: MariaDB 10.11.19
- PHP: 8.4.27
- 環境変数のバックアップ: サーバー上の `~/routinemaker-api.env.bak`（以前の作業で作成）
- メール送信: mixhost の SMTP（465番、送信元 noreply@yuru-labo.com）

### メール送信先の修正（2026-10-23）

yuru-labo.com のネームサーバーを Cloudflare に移し、ドメイン本体を Workers のサイトに向けたことで、`MAIL_HOST=yuru-labo.com` ではメールサーバーにつながらなくなった。
サーバーの .env を `MAIL_HOST=ik10004.mixhost.jp`（MX レコードの向き先）に書き換えた。書き換え前の .env は `~/routinemaker-api.env.bak-20261023`。
はんぷるは `hample.yuru-labo.com`（mixhost を向いたまま）を使っているので影響はない。

## Neon へ移すデータ

移すのは users、user_settings、routines、histories の4テーブル。
password_reset_tokens と、Laravel だけが使うテーブル（sessions、cache、cache_locks、jobs、job_batches、failed_jobs、personal_access_tokens、migrations）は移さない。

型の変換:

| MariaDB | PostgreSQL |
|---|---|
| `bigint UNSIGNED`（自動採番） | `bigint` の identity 列。移したあと採番の続きを最大値に合わせる |
| `tinyint(1)` | `boolean` |
| `longtext ... CHECK (json_valid(...))`（tasks） | `jsonb` |
| `timestamp` | `timestamptz` |

外部キー（routines・histories・user_settings → users、histories → routines、いずれも削除時に連動）は作り直す。

### 戻す先についての補足

`laravel-final` を付けたあと、2026-10-23 に依存パッケージをセキュリティ修正版に更新して、本番にデプロイした（8bba854）。
Laravel 版に戻すときは、タグではなく main の最新を使う。

## 新しい API（2026-10-23 時点）

- コード: routinemaker-web リポジトリの `feat/hono-api` ブランチ（GitHub には `staging` として push 済み）
- 設計書と計画: routinemaker-web の `docs/superpowers/specs/2026-10-23-hono-api-design.md`、`docs/superpowers/plans/2026-10-23-hono-api.md`
- Google ログインは、候補だった arctic に npm 側で「Package no longer supported」の表示が付いていたため、パッケージを使わず fetch で書いた
- 実装後にセキュリティレビューを行い、指摘（退会時のセッション削除、回数制限の競合、入力の上限など）を直した

### 用意した環境

| 項目 | 本番 | 確認用 |
|---|---|---|
| URL | routinemaker.yuru-labo.com | staging.routinemaker.yuru-labo.com |
| Neon のブランチ | 本番用（テーブルのみ、データは空） | staging（旧 DB のデータを移行スクリプトで投入済み） |
| Vercel | Production | Preview（staging ブランチ、Vercel の保護あり） |

- Neon のプロジェクトは AWS Asia Pacific (Singapore)。Vercel の Function Region も Singapore (sin1) に合わせた
- 環境変数は Vercel の Production と Preview に分けて入れた（すべて Sensitive）。本番の `NEXT_PUBLIC_API_BASE_URL` は今の画面が使っているので、切り替えまで残す
- Google Cloud Console の承認済みリダイレクト URI に、本番と確認用の `/api/auth/google/callback` を追加した。Laravel 版の URI は切り替えまで残す

### 確認用の環境で確かめたこと

- 登録、ログイン（大文字まじりのメールアドレス）、ログアウト、退会
- ルーティンの作成・実行・完了・中断、履歴の一覧と詳細、設定の変更
- 他人のルーティン・履歴が見えないこと、管理者ページが拒否されること
- 既存の利用者としての Google ログイン、パスワード再設定メールの送受信とリンク先
- 途中で見つかった不具合: Vercel 上で本文のない POST が 500 になった（NextRequest を hono の bodyLimit が作り直せなかった）。Next.js から Hono に渡す前に、標準の Request に組み立て直して直した

## 切り替え（2026-10-23 実施）

1. 旧 API を `php artisan down` でメンテナンスモードにした（503 を返す）
2. phpMyAdmin で最新のダンプ（14:40）を取り、`~/mockcode/app/routinemaker-backup/anwwtear_routinemaker_api_cutover.sql` に保存した。件数は朝と同じ（users 8、user_settings 8、routines 7、histories 17）
3. Neon の本番用ブランチに移行スクリプトで流し、件数が一致した
4. feat/hono-api を main に早送りで push し（9fe1d56）、Vercel の本番に出した
5. 本人のアカウントと Google ログインで、本番の動作を確かめた
6. routinemaker-api.yuru-labo.com の A レコードを Cloudflare のプロキシ経由にし、Redirect Rule で https://routinemaker.yuru-labo.com へ 301 転送した

旧 API はメンテナンスモードのまま残している。戻す必要が出たら、`php artisan up` で再開できる（ただし切り替え後に新しい API で増えたデータは、旧 DB には入っていない）。

## 残りの後片付け

しばらく様子を見てから行う。

- Vercel の本番の環境変数から `NEXT_PUBLIC_API_BASE_URL` を消す
- mixhost 上の routinemaker-api のファイルと DB を消す（消す前に最終のダンプを取る）
- Cloudflare の routinemaker-api の A レコードと Redirect Rule を消す
- Google Cloud Console の旧リダイレクト URI（routinemaker-api.yuru-labo.com）を消す
- Neon の staging ブランチ、Vercel の確認用ドメイン、Cloudflare の staging.routinemaker の CNAME を消す
