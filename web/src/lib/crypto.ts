// Zero-knowledge AES-256-GCM encryption via WebCrypto API.
// The key never leaves the browser — it travels in the URL fragment (#).

const KEY_ALG = { name: 'AES-GCM', length: 256 } as const;

export type EncryptedPayload = {
	ciphertext: string;
	iv: string;
	key: string;
};

export async function encrypt(plaintext: string): Promise<EncryptedPayload> {
	const key = await crypto.subtle.generateKey(KEY_ALG, true, ['encrypt', 'decrypt']);
	const iv = crypto.getRandomValues(new Uint8Array(12));
	const enc = new TextEncoder();
	const buf = await crypto.subtle.encrypt(
		{ name: 'AES-GCM', iv: iv as BufferSource },
		key,
		enc.encode(plaintext) as BufferSource
	);
	const rawKey = new Uint8Array(await crypto.subtle.exportKey('raw', key));
	return {
		ciphertext: toBase64Url(new Uint8Array(buf)),
		iv: toBase64Url(iv),
		key: toBase64Url(rawKey)
	};
}

export async function decrypt(
	ciphertext: string,
	iv: string,
	keyBase64: string
): Promise<string> {
	const key = await crypto.subtle.importKey(
		'raw',
		fromBase64Url(keyBase64) as BufferSource,
		KEY_ALG,
		false,
		['decrypt']
	);
	const buf = await crypto.subtle.decrypt(
		{ name: 'AES-GCM', iv: fromBase64Url(iv) as BufferSource },
		key,
		fromBase64Url(ciphertext) as BufferSource
	);
	return new TextDecoder().decode(buf);
}

function toBase64Url(bytes: Uint8Array): string {
	let s = '';
	for (const b of bytes) s += String.fromCharCode(b);
	return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function fromBase64Url(s: string): Uint8Array {
	const pad = s.length % 4 === 0 ? '' : '='.repeat(4 - (s.length % 4));
	const b = atob(s.replace(/-/g, '+').replace(/_/g, '/') + pad);
	const out = new Uint8Array(b.length);
	for (let i = 0; i < b.length; i++) out[i] = b.charCodeAt(i);
	return out;
}
