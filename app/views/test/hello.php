<div style="text-align: center; max-width: 600px; margin: 0 auto;">
    <h1 style="color: #28a745;">👋 Hello <?= htmlspecialchars($name) ?>!</h1>

    <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 30px 0;">
        <p style="font-size: 18px;"><?= htmlspecialchars($message) ?></p>
        <p><strong>Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div style="margin-top: 30px;">
        <a href="/test" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            ← Back to Test Page
        </a>
    </div>
</div>