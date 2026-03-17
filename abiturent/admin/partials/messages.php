<?php if ($GLOBALS['message']): ?>
    <div class="message <?php echo $GLOBALS['message_type'] === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($GLOBALS['message']); ?>
    </div>
<?php endif; ?>