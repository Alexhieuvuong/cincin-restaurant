<?php
require_once 'includes/header.php';
require_once '../includes/functions.php';
require_once 'includes/functions.php';

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<h1>Users Management</h1>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Admin</th>
                <th>Registered</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center">No users found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                        <td><?php echo $user['is_admin'] ? '<span class="status-badge admin">Yes</span>' : 'No'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge.admin {
    background: #d4edda;
    color: #155724;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: 600;
}
</style>

<?php require_once 'includes/footer.php'; ?> 