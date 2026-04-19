<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* The exact gradient from your image */
            background: linear-gradient(135deg, #f28c28 0%, #ffb366 40%, #f4f4f0 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .auth-card {
            background: #ffffff;
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .avatar-circle {
            width: 85px;
            height: 85px;
            background-color: #f28c28; 
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(242, 140, 40, 0.3);
        }

        .person-icon {
            font-size: 40px;
            color: #ffffff;
        }

        .auth-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .auth-title span {
            color: #f28c28;
        }

        .auth-subtitle {
            font-size: 15px;
            color: #888;
            margin-bottom: 35px;
        }

        .input-group {
            position: relative;
            margin-bottom: 18px;
        }

        .input-group input {
            width: 100%;
            padding: 13px 13px 13px 40px;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            background-color: #fcfcfc;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #f28c28;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(242, 140, 40, 0.05);
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #b0b0b0;
            font-size: 18px;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background-color: #f3a660; 
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: transform 0.2s ease, background 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #e6954d;
            transform: translateY(-1px);
        }

        .auth-footer {
            margin-top: 30px;
            font-size: 14px;
            color: #555;
        }

        .auth-footer a {
            color: #f28c28;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="avatar-circle">
            <i class="fa-solid fa-user person-icon"></i>
        </div>
        
        <h2 class="auth-title">Create <span>Account</span></h2>
        <p class="auth-subtitle">Please enter your details to register</p>
        
        <form action="registration.php" method="POST">
            <div class="input-group">
                <span class="input-icon"><i class="fa-regular fa-user"></i></span>
                <input type="text" name="name" placeholder="Full Name" required>
            </div>

            <div class="input-group">
                <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>

            <div class="input-group">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <span class="password-toggle" id="togglePassword">
                    <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                </span>
            </div>

            <div class="input-group">
                <span class="input-icon"><i class="fa-solid fa-shield-halved"></i></span>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <span class="password-toggle" id="toggleConfirmPassword">
                    <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                </span>
            </div>

            <button type="submit" class="btn-primary">Register Now</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>

    <script>
        function setupToggle(toggleId, inputId, iconId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }

        setupToggle('togglePassword', 'password', 'eyeIcon1');
        setupToggle('toggleConfirmPassword', 'confirm_password', 'eyeIcon2');
    </script>
</body>
</html>