# Access Patterns

burnnote はシンプルな PK のみテーブルで、3 つのオペレーションのみ。

## 一覧

| # | Use case | API | PK | Filter / Condition | Source |
|---|---|---|---|---|---|
| 1 | put (新規作成) | `POST /api/notes` | `id` (生成) | `ConditionExpression: attribute_not_exists(id)` で衝突時に上書き防止 | [NoteRepository.php:18](../api/app/Services/NoteRepository.php#L18) |
| 2 | peek (削除なし、UI プレビュー用) | `GET /api/notes/{id}/exists` | `id` (eq, ConsistentRead) | post-filter `expires_at <= time()` で null | [NoteRepository.php:40](../api/app/Services/NoteRepository.php#L40) |
| 3 | consume (1 回読み + 削除) | `GET /api/notes/{id}` | `id` (eq) | `ConditionExpression: attribute_exists(id) AND expires_at > :now` で deleteItem (`ReturnValues: ALL_OLD` で読み取り) | [NoteRepository.php:71](../api/app/Services/NoteRepository.php#L71) |

## ConsistentRead の使い分け

- `peek` は **ConsistentRead** ([NoteRepository.php:45](../api/app/Services/NoteRepository.php#L45))。直前の `put` 直後でも確実に取得できるよう strong read
- `consume` は ConditionExpression 付き deleteItem なので暗黙的に strong consistent

## Anti-patterns / Known concerns

### A1. TTL の eventual deletion ラグ
DynamoDB の TTL は **48 時間以内** にアイテムを削除する (即時ではない)。よって `peek` / `consume`
時点で `expires_at <= now` のアイテムが DynamoDB 上にまだ残っている可能性がある。
Application 側でも `expires_at <= time()` 判定 ([NoteRepository.php:55](../api/app/Services/NoteRepository.php#L55))
を行い、TTL 削除を待たずに「期限切れ」を扱う。

### A2. consume の ConditionalCheckFailedException で「消費済 / 期限切れ / 不在」を区別しない
[NoteRepository.php:84](../api/app/Services/NoteRepository.php#L84) で `ConditionalCheckFailedException`
を全て null に変換 ([NoteRepository.php:85](../api/app/Services/NoteRepository.php#L85))。これは **意図的**:
- 「存在するが消費済」と「不在」を区別すると、攻撃者が ID 列挙によって過去の存在を推測できる (情報漏洩)
- 「期限切れ」と「不在」も同様に区別しない

### A3. ID 衝突防止
`put` は `ConditionExpression: attribute_not_exists(id)` ([NoteRepository.php:31](../api/app/Services/NoteRepository.php#L31))。
ID は `Random\Randomizer` の 16 bytes (128 bit エントロピー) なので衝突確率は実質 0 だが、
万一の衝突時に既存データを上書きしないよう ConditionExpression で防御。

### A4. 暗号文サイズ上限
DynamoDB アイテム上限は 400 KB。`ciphertext + iv + その他` で 400 KB を超えるとアイテムが書けない。
平文サイズの上限を Web 側で警告するなどの UX で対応する想定 (現状は API 側で明示的なバリデーションなし)。
