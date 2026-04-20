<script lang="ts">
	import { mode, setMode, userPrefersMode } from 'mode-watcher';
	import Sun from 'phosphor-svelte/lib/Sun';
	import Moon from 'phosphor-svelte/lib/Moon';
	import Monitor from 'phosphor-svelte/lib/Monitor';

	function cycle() {
		const current = userPrefersMode.current;
		const next = current === 'light' ? 'dark' : current === 'dark' ? 'system' : 'light';
		setMode(next);
	}

	const label = $derived.by(() => {
		const u = userPrefersMode.current;
		if (u === 'system') return `Theme: system (${mode.current})`;
		return `Theme: ${u}`;
	});
</script>

<button
	type="button"
	onclick={cycle}
	aria-label={label}
	title={label}
	class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-[color:var(--color-border)] bg-background transition-colors hover:bg-accent hover:text-accent-foreground"
>
	{#if userPrefersMode.current === 'light'}
		<Sun size={18} weight="regular" />
	{:else if userPrefersMode.current === 'dark'}
		<Moon size={18} weight="regular" />
	{:else}
		<Monitor size={18} weight="regular" />
	{/if}
</button>
