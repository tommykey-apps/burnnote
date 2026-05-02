# DynamoDB Schema Documentation

このディレクトリは burnnote の DynamoDB シングルテーブル設計のドキュメント。

| File | Owner | When updated |
|---|---|---|
| [`entities.md`](entities.md) | manual | 新エンティティ追加 / 属性変更時 |
| [`access-patterns.md`](access-patterns.md) | manual | 新クエリパターン追加時 |
| [`schema/`](schema/) | tbls (auto) | `make db-docs` で再生成 |

## ドキュメント生成

```bash
make db-docs        # DynamoDB Local 起動 + tbls 実行 + docs-db/schema/ 再生成
make db-docs-diff   # 現状の docs と live スキーマの差分表示
```

**`tbls doc` を素手で打たないこと。** `AWS_ENDPOINT_URL` を設定し忘れると本番 AWS DynamoDB
に誤接続する。本番テーブル名と Local が同じ `burnnote` なので誤接続が成功する可能性
がある。必ず `make db-docs` 経由で実行する。

## 更新ポリシー

`schema/` を再生成すべきタイミング:
- `infra/dynamodb.tf` の変更 (テーブル名 / KeySchema / TTL 設定)
- `api/app/Services/NoteRepository.php` の変更 (新フィールド追加・属性変更)

同じ PR で `entities.md` と `access-patterns.md` も手動更新すること。

## CI による drift 検出の限界

`.github/workflows/db-docs.yaml` は `tbls diff` でスキーマ drift を検出するが、
**手書きドキュメント (`entities.md` / `access-patterns.md`) の更新漏れは検出できない**。
CI が緑でも手書きドキュメントが古い可能性があるため、code review で確認する。

CI が捕捉できる範囲:
- PK の型変更
- テーブル追加 / 削除 / 名前変更
- `.tbls.yml` の comment 更新が `schema/` に反映されてない

## tbls の DynamoDB 出力の制約

tbls の DynamoDB driver は `DescribeTable` API しか呼ばないため、
`AttributeDefinitions` で宣言された属性 (このテーブルでは `id` のみ) しか
カラム化されない。`ciphertext` `iv` `created_at` `expires_at` は出力されない。
TTL 属性 (`expires_at`) も AttributeDefinitions に含まれないため tbls 出力に
反映されない。実アイテムの属性と TTL の挙動は `entities.md` を参照。
