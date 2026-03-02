<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
            font-family: Arial, sans-serif;
            text-align: center;
        }

        #success-msg {
            color: green;
            margin-bottom: 20px;
        }

        h1 {
            margin-bottom: 10px;
        }

        p {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- ✅ Show success message if present -->
    @if(session('success'))
        <div id="success-msg">
            {{ session('success') }}
        </div>

        <script>
            setTimeout(() => {
                const msg = document.getElementById('success-msg');
                if(msg) msg.style.display = 'none';
            }, 3000);
        </script>
    @endif

    <h1>Welcome to the Staff Dashboard!</h1>
    <p>You are logged in as a staff member.</p>

    <!-- Remove logout button for now -->

</body>
</html>