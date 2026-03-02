<!DOCTYPE html>
<html>
<head>
    <title>Staff Login</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* full viewport height */
            font-family: Arial, sans-serif;
        }

        form {
            border: 1px solid #ccc;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #2d89ef;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #1b5fa7;
        }

        h2 {
            text-align: center;
        }

        .error-message {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('staff.login.submit') }}" novalidate>
        @csrf
        <h2>Staff Login</h2>

        <div>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email">
            @error('email')
                <div class="error-message">@ {{ $message }}</div>
            @enderror
        </div>

        <div>
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password">
            @error('password')
                <div class="error-message">@ {{ $message }}</div>
            @enderror
        </div>

        <button type="submit">Login</button>
    </form>
</body>
</html>