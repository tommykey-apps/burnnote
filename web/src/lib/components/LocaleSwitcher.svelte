<script lang="ts">
	import { localeState, type Locale } from '$lib/i18n/index.svelte';

	function onChange(e: Event) {
		const v = (e.currentTarget as HTMLSelectElement).value as Locale;
		localeState.set(v);
	}
</script>

<select
	onchange={onChange}
	aria-label="Language"
	class="h-9 rounded-md border border-[color:var(--color-border)] bg-background px-2 text-sm"
>
	<option value="en" selected={localeState.current === 'en'}>EN</option>
	<option value="ja" selected={localeState.current === 'ja'}>JA</option>
</select>
