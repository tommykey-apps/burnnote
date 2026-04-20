<script lang="ts">
	import { encrypt } from '$lib/crypto';
	import { createNote } from '$lib/api';

	let plaintext = $state('');
	let expiresIn = $state(3600);
	let url = $state<string | null>(null);
	let submitting = $state(false);
	let error = $state<string | null>(null);
	let copied = $state(false);

	const expiryOptions = [
		{ label: '5 minutes', value: 300 },
		{ label: '1 hour', value: 3600 },
		{ label: '1 day', value: 86400 },
		{ label: '7 days', value: 604800 }
	];

	async function handleSubmit(e: SubmitEvent) {
		e.preventDefault();
		if (!plaintext.trim()) return;
		submitting = true;
		error = null;
		copied = false;
		try {
			const { ciphertext, iv, key } = await encrypt(plaintext);
			const res = await createNote(ciphertext, iv, expiresIn);
			url = `${location.origin}/s/${res.id}#${key}`;
		} catch (err) {
			error = err instanceof Error ? err.message : String(err);
		} finally {
			submitting = false;
		}
	}

	async function copyUrl() {
		if (!url) return;
		await navigator.clipboard.writeText(url);
		copied = true;
		setTimeout(() => (copied = false), 2000);
	}

	function reset() {
		plaintext = '';
		url = null;
		error = null;
	}
</script>

<div class="space-y-6">
	<div>
		<h1 class="text-3xl font-bold tracking-tight">Share a one-time secret</h1>
		<p class="mt-2 text-muted-foreground">
			The secret is encrypted in your browser. The server stores only the ciphertext.
			The URL is destroyed after a single read.
		</p>
	</div>

	{#if url}
		<div
			class="rounded-lg border border-[color:var(--color-border)] bg-card p-6 shadow-sm space-y-4"
		>
			<p class="text-sm text-muted-foreground">
				Share this URL. It works only once.
			</p>
			<div class="flex gap-2">
				<input
					class="flex-1 rounded-md border border-[color:var(--color-border)] bg-background px-3 py-2 font-mono text-sm"
					readonly
					value={url}
					onclick={(e) => (e.currentTarget as HTMLInputElement).select()}
				/>
				<button
					type="button"
					onclick={copyUrl}
					class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
				>
					{copied ? 'Copied!' : 'Copy'}
				</button>
			</div>
			<button
				type="button"
				onclick={reset}
				class="text-sm text-muted-foreground hover:text-foreground underline"
			>
				Create another
			</button>
		</div>
	{:else}
		<form onsubmit={handleSubmit} class="space-y-4">
			<label class="block">
				<span class="text-sm font-medium">Secret</span>
				<textarea
					bind:value={plaintext}
					rows="8"
					required
					maxlength="8000"
					placeholder="Type or paste a secret…"
					class="mt-1 block w-full rounded-md border border-[color:var(--color-border)] bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"
				></textarea>
			</label>

			<label class="block">
				<span class="text-sm font-medium">Expires in</span>
				<select
					bind:value={expiresIn}
					class="mt-1 block w-full rounded-md border border-[color:var(--color-border)] bg-background px-3 py-2 text-sm"
				>
					{#each expiryOptions as opt}
						<option value={opt.value}>{opt.label}</option>
					{/each}
				</select>
			</label>

			{#if error}
				<p class="text-sm text-destructive">{error}</p>
			{/if}

			<button
				type="submit"
				disabled={submitting || !plaintext.trim()}
				class="rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50"
			>
				{submitting ? 'Encrypting…' : 'Create one-time URL'}
			</button>
		</form>
	{/if}
</div>
