<?php
session_start();
require_once '../admin/config/database.php';

// If already logged in, redirect
if (isset($_SESSION['admin_id'])) {
    header('Location: ../admin');
    exit;
}

if (isset($_SESSION['student_id'])) {
    header('Location: ../student/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $login_type = $_POST['login_type']; // 'admin' or 'student'
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        if ($login_type === 'admin') {
            // Admin Login
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    // Update last login
                    $conn->query("UPDATE admins SET last_login = NOW() WHERE id = {$admin['id']}");
                    
                    header('Location: ../admin/index.php');
                    exit;
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Admin not found.";
            }
            $stmt->close();
            
        } else {
            // Student Login
            $stmt = $conn->prepare("SELECT * FROM students WHERE username = ? AND login_enabled = 1 AND status = 'Active'");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $student = $result->fetch_assoc();
                
                if (password_verify($password, $student['password'])) {
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_code'] = $student['student_code'];
                    $_SESSION['student_name'] = $student['full_name'];
                    $_SESSION['student_email'] = $student['email'];
                    
                    // Update last login
                    $conn->query("UPDATE students SET last_login = NOW() WHERE id = {$student['id']}");
                    
                    header('Location: ../student/dashboard.php');
                    exit;
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Student not found or login not enabled.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Next Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --purple-primary: #7c3aed;
            --purple-dark: #5b21b6;
        }
        
        body {
            background: linear-gradient(135deg, var(--purple-primary) 0%, var(--purple-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-dark));
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .login-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .login-tabs button {
            flex: 1;
            padding: 12px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .login-tabs button.active {
            background: var(--purple-primary);
            color: white;
            border-color: var(--purple-primary);
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--purple-primary);
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.25);
        }
        
        .input-group-text {
            background: #f3e8ff;
            border: 2px solid #f3e8ff;
            color: var(--purple-dark);
            border-radius: 10px 0 0 10px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-dark));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(124, 58, 237, 0.4);
            color: white;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--purple-primary);
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-graduation-cap"></i>
                <h2>Next Academy</h2>
                <p class="mb-0">Login to Your Account</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="login-tabs">
                    <!-- <button type="button" class="active" onclick="switchTab('student')">
                        <i class="fas fa-user-graduate"></i> Student
                    </button> -->
                    <!-- <button type="button" onclick="switchTab('admin')">
                        <i class="fas fa-user-shield"></i> Admin
                    </button> -->
                </div>
                
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="login_type" id="login_type" value="student">
                    
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Enter username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="../index.php">
                        <i class="fas fa-arrow-left"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(type) {
            document.getElementById('login_type').value = type;
            
            const buttons = document.querySelectorAll('.login-tabs button');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            event.target.classList.add('active');
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>