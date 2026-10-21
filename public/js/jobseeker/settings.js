async function fetchSettings() {
  const res = await fetch("/api/jobseeker/settings");
  const settings = await res.json();
  console.log("Fetched settings:", settings);
}
console.log("Fetching settings...");
fetchSettings();
