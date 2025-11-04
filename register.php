<?php
// Start a session to track user state
session_start();
// Include the database connection file
require_once 'db.php';

// Initialize variables to store form input and error/success messages
$email = $password = $confirm_password = "";
$email_err = $password_err = $confirm_password_err = $success_msg = "";

// Process form data when form is submitted via POST method
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate email input
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email address.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        // Check if email format is valid
        $email_err = "Please enter a valid email address.";
    } elseif(!preg_match('/@gmail\.com$/', trim($_POST["email"]))){
        // Check if email is a Gmail address
        $email_err = "Please use a Gmail address (@gmail.com).";
    } else {
        // Check if email already exists in database
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(1, $param_email, PDO::PARAM_STR);
            $param_email = trim($_POST["email"]);
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    // Email already exists
                    $email_err = "This email is already registered.";
                } else {
                    // Email is available
                    $email = trim($_POST["email"]);
                }
            }
            unset($stmt);
        }
    }

    // Validate password input
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        // Check if password meets minimum length requirement
        $password_err = "Password must be at least 6 characters long.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password input
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        // Check if password and confirm password match
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // If no validation errors, proceed to insert new user into database
    if(empty($email_err) && empty($password_err) && empty($confirm_password_err)){
        $sql = "INSERT INTO users (email, password) VALUES (?, ?)";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(1, $param_email, PDO::PARAM_STR);
            $stmt->bindParam(2, $param_password, PDO::PARAM_STR);

            $param_email = $email;
            // Hash the password before storing in database for security
            $param_password = password_hash($password, PASSWORD_DEFAULT);

            if($stmt->execute()){
                // Registration successful
                $success_msg = "Registration successful! You can now login.";
                // Clear form fields after successful registration
                $email = $password = $confirm_password = "";
            }
            unset($stmt);
        }
    }
    // Close database connection
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Nayre Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="floating-shapes"></div>
    <div class="container">
        <div class="form-container">
            <h2>Create Account</h2>
            <p>Register to access the portfolio</p>

            <?php if(!empty($success_msg)): ?>
                <div class="success-message"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <div class="gmail-note">
                Please use your Gmail address (@gmail.com) to register.
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" placeholder="yourname@gmail.com">
                    <?php if(!empty($email_err)): ?>
                        <span class="error-text"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'error' : ''; ?>" placeholder="At least 6 characters">
                    <?php if(!empty($password_err)): ?>
                        <span class="error-text"><?php echo $password_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'error' : ''; ?>" placeholder="Confirm your password">
                    <?php if(!empty($confirm_password_err)): ?>
                        <span class="error-text"><?php echo $confirm_password_err; ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary" id="registerBtn">
                    <span class="loading-spinner"></span>
                    <span class="btn-text">Create Account</span>
                </button>
            </form>

            <div class="switch-form">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Add loading animation to form submission button
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('registerBtn');
            const btnText = btn.querySelector('.btn-text');

            // Show loading spinner and change button text
            btn.classList.add('loading');
            btnText.textContent = 'Creating Account...';

            // Add a small delay to show the loading animation before form submits
            setTimeout(() => {
                // Form will submit after this delay
            }, 300);
        });

        // Add focus animations to form input fields
        document.querySelectorAll('.form-control').forEach(input => {
            // When input field receives focus, add 'focused' class to parent
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            // When input field loses focus and is empty, remove 'focused' class
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });

        // Add real-time email validation with visual feedback
        const emailInput = document.querySelector('input[name="email"]');
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                const value = this.value;
                // Show orange border if email doesn't contain @gmail.com
                if (value && !value.includes('@gmail.com')) {
                    this.style.borderColor = '#ffa500';
                // Show green border if email contains @gmail.com
                } else if (value.includes('@gmail.com')) {
                    this.style.borderColor = '#51cf66';
                } else {
                    // Reset border color if field is empty
                    this.style.borderColor = '';
                }
            });
        }

        // Password strength indicator with visual feedback
        const passwordInput = document.querySelector('input[name="password"]');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const value = this.value;
                const length = value.length;

                // Show red border if password is less than 6 characters
                if (length < 6) {
                    this.style.borderColor = length > 0 ? '#ff6b6b' : '';
                } else {
                    // Show green border if password meets minimum length
                    this.style.borderColor = '#51cf66';
                }
            });
        }
    </script>
</body>
</html>