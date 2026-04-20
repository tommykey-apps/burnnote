<?php

namespace App\Http\Controllers;

use App\Services\NoteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(private readonly NoteRepository $repo) {}

    public function store(Request $request): JsonResponse
    {
        $maxBytes = (int) env('BURNNOTE_MAX_CIPHERTEXT_BYTES', 16384);
        $maxExpires = (int) env('BURNNOTE_MAX_EXPIRES_SEC', 604800);

        $validated = $request->validate([
            'ciphertext' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+={0,2}$/', "max:$maxBytes"],
            'iv'         => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+={0,2}$/', 'max:32'],
            'expires_in' => ['required', 'integer', 'min:60', "max:$maxExpires"],
        ]);

        $expiresAt = time() + $validated['expires_in'];
        $id = $this->repo->put($validated['ciphertext'], $validated['iv'], $expiresAt);

        return response()->json([
            'id'         => $id,
            'expires_at' => $expiresAt,
        ], 201);
    }

    public function exists(string $id): JsonResponse
    {
        $note = $this->repo->peek($id);

        if ($note === null) {
            return response()->json(['error' => 'not_found_or_expired'], 404);
        }

        return response()->json([
            'exists'     => true,
            'expires_at' => $note['expires_at'],
        ]);
    }

    public function consume(string $id): JsonResponse
    {
        $note = $this->repo->consume($id);

        if ($note === null) {
            return response()->json(['error' => 'gone'], 410);
        }

        return response()->json([
            'ciphertext' => $note['ciphertext'],
            'iv'         => $note['iv'],
        ]);
    }
}
