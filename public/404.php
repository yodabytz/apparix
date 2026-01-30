<?php
/**
 * Custom 404 Not Found Page
 * Apparix
 */

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Page Not Found - Apparix</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-pink: #FF68C5;
            --secondary-pink: #FF94C8;
            --light-pink: #FFE4F3;
            --dark-gray: #424242;
            --text-secondary: #666;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--light-pink) 0%, #fff 50%, var(--light-pink) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .container {
            max-width: 500px;
        }

        .error-code {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 8rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-pink) 0%, var(--secondary-pink) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }

        .lily-pad {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.75rem;
            color: var(--dark-gray);
            margin-bottom: 1rem;
        }

        p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 0.875rem 1.75rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-pink) 0%, #ff85d0 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 104, 197, 0.4);
        }

        .btn-outline {
            background: white;
            color: var(--primary-pink);
            border: 2px solid var(--primary-pink);
        }

        .btn-outline:hover {
            background: var(--light-pink);
            transform: translateY(-2px);
        }

        .search-box {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 104, 197, 0.2);
        }

        .search-box p {
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            max-width: 350px;
            margin: 0 auto;
        }

        .search-form input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .search-form input:focus {
            outline: none;
            border-color: var(--primary-pink);
        }

        .search-form button {
            padding: 0.75rem 1.25rem;
            background: var(--primary-pink);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }

        .search-form button:hover {
            background: #ff4db8;
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 5rem;
            }

            .lily-pad {
                font-size: 3rem;
            }

            h1 {
                font-size: 1.4rem;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <div class="lily-pad">🪷</div>
        <h1>Oops! This page hopped away</h1>
        <p>The page you're looking for doesn't exist or has been moved. Don't worry, let's help you find your way back!</p>

        <div class="buttons">
            <a href="/" class="btn btn-primary">Back to Home</a>
            <a href="/products" class="btn btn-outline">Browse Products</a>
        </div>

        <div class="search-box">
            <p>Or try searching for what you need:</p>
            <form class="search-form" action="/search" method="GET">
                <input type="text" name="q" placeholder="Search products..." required>
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
</body>
</html>
