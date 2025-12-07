<?php


if (!function_exists("getMysqlTimestamp")) {
	function getMysqlTimestamp($timezone = null)
	{
		if ($timezone == null) {
			// Use default timezone here
			$timezone = "America/Mexico_City";
		}
		// Get a list of valid timezone identifiers
		$validTimezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

		if (!in_array($timezone, $validTimezones)) {
			$timezone = 'America/Mexico_City';
		}
		$myTime = new DateTime('now', new DateTimeZone($timezone));
		return $myTime->format("Y-m-d H:i:s");
	}
}
if (!function_exists("getMysqlDate")) {
	function getMysqlDate($timezone = null)
	{
		if ($timezone == null) {
			// Use default timezone here
			$timezone = "America/Mexico_City";
		}
		// Get a list of valid timezone identifiers
		$validTimezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

		if (!in_array($timezone, $validTimezones)) {
			$timezone = 'America/Mexico_City';
		}
		$myTime = new DateTime('now', new DateTimeZone($timezone));
		return $myTime->format("Y-m-d");
	}
}
if (!function_exists("getMysqlTimestamp")) {
	function generateUniqueSlug($name, $model)
	{
		$slug = Illuminate\Support\Str::slug($name);
		$originalSlug = $slug;
		$count = 1;

		while ($model::where('slug', $slug)->exists()) {
			$slug = "{$originalSlug}-{$count}";
			$count++;
		}

		return $slug;
	}
}
