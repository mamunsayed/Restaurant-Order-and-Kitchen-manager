<div class="top-header">
    <div class="welcome-text">
        Welcome, <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>
    <div class="top-header-right">
        <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div>
        <div class="user-avatar">
            <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
        </div>
    </div>
</div>