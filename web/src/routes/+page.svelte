<script lang="ts">
	import { encrypt } from '$lib/crypto';
	import { createNote } from '$lib/api';
	import { t } from '$lib/i18n/index.svelte';

	let plaintext = $state('');
	let expiresIn = $state(3600);
	let url = $state<string | null>(null);
	let submitting = $state(false);
	let error = $state<string | null>(null);
	let copied = $state(false);

	const expiryOptions = [
		{ key: '5m', value: 300 },
		{ key: '1h', value: 3600 },
		{ key: '1d', value: 86400 },
		{ key: '7d', value: 604800 }
	] as const;

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
		<h1 class="text-3xl font-bold tracking-tight">{t('create.heading')}</h1>
		<p class="mt-2 text-muted-foreground">{t('create.description')}</p>
	</div>

	{#if url}
		<div
			class="rounded-lg border border-[color:var(--color-border)] bg-card p-6 shadow-sm space-y-4"
		>
			<p class="text-sm text-muted-foreground">{t('create.result_note')}</p>
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
					{copied ? t('create.copied') : t('create.copy')}
				</button>
			</div>
			<button
				type="button"
				onclick={reset}
				class="text-sm text-muted-foreground hover:text-foreground underline"
			>
				{t('create.reset')}
			</button>
		</div>
	{:else}
		<form onsubmit={handleSubmit} class="space-y-4">
			<label class="block">
				<span class="text-sm font-medium">{t('create.label_secret')}</span>
				<textarea
					bind:value={plaintext}
					rows="8"
					required
					maxlength="8000"
					placeholder={t('create.placeholder')}
					class="mt-1 block w-full rounded-md border border-[color:var(--color-border)] bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring"
				></textarea>
			</label>

			<label class="block">
				<span class="text-sm font-medium">{t('create.label_expires')}</span>
				<select
					bind:value={expiresIn}
					class="mt-1 block w-full rounded-md border border-[color:var(--color-border)] bg-background px-3 py-2 text-sm"
				>
					{#each expiryOptions as opt}
						<option value={opt.value}>{t(`create.expiry.${opt.key}`)}</option>
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
				{submitting ? t('create.submitting') : t('create.submit')}
			</button>
		</form>
	{/if}
</div>
