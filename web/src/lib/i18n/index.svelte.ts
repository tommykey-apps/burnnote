import en from './en.json';
import ja from './ja.json';

export type Locale = 'en' | 'ja';

const messages = { en, ja } as const;

const STORAGE_KEY = 'burnnote-locale';

type MessageNode = string | { [k: string]: MessageNode };

let locale = $state<Locale>('en');

export function getLocale(): Locale {
	return locale;
}

export function setLocale(next: Locale): void {
	locale = next;
	if (typeof window !== 'undefined') {
		try {
			localStorage.setItem(STORAGE_KEY, next);
		} catch {
			// ignore storage errors
		}
		document.documentElement.lang = next;
	}
}

export function initLocale(): void {
	if (typeof window === 'undefined') return;
	let stored: string | null = null;
	try {
		stored = localStorage.getItem(STORAGE_KEY);
	} catch {
		// ignore
	}
	if (stored === 'en' || stored === 'ja') {
		setLocale(stored);
		return;
	}
	const nav = navigator.language?.toLowerCase() ?? '';
	setLocale(nav.startsWith('ja') ? 'ja' : 'en');
}

export function t(key: string, params?: Record<string, string | number>): string {
	const parts = key.split('.');
	let node: MessageNode = messages[locale];
	for (const p of parts) {
		if (typeof node === 'string') return key;
		node = node[p];
		if (node === undefined) return key;
	}
	if (typeof node !== 'string') return key;
	if (!params) return node;
	return node.replace(/\{(\w+)\}/g, (_, k) => {
		const v = params[k];
		return v === undefined ? `{${k}}` : String(v);
	});
}
