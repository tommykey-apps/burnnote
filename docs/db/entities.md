# Entities

DynamoDB シングルテーブル `burnnote` には 1 種類のエンティティ `Note` のみが格納される。
ゼロ知識暗号化された one-time secret で、TTL によって最大 7 日で自動削除される。

## Note

ブラウザで AES-256-GCM 暗号化された秘密文。サーバーは鍵を保持せず、暗号文と IV のみ保持する。
受信者が `consume` した時点で deleteItem により原子的に削除される。

- **PK**: `id`
- **SK**: なし (シンプル PK のみ)
- **TTL**: `expires_at` 属性 (Unix timestamp) で DynamoDB が非同期削除

| Field | Type | Source |
|---|---|---|
| `id` | str (URL-safe base64, 16 bytes = 128 bit エントロピー) | [NoteRepository.php:11](https://github.com/tommykey-apps/burnnote/blob/main/api/app/Services/NoteRepository.php#L11) (`ID_BYTES = 16`), [NoteRepository.php:100](https://github.com/tommykey-apps/burnnote/blob/main/api/app/Services/NoteRepository.php#L100) (`generateId`) |
| `ciphertext` | str (AES-256-GCM 暗号文 base64) | [NoteRepository.php:26](https://github.com/tommykey-apps/burnnote/blob/main/api/app/Services/NoteRepository.php#L26) |
| `iv` | str (初期化ベクトル base64) | [NoteRepository.php:27](https://github.com/tommykey-apps/burnnote/blob/main/api/app/Services/NoteRepository.php#L27) |
| `created_at` | int (Unix timestamp) | [NoteRepository.php:28](https://github.com/tommykey-apps/burnnote/blob/main/api/app/Services/NoteRepository.php#L28) |
| `expires_at` | int (Unix timestamp、**DynamoDB TTL: 自動削除トリガー**) | [NoteRepository.php:29](https://github.com/tommykey-apps/burnnote/blob/main/api/app/Services/NoteRepository.php#L29) |

サンプルアイテム:
```json
{
  "id": "AbCdEfGhIjKlMnOp_-",
  "ciphertext": "base64-encoded-aes-256-gcm-ciphertext...",
  "iv": "base64-encoded-iv...",
  "created_at": 1746230400,
  "expires_at": 1746835200
}
```

## 設計意図

- **ゼロ知識暗号化**: 鍵は URL fragment (`#` 以降) に格納されサーバーに送信されない。サーバーは復号できない
- **TTL 自動削除**: `expires_at <= now` のアイテムは DynamoDB が **非同期** で削除する (即時ではなく数日のラグあり)
- **One-time read**: `consume` は `ConditionExpression: attribute_exists(id) AND expires_at > :now` 付き deleteItem で原子的に「読みかつ消す」
- **ID エントロピー**: 128 bit (16 bytes) ランダム → ブルートフォース耐性十分。`put` の `ConditionExpression: attribute_not_exists(id)` で衝突時に上書きを防ぐ
