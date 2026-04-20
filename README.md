# burnnote

1 回読んだら消える秘密共有サービス。ブラウザ側で暗号化 (AES-256-GCM)、サーバーは暗号文しか知らないゼロ知識設計。

## 構成図

後日 [docs/architecture.drawio](docs/architecture.drawio) に追加予定。

## 使った技術

| | |
|---|---|
| バックエンド | PHP 8.4 + Laravel 13 + Bref 3 (Lambda, arm64) |
| フロント | SvelteKit 2, Svelte 5, Tailwind CSS v4, shadcn-svelte |
| DB | DynamoDB (On-Demand + TTL) |
| 暗号化 | AES-256-GCM (WebCrypto API)、鍵は URL fragment |
| コンピュート | Lambda (arm64, php-84-fpm Bref layer) |
| API | API Gateway HTTP API |
| IaC | Terraform (S3 backend + DynamoDB state lock) |
| CI/CD | GitHub Actions (setup-php + pnpm + flox) |
| 配信 | CloudFront (S3 + API Gateway デュアルオリジン, PriceClass_200) |

## 使ってるAWSサービス

| サービス | 役割 |
|---------|------|
| Lambda              | Laravel API をサーバーレス実行 (arm64、Bref layer)        |
| API Gateway (HTTP)  | HTTP リクエストを Lambda にルーティング                    |
| DynamoDB            | ノートの暗号文保存、TTL で自動削除                         |
| CloudFront          | SPA + API の配信、SSL 終端                                 |
| S3                  | SvelteKit 静的ファイル配信                                 |
| Route53             | `burnnote.tommykeyapp.com` の DNS                          |
| ACM                 | ワイルドカード証明書 `*.tommykeyapp.com`                   |
| SSM Parameter Store | CI/CD から参照する CloudFront ID / バケット名              |
| CloudWatch Logs     | Lambda ログ (保持期間 7 日)                                |
| IAM                 | Lambda 実行ロール (DynamoDB CRUD)                          |

## 使い方

1. トップページで平文を入力、期限を選ぶ
2. ブラウザ内で AES-256-GCM 暗号化 → 暗号文のみサーバーへ
3. 発行 URL の fragment (`#` 以降) に鍵が埋め込まれる (サーバーには届かない)
4. 共有相手が URL を開くと暗号文を受け取り即削除 → ブラウザで復号
5. 2 度目のアクセスは 410 Gone

## 開発

```bash
flox activate
docker compose up -d            # DynamoDB Local
cd api && composer install && php artisan serve --port=8080 &
cd web && pnpm install && pnpm dev
```
