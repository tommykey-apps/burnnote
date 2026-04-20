<?php

namespace App\Services;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Random\Randomizer;

class NoteRepository
{
    private const ID_BYTES = 16;

    public function __construct(
        private readonly DynamoDbClient $client,
        private readonly string $table,
    ) {}

    public function put(string $ciphertext, string $iv, int $expiresAt): string
    {
        $id = $this->generateId();

        $this->client->putItem([
            'TableName' => $this->table,
            'Item' => [
                'id'         => ['S' => $id],
                'ciphertext' => ['S' => $ciphertext],
                'iv'         => ['S' => $iv],
                'created_at' => ['N' => (string) time()],
                'expires_at' => ['N' => (string) $expiresAt],
            ],
            'ConditionExpression' => 'attribute_not_exists(id)',
        ]);

        return $id;
    }

    /**
     * @return array{ciphertext: string, iv: string, expires_at: int}|null
     */
    public function peek(string $id): ?array
    {
        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key' => ['id' => ['S' => $id]],
            'ConsistentRead' => true,
        ]);

        if (! isset($result['Item'])) {
            return null;
        }

        $item = $result['Item'];
        $expiresAt = (int) $item['expires_at']['N'];

        if ($expiresAt <= time()) {
            return null;
        }

        return [
            'ciphertext' => $item['ciphertext']['S'],
            'iv'         => $item['iv']['S'],
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Atomically read + delete. Returns null if not found or expired or already consumed.
     *
     * @return array{ciphertext: string, iv: string}|null
     */
    public function consume(string $id): ?array
    {
        try {
            $result = $this->client->deleteItem([
                'TableName' => $this->table,
                'Key' => ['id' => ['S' => $id]],
                'ConditionExpression' => 'attribute_exists(id) AND expires_at > :now',
                'ExpressionAttributeValues' => [
                    ':now' => ['N' => (string) time()],
                ],
                'ReturnValues' => 'ALL_OLD',
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                return null;
            }
            throw $e;
        }

        if (! isset($result['Attributes'])) {
            return null;
        }

        return [
            'ciphertext' => $result['Attributes']['ciphertext']['S'],
            'iv'         => $result['Attributes']['iv']['S'],
        ];
    }

    private function generateId(): string
    {
        $bytes = (new Randomizer())->getBytes(self::ID_BYTES);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
