<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register | La Trinidad Crime Mapping</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/site.css" />
</head>
<body class="page-auth">
    <div class="page-shell auth-shell">
        <header class="site-header">
            <div class="brand">
                <span class="brand-mark"></span>
                <div>
                    <div class="brand-title">La Trinidad Crime Mapping</div>
                    <div class="brand-subtitle">Create an account</div>
                </div>
            </div>
            <nav class="site-nav">
                <a href="index.php">Dashboard</a>
                <a href="map.php">Map</a>
                <a href="about.php">About & FAQ</a>
                <a href="login.php">Login</a>
                <a class="nav-cta is-active" href="register.php">Register</a>
            </nav>
        </header>

        <main class="auth-card">
            <h1>Create your account</h1>
            <p class="muted">Registered users can submit reports and track updates.</p>
            <form class="form-grid" action="#" method="post">
                <label>
                    <span>Full name</span>
                    <input type="text" name="name" placeholder="Juan Dela Cruz" required />
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" placeholder="you@domain.com" required />
                </label>
                <label>
                    <span>Contact number</span>
                    <input type="text" name="contact" placeholder="+63900 000 0000" required />
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Create a password" required />
                </label>
                <button class="btn-primary" type="submit">Register</button>
                <p class="muted">Already registered? <a href="login.php">Login here</a>.</p>
            </form>
        </main>
    </div>
</body>
</html>
