<script lang="ts">
	import '../app.css';
	import { ModeWatcher } from 'mode-watcher';
	import ThemeToggle from '$lib/components/ThemeToggle.svelte';

	let { children } = $props();
</script>

<ModeWatcher />

<svelte:head>
	<title>burnnote — one-time secrets</title>
	<meta name="description" content="Share secrets that self-destruct after one read." />
</svelte:head>

<div class="min-h-screen bg-background text-foreground">
	<header
		class="sticky top-0 z-40 border-b border-[color:var(--color-border)] bg-background/80 backdrop-blur"
	>
		<div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
			<a href="/" class="text-xl font-bold tracking-tight">burnnote</a>
			<div class="flex items-center gap-3">
				<span class="hidden text-sm text-muted-foreground sm:inline">one-time secrets</span>
				<ThemeToggle />
			</div>
		</div>
	</header>
	<main class="mx-auto max-w-3xl px-6 py-10">
		{@render children()}
	</main>
	<footer class="mx-auto max-w-3xl px-6 py-8 text-center text-sm text-muted-foreground">
		Encrypted in your browser. The server never sees the key.
	</footer>
</div>
