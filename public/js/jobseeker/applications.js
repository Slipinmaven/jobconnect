async function fetchApplications() {
  const userId = window.userId;
  const response = await fetch(`/api/applications/user`);
  const applications = await response.json();
  console.log("Fetched applications:", applications);
}
fetchApplications();
