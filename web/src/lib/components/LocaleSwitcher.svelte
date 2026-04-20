<script lang="ts">
	import { getLocale, setLocale, type Locale } from '$lib/i18n/index.svelte';

	function onChange(e: Event) {
		const v = (e.currentTarget as HTMLSelectElement).value as Locale;
		setLocale(v);
	}
</script>

<select
	value={getLocale()}
	onchange={onChange}
	aria-label="Language"
	class="h-9 rounded-md border border-[color:var(--color-border)] bg-background px-2 text-sm"
>
	<option value="en">EN</option>
	<option value="ja">JA</option>
</select>
