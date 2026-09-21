<?php
// Database connection
$conn = new mysqli("127.0.0.1", "root", "", "my_app");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for the form
$edit_state = false;
$id = 0;
$name = "";
$email = "";
$search = "";

// Handle DELETE request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php"); 
    exit;
}

// Handle EDIT request
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_state = true;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['name'];
        $email = $row['email'];
    }
    $stmt->close();
}

// Handle FORM SUBMISSION (Add or Update)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];

    if (isset($_POST['update'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $email, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php"); 
    exit;
}

// Get total user count (NEW!)
$count_result = $conn->query("SELECT COUNT(*) AS total FROM users");
$count_row = $count_result->fetch_assoc();
$total_users = $count_row['total'];

// Handle SEARCH request
if (isset($_GET['search']) && $_GET['search'] != "") {
    $search = $_GET['search'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ?");
    $like_search = "%" . $search . "%";
    $stmt->bind_param("ss", $like_search, $like_search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fetch all users if no search
    $result = $conn->query("SELECT * FROM users");
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Small custom tweaks for a premium dark look */
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
        }
        .card {
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        .table {
            --bs-table-bg: transparent;
        }
    </style>
</head>
<body class="text-light">
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0">User Manager</h3>
                <!-- Total Users Counter -->
                <span class="badge bg-light text-primary fs-6">Total Users: <?php echo $total_users; ?></span>
            </div>
            <div class="card-body">
                
                <!-- Form for Add/Edit -->
                <form method="POST" action="" class="row g-3 mb-4">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="email" class="form-control" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <?php if ($edit_state): ?>
                            <button type="submit" name="update" class="btn btn-warning w-100 mb-2">Update User</button>
                            <a href="index.php" class="btn btn-outline-secondary w-100">Cancel Edit</a>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success w-100">Add User</button>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Search Bar -->
                <form method="GET" action="" class="row g-3 mb-4">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                        <?php if ($search != ""): ?>
                            <a href="index.php" class="btn btn-outline-light w-100 mt-2">Clear Search</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- User Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
    </div>
</body>
</html>