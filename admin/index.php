<?php
require_once 'includes/header.php';
require_once '../includes/functions.php';
require_once 'includes/functions.php';
?>

<h1>Admin Dashboard</h1>

<div class="dashboard-stats">
    <div class="stat-box">
        <i class="fas fa-users"></i>
        <div class="stat-content">
            <h3>Users</h3>
            <p class="stat-number"><?php echo getUserCount($conn); ?></p>
        </div>
    </div>
    
    <div class="stat-box">
        <i class="fas fa-utensils"></i>
        <div class="stat-content">
            <h3>Products</h3>
            <p class="stat-number"><?php echo getProductCount($conn); ?></p>
        </div>
    </div>
    
    <div class="stat-box">
        <i class="fas fa-tags"></i>
        <div class="stat-content">
            <h3>Categories</h3>
            <p class="stat-number"><?php echo getCategoryCount($conn); ?></p>
        </div>
    </div>
    
    <div class="stat-box">
        <i class="fas fa-shopping-cart"></i>
        <div class="stat-content">
            <h3>Orders</h3>
            <p class="stat-number"><?php echo getOrderCount($conn); ?></p>
        </div>
    </div>
</div>

<div class="dashboard-info">
    <div class="info-box">
        <h3>Recent Orders</h3>
        <?php
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at, u.name as user_name 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC LIMIT 5";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>';
            
            while ($order = $result->fetch_assoc()) {
                echo '<tr>
                    <td>#' . $order['id'] . '</td>
                    <td>' . $order['user_name'] . '</td>
                    <td>' . formatPrice($order['total_amount']) . '</td>
                    <td><span class="status-badge ' . $order['status'] . '">' . ucfirst(str_replace('_', ' ', $order['status'])) . '</span></td>
                    <td>' . date('M d, Y', strtotime($order['created_at'])) . '</td>
                </tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>No orders found.</p>';
        }
        ?>
    </div>
    
    <div class="info-box">
        <h3>System Information</h3>
        <ul class="system-info">
            <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
            <li><strong>Database Server:</strong> <?php echo $conn->server_info; ?></li>
            <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
            <li><strong>Debug Log:</strong> <a href="../debug_status.php">View Logs</a></li>
        </ul>
    </div>
</div>

<style>
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
}

.stat-box i {
    font-size: 2.5rem;
    margin-right: 15px;
    color: var(--primary-color);
}

.stat-content h3 {
    margin: 0;
    font-size: 1rem;
    color: #777;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 5px 0 0;
    color: #333;
}

.dashboard-info {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.info-box {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.info-box h3 {
    margin-top: 0;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    color: #333;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background-color: #f9f9f9;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.pending {
    background-color: #fff8e1;
    color: #ffa000;
}

.status-badge.processing {
    background-color: #e3f2fd;
    color: #1976d2;
}

.status-badge.out_for_delivery {
    background-color: #e8f5e9;
    color: #388e3c;
}

.status-badge.delivered {
    background-color: #e8f5e9;
    color: #388e3c;
}

.status-badge.cancelled {
    background-color: #ffebee;
    color: #d32f2f;
}

.system-info {
    list-style: none;
    padding: 0;
}

.system-info li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.system-info li:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .dashboard-info {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
require_once 'includes/footer.php';
?> 