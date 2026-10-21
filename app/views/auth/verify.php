<!-- DEBUG: Show all undefined variables -->
<?php
$undefined_vars = [];
$defined_vars = array_keys(get_defined_vars());
$expected_vars = ['content', 'title', 'css', 'js']; // add all expected variables

foreach ($expected_vars as $var) {
    if (!in_array($var, $defined_vars)) {
        $undefined_vars[] = $var;
    }
}

if (!empty($undefined_vars)) {
    echo "<!-- Undefined variables: " . implode(', ', $undefined_vars) . " -->";
}
?>
<?php
if (isset($token)) {
    if (empty($token)) {
        die('Invalid verification link');
    }
}
?>
<div class="container">
    <h1>Verifying your email...</h1>
    <div class="spinner" id="spinner"></div>
    <p>Please wait while we verify your email address.</p>
    <div class="message" id="message"></div>
</div>
<script>
    async function verifyEmail() {
        try {
            const token = '<?php echo htmlspecialchars($token); ?>';
            const email = '<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>';

            const formData = {
                token: token,
                email: email
            }

            const response = await fetch("/api/verify-email", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(formData),
            });

            document.getElementById('spinner').style.display = 'none';
            const data = await response.json();
            console.log("Response: ", data);

            if (!response.ok) {
                throw new Error(data.message);
            }

            // FIXED: Changed 'res' to 'data'
            if (data.success) {
                showMessage(data.message || '✓ Email verified successfully!', 'success');
                Toast.success(data.message || 'Email verified!');

                setTimeout(() => {
                    window.location.href = '/login?message=verified';
                }, 2000);
            } else {
                const errorMsg = data.message || 'Verification failed';
                showMessage(errorMsg, 'error');
                Toast.error(errorMsg);
            }

        } catch (error) {
            document.getElementById('spinner').style.display = 'none';
            const errorMsg = error.message || 'Verification link expired or invalid';
            showMessage(errorMsg, 'error');
            Toast.error(errorMsg);
            setTimeout(() => {
                window.location.href = '/reverify?email=' + encodeURIComponent('<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>');
            }, 2000);
        }
    }

    function showMessage(msg, type) {
        const messageDiv = document.getElementById('message');
        messageDiv.textContent = msg;
        messageDiv.className = 'message ' + type;
    }

    // Start verification when page loads
    window.addEventListener('load', verifyEmail);
</script>