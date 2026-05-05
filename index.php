<?php
session_start();

// Create json folder if it doesn't exist
if (!is_dir('json')) {
    mkdir('json', 0755, true);
}

$users_file = 'json/users.json';

// Initialize users file if it doesn't exist
if (!file_exists($users_file)) {
    file_put_contents($users_file, json_encode([], JSON_PRETTY_PRINT));
}

$error = '';
$success = '';
$show_signup = isset($_GET['signup']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // SIGNUP
        if ($action === 'signup') {
            $username = trim($_POST['signup_username'] ?? '');
            $email = trim($_POST['signup_email'] ?? '');
            $password = $_POST['signup_password'] ?? '';
            $confirm_password = $_POST['signup_confirm_password'] ?? '';
            
            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $error = 'All fields are required!';
            } elseif (strlen($username) < 3) {
                $error = 'Username must be at least 3 characters long!';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address!';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long!';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match!';
            } else {
                // Load existing users
                $users = json_decode(file_get_contents($users_file), true) ?? [];
                
                // Check if username or email already exists
                $username_exists = false;
                $email_exists = false;
                
                foreach ($users as $user) {
                    if ($user['username'] === $username) {
                        $username_exists = true;
                    }
                    if ($user['email'] === $email) {
                        $email_exists = true;
                    }
                }
                
                if ($username_exists) {
                    $error = 'Username already exists!';
                } elseif ($email_exists) {
                    $error = 'Email already registered!';
                } else {
                    // Create new user
                    $new_user = [
                        'id' => count($users) + 1,
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_BCRYPT),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $users[] = $new_user;
                    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
                    
                    $success = 'Account created successfully! Please login.';
                    $show_signup = false;
                }
            }
        }
        
        // LOGIN
        elseif ($action === 'login') {
            $username = trim($_POST['login_username'] ?? '');
            $password = $_POST['login_password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Username and password are required!';
            } else {
                $users = json_decode(file_get_contents($users_file), true) ?? [];
                $user_found = false;
                
                foreach ($users as $user) {
                    if ($user['username'] === $username && password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $user_found = true;
                        break;
                    }
                }
                
                if ($user_found) {
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid username or password!';
                }
            }
        }
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_signup ? 'Sign Up' : 'Login'; ?> - Professional Auth</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 450px;
        }

        .auth-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .auth-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input::placeholder {
            color: #999;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }

        .alert-error {
            background-color: #fee;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
        }

        .alert-success {
            background-color: #efe;
            border-left: 4px solid #27ae60;
            color: #229954;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .toggle-form {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .toggle-form a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .toggle-form a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .password-requirements {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }

        .password-requirements ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        .password-requirements li {
            margin-bottom: 4px;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: #764ba2;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 30px 20px;
            }

            .auth-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($show_signup): ?>
                <!-- SIGNUP FORM -->
                <div class="auth-header">
                    <h1>Create Account</h1>
                    <p>Join us today and get started</p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="signup">

                    <div class="form-group">
                        <label for="signup_username">Username</label>
                        <input type="text" id="signup_username" name="signup_username" placeholder="Choose a username" required>
                    </div>

                    <div class="form-group">
                        <label for="signup_email">Email Address</label>
                        <input type="email" id="signup_email" name="signup_email" placeholder="your@email.com" required>
                    </div>

                    <div class="form-group">
                        <label for="signup_password">Password</label>
                        <input type="password" id="signup_password" name="signup_password" placeholder="Enter password" required>
                        <div class="password-requirements">
                            <strong>Password requirements:</strong>
                            <ul>
                                <li>Minimum 6 characters</li>
                                <li>Mix of letters and numbers recommended</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="signup_confirm_password">Confirm Password</label>
                        <input type="password" id="signup_confirm_password" name="signup_confirm_password" placeholder="Confirm password" required>
                    </div>

                    <button type="submit">Create Account</button>

                    <div class="toggle-form">
                        Already have an account? <a href="index.php">Login here</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- LOGIN FORM -->
                <div class="auth-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account</p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <label for="login_username">Username</label>
                        <input type="text" id="login_username" name="login_username" placeholder="Enter your username" required>
                    </div>

                    <div class="form-group">
                        <label for="login_password">Password</label>
                        <input type="password" id="login_password" name="login_password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit">Sign In</button>

                    <div class="toggle-form">
                        Don't have an account? <a href="index.php?signup=1">Sign up here</a>
                    </div>

                    <div class="form-footer">
                        <a href="#">Forgot password?</a>
                    </div>
                </form>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>