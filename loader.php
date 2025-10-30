<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0e27;
            overflow: hidden;
            font-family: 'Courier New', monospace;
        }

        #loader-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0a0e27;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        #loader-container.fade-out {
            opacity: 0;
            pointer-events: none;
        }

        /* Matrix Rain Background */
        #matrix-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        /* Loader Content */
        .loader-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        /* Hacker Icon */
        .hacker-icon {
            width: 150px;
            height: 150px;
            margin: 0 auto 30px;
            position: relative;
            animation: float 3s ease-in-out infinite;
        }

        .hacker-mask {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #00ff41 0%, #00b8ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 30px rgba(0, 255, 65, 0.5),
                        0 0 60px rgba(0, 255, 65, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        .hacker-mask::before {
            content: '</>';
            font-size: 60px;
            font-weight: bold;
            color: #0a0e27;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 30px rgba(0, 255, 65, 0.5); }
            50% { transform: scale(1.05); box-shadow: 0 0 50px rgba(0, 255, 65, 0.8); }
        }

        /* Loading Text */
        .loading-text {
            color: #00ff41;
            font-size: 24px;
            letter-spacing: 3px;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(0, 255, 65, 0.8);
            animation: glitch 1s infinite;
        }

        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(2px, -2px); }
            60% { transform: translate(-2px, -2px); }
            80% { transform: translate(2px, 2px); }
        }

        /* Progress Bar */
        .progress-container {
            width: 300px;
            height: 4px;
            background: rgba(0, 255, 65, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin: 0 auto;
        }

        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #00ff41, #00b8ff);
            border-radius: 10px;
            animation: progress 2s ease-in-out;
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.8);
        }

        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }

        /* Terminal Lines */
        .terminal-lines {
            margin-top: 30px;
            text-align: left;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .terminal-line {
            color: #00ff41;
            font-size: 12px;
            opacity: 0;
            animation: fadeIn 0.3s forwards;
            margin: 5px 0;
        }

        .terminal-line:nth-child(1) { animation-delay: 0.2s; }
        .terminal-line:nth-child(2) { animation-delay: 0.4s; }
        .terminal-line:nth-child(3) { animation-delay: 0.6s; }
        .terminal-line:nth-child(4) { animation-delay: 0.8s; }
        .terminal-line:nth-child(5) { animation-delay: 1s; }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        .terminal-line .prompt {
            color: #00b8ff;
        }

        .terminal-line .success {
            color: #00ff41;
        }
    </style>
</head>
<body>
    <div id="loader-container">
        <canvas id="matrix-canvas"></canvas>

        <div class="loader-content">
            <div class="hacker-icon">
                <div class="hacker-mask"></div>
            </div>

            <div class="loading-text">INITIALIZING...</div>

            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>

            <div class="terminal-lines">
                <div class="terminal-line"><span class="prompt">$</span> Loading system modules...</div>
                <div class="terminal-line"><span class="prompt">$</span> Checking security protocols...</div>
                <div class="terminal-line"><span class="prompt">$</span> Establishing connection...</div>
                <div class="terminal-line"><span class="prompt">$</span> Decrypting portfolio data...</div>
                <div class="terminal-line"><span class="success">✓</span> System ready!</div>
            </div>
        </div>
    </div>

    <script>
        // Matrix Rain Effect
        const canvas = document.getElementById('matrix-canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const characters = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
        const fontSize = 14;
        const columns = canvas.width / fontSize;
        const drops = [];

        for (let i = 0; i < columns; i++) {
            drops[i] = Math.random() * canvas.height / fontSize;
        }

        function drawMatrix() {
            ctx.fillStyle = 'rgba(10, 14, 39, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = '#00ff41';
            ctx.font = fontSize + 'px monospace';

            for (let i = 0; i < drops.length; i++) {
                const text = characters.charAt(Math.floor(Math.random() * characters.length));
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }

        const matrixInterval = setInterval(drawMatrix, 35);

        // Redirect after 2 seconds
        setTimeout(() => {
            const loader = document.getElementById('loader-container');
            loader.classList.add('fade-out');

            setTimeout(() => {
                clearInterval(matrixInterval);
                window.location.href = 'home.php';
            }, 500);
        }, 2000);

        // Resize canvas on window resize
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
</body>
</html>
