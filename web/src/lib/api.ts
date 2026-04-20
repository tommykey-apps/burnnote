export type CreateResponse = { id: string; expires_at: number };
export type ReadResponse = { ciphertext: string; iv: string };

export async function createNote(
	ciphertext: string,
	iv: string,
	expiresIn: number
): Promise<CreateResponse> {
	const res = await fetch('/api/notes', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
		body: JSON.stringify({ ciphertext, iv, expires_in: expiresIn })
	});
	if (!res.ok) throw new Error(`create failed: ${res.status}`);
	return res.json();
}

export async function existsNote(id: string): Promise<boolean> {
	const res = await fetch(`/api/notes/${encodeURIComponent(id)}/exists`, {
		headers: { Accept: 'application/json' }
	});
	return res.ok;
}

export async function consumeNote(id: string): Promise<ReadResponse | 'gone'> {
	const res = await fetch(`/api/notes/${encodeURIComponent(id)}`, {
		headers: { Accept: 'application/json' }
	});
	if (res.status === 410 || res.status === 404) return 'gone';
	if (!res.ok) throw new Error(`read failed: ${res.status}`);
	return res.json();
}
