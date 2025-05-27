<?php
require_once 'includes/header.php';
require_once '../includes/functions.php';
require_once 'includes/functions.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];

    $query = "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssi", $status, $payment_status, $order_id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Order status updated successfully.');
        } else {
            setFlashMessage('danger', 'Failed to update order status.');
        }
        $stmt->close();
    }
}

// Get all orders with customer info
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC";
$result = $conn->query($query);
$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Get order details if requested
$order_details = null;
$order_items = [];
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $order_details = getOrderById($conn, $order_id);
    $order_items = getOrderDetails($conn, $order_id);
}
?>

<h1>Orders Management</h1>
<?php displayFlashMessage(); ?>

<div class="orders-admin-container">
    <div class="orders-list">
        <h2>All Orders</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td><span class="status-badge <?php echo $order['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                            <td><span class="status-badge <?php echo $order['payment_status']; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td><a href="?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($order_details): ?>
    <div class="order-details-admin">
        <h2>Order #<?php echo str_pad($order_details['id'], 6, '0', STR_PAD_LEFT); ?></h2>
        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order_details['customer_name'] ?? ''); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email'] ?? ''); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($order_details['address']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order_details['phone']); ?></p>
        <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order_details['created_at'])); ?></p>
        <p><strong>Status:</strong> <span class="status-badge <?php echo $order_details['status']; ?>"><?php echo ucfirst($order_details['status']); ?></span></p>
        <p><strong>Payment Status:</strong> <span class="status-badge <?php echo $order_details['payment_status']; ?>"><?php echo ucfirst($order_details['payment_status']); ?></span></p>
        <p><strong>Total:</strong> <?php echo formatPrice($order_details['total_amount']); ?></p>
        <h3>Order Items</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Image</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php if (!empty($item['image'])): ?><img src="../<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:40px;height:40px;object-fit:cover;"/><?php endif; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo formatPrice($item['price']); ?></td>
                    <td>
                        <?php 
                        if (!empty($item['options'])) {
                            $options = json_decode($item['options'], true);
                            if ($options && is_array($options)) {
                                echo '<div class="order-item-options">';
                                foreach ($options as $option) {
                                    echo '<span class="option-badge">' . ucwords($option) . '</span>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<span class="no-options">No extra options</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Update Order Status</h3>
        <form method="POST" class="order-status-form">
            <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
            <div class="form-group">
                <label for="status">Order Status</label>
                <select name="status" id="status" class="form-control">
                    <?php foreach ([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($order_details['status'] == $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="payment_status">Payment Status</label>
                <select name="payment_status" id="payment_status" class="form-control">
                    <?php foreach ([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($order_details['payment_status'] == $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
            <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
.orders-admin-container {
    display: flex;
    gap: 40px;
    align-items: flex-start;
}
.orders-list {
    flex: 2;
}
.order-details-admin {
    flex: 1.5;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    padding: 24px;
}
.order-details-admin h2 {
    margin-top: 0;
}
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: 600;
    margin-right: 4px;
}
.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.processing { background: #cce5ff; color: #004085; }
.status-badge.out_for_delivery { background: #d1ecf1; color: #0c5460; }
.status-badge.delivered { background: #d4edda; color: #155724; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }
.status-badge.completed { background: #d4edda; color: #155724; }
.status-badge.failed { background: #f8d7da; color: #721c24; }
.data-table img { border-radius: 4px; }
.order-status-form { margin-top: 20px; }
.order-status-form .form-group { margin-bottom: 16px; }
.order-item-options {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.option-badge {
    background-color: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.no-options {
    color: #999;
    font-style: italic;
    font-size: 12px;
}
</style>

<?php require_once 'includes/footer.php'; ?> 