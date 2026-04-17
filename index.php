<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Cimage Library Management</title>
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-page">
	<header class="header">
		<h1>Cimage College Library</h1>
		<nav>
			<ul class="nav-links">
				<li><a href="student/available_books.php">Browse Books</a></li>
				<li><a href="contact-us.php">Contact</a></li>
				<li><a class="btn btn-small" href="login.php">Login</a></li>
			</ul>
		</nav>
	</header>
	<main>
		<section class="landing-hero container">
			<div class="hero-content">
				<h2>Discover. Borrow. Learn.</h2>
				<p>Manage books, track issues, and stay updated with announcements.</p>
				<div class="landing-cta">
					<a class="btn btn-primary" href="login.php">Sign in</a>
					<a class="btn btn-outline" href="student/available_books.php">Browse Available Books</a>
				</div>
			</div>
		</section>
		<section class="landing-features container">
			<div class="card">
				<h3>Rich Catalog</h3>
				<p>Search categories and find your next read quickly.</p>
			</div>
			<div class="card">
				<h3>Student-Friendly</h3>
				<p>View issued books, due dates, and announcements at a glance.</p>
			</div>
			<div class="card">
				<h3>Admin Tools</h3>
				<p>Effortlessly manage books, categories, and student records.</p>
			</div>
		</section>
	</main>
	<footer class="container" style="text-align:center; padding: 16px 0; color:#4a5568;">
		© <span id="year"></span> Cimage College Library
	</footer>
	<script>
		document.getElementById('year').textContent = new Date().getFullYear();
	</script>
	<script src="assets/js/script.js"></script>
</body>
</html>
