async function getInterviews() {
  try {
    const response = await fetch("/api/jobseeker/interviews", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin", // Include cookies for authentication
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const interviews = await response.json();
    console.log("Fetched interviews:", interviews);

    // TODO: Update the UI with the interviews data
    displayInterviews(interviews);
  } catch (error) {
    console.error("Error fetching interviews:", error);
    // TODO: Show error message to user
    showError("Failed to load interviews. Please try again.");
  }
}

function displayInterviews(interviews) {
  // TODO: Implement UI update logic
  console.log("Displaying interviews:", interviews);
}

function showError(message) {
  // TODO: Implement error display logic
  alert(message); // Temporary alert for debugging
}

console.log("Fetching interviews...");
getInterviews();
