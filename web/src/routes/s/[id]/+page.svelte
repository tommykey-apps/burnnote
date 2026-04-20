<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { consumeNote, existsNote } from '$lib/api';
	import { decrypt } from '$lib/crypto';

	type View =
		| { kind: 'checking' }
		| { kind: 'ready' }
		| { kind: 'gone' }
		| { kind: 'no-key' }
		| { kind: 'revealing' }
		| { kind: 'revealed'; text: string }
		| { kind: 'error'; message: string };

	let view = $state<View>({ kind: 'checking' });
	let keyFragment = $state('');

	const noteId = $derived(page.params.id ?? '');

	onMount(async () => {
		keyFragment = location.hash.replace(/^#/, '');
		if (!keyFragment) {
			view = { kind: 'no-key' };
			return;
		}
		if (!noteId) {
			view = { kind: 'gone' };
			return;
		}
		const ok = await existsNote(noteId);
		view = ok ? { kind: 'ready' } : { kind: 'gone' };
	});

	async function reveal() {
		view = { kind: 'revealing' };
		try {
			const r = await consumeNote(noteId);
			if (r === 'gone') {
				view = { kind: 'gone' };
				return;
			}
			const text = await decrypt(r.ciphertext, r.iv, keyFragment);
			view = { kind: 'revealed', text };
			history.replaceState(null, '', location.pathname);
		} catch (err) {
			view = { kind: 'error', message: err instanceof Error ? err.message : String(err) };
		}
	}
</script>

<div class="space-y-6">
	{#if view.kind === 'checking'}
		<p class="text-muted-foreground">Checking…</p>
	{:else if view.kind === 'no-key'}
		<div class="rounded-lg border border-destructive/50 bg-destructive/10 p-6">
			<h1 class="text-xl font-bold">No key in URL</h1>
			<p class="mt-2 text-sm text-muted-foreground">
				This URL is missing the decryption key (the part after <code>#</code>). Without it
				the secret cannot be decrypted.
			</p>
		</div>
	{:else if view.kind === 'gone'}
		<div class="rounded-lg border border-[color:var(--color-border)] bg-card p-6">
			<h1 class="text-xl font-bold">This secret is gone</h1>
			<p class="mt-2 text-sm text-muted-foreground">
				It has been read once, or it has expired.
			</p>
		</div>
	{:else if view.kind === 'ready'}
		<div class="rounded-lg border border-[color:var(--color-border)] bg-card p-6 space-y-4">
			<h1 class="text-xl font-bold">A secret is waiting for you</h1>
			<p class="text-sm text-muted-foreground">
				Once revealed, it will be permanently deleted from the server. You will not be able
				to re-open this link.
			</p>
			<button
				type="button"
				onclick={reveal}
				class="rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
			>
				Reveal once
			</button>
		</div>
	{:else if view.kind === 'revealing'}
		<p class="text-muted-foreground">Decrypting…</p>
	{:else if view.kind === 'revealed'}
		<div class="rounded-lg border border-[color:var(--color-border)] bg-card p-6 space-y-3">
			<p class="text-sm text-muted-foreground">This secret has been destroyed on the server.</p>
			<pre
				class="whitespace-pre-wrap break-words rounded-md bg-muted p-4 font-mono text-sm">{view.text}</pre>
		</div>
	{:else if view.kind === 'error'}
		<div class="rounded-lg border border-destructive/50 bg-destructive/10 p-6">
			<h1 class="text-xl font-bold">Failed to decrypt</h1>
			<p class="mt-2 text-sm text-muted-foreground">{view.message}</p>
		</div>
	{/if}
</div>
