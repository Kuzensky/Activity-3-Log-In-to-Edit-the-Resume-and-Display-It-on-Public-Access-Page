<?php
// Start a session to track user login state
session_start();

// Check if user is already logged in, if yes redirect to resume page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

// Initialize variables to store form input and error messages
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Process form data when form is submitted via POST method
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate email input
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password input
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If no validation errors, automatically log in with any credentials
    if(empty($email_err) && empty($password_err)){
        // Create session variables for any email/password combination
        $_SESSION["loggedin"] = true;
        $_SESSION["id"] = 1; // Default user ID
        $_SESSION["email"] = $email;

        // Redirect to homepage
        header("location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nayre Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="floating-shapes"></div>
    <div class="container">
        <div class="form-container">
            <h2>Login</h2>
            <p>Sign in to edit the resume</p>

            <?php if(!empty($login_err)): ?>
                <div class="error-message"><?php echo $login_err; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email">
                    <?php if(!empty($email_err)): ?>
                        <span class="error-text"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'error' : ''; ?>" placeholder="Enter your password">
                    <?php if(!empty($password_err)): ?>
                        <span class="error-text"><?php echo $password_err; ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary" id="loginBtn">
                    <span class="loading-spinner"></span>
                    <span class="btn-text">Login</span>
                </button>
            </form>

        </div>
    </div>

    <script>
        // Add loading animation to form submission button
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');

            // Show loading spinner and change button text
            btn.classList.add('loading');
            btnText.textContent = 'Signing in...';

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
    </script>
</body>
</html>