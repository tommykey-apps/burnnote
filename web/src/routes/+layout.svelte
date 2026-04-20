<script lang="ts">
	import '../app.css';
	import { onMount } from 'svelte';
	import { ModeWatcher } from 'mode-watcher';
	import ThemeToggle from '$lib/components/ThemeToggle.svelte';
	import LocaleSwitcher from '$lib/components/LocaleSwitcher.svelte';
	import { initLocale, t } from '$lib/i18n/index.svelte';

	let { children } = $props();

	onMount(() => {
		initLocale();
	});
</script>

<ModeWatcher />

<svelte:head>
	<title>{t('meta.title')}</title>
	<meta name="description" content={t('meta.description')} />
</svelte:head>

<div class="min-h-screen bg-background text-foreground">
	<header
		class="sticky top-0 z-40 border-b border-[color:var(--color-border)] bg-background/80 backdrop-blur"
	>
		<div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
			<a href="/" class="text-xl font-bold tracking-tight">burnnote</a>
			<div class="flex items-center gap-2">
				<span class="hidden text-sm text-muted-foreground sm:inline">{t('nav.tagline')}</span>
				<LocaleSwitcher />
				<ThemeToggle />
			</div>
		</div>
	</header>
	<main class="mx-auto max-w-3xl px-6 py-10">
		{@render children()}
	</main>
	<footer class="mx-auto max-w-3xl px-6 py-8 text-center text-sm text-muted-foreground">
		{t('footer.note')}
	</footer>
</div>
