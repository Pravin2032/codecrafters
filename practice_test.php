<?php
$servername = "localhost";  // Change if needed
$username = "root";  // Change to your database username
$password = "";  // Change to your database password
$dbname = "codecrafters";  // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to insert certificate details
function saveCertificate($name, $email, $language, $score, $certificate_id) {
    global $conn;

    $date_issued = date("Y-m-d");
    $stmt = $conn->prepare("INSERT INTO certificates (user_name, email, language, score, date_issued, certificate_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiss", $name, $email, $language, $score, $date_issued, $certificate_id);

    if ($stmt->execute()) {
        return "Certificate saved successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
}

// Example usage
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $language = $_POST['language'];
    $score = $_POST['score'];
    $certificate_id = strtoupper(bin2hex(random_bytes(6)));  // Generate a 12-character certificate ID

    echo saveCertificate($name, $email, $language, $score, $certificate_id);
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coding Practice Test</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #f72585;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #8d99ae;
            --shadow: 0 4px 6px rgba(0,0,0,0.08);
            --border-radius: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: #edf2fb;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        .app-container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 25px 30px;
            position: relative;
        }

        .card-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .card-body {
            padding: 30px;
        }

        .input-group {
            margin-bottom: 24px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .input-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: border 0.3s;
        }

        .input-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        select.input-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b2d42' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-icon {
            margin-right: 8px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #05c090;
        }

        .btn-secondary {
            background-color: var(--light);
            color: var(--dark);
            border: 1px solid #e9ecef;
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
        }

        .progress-container {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 16px 0 24px;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--primary);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .question-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .question-number {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
        }

        .question-content {
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 500;
        }

        .question-content pre {
            background-color: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 16px 0;
            border: 1px solid #e9ecef;
            font-family: monospace;
            font-size: 14px;
        }

        .options-list {
            list-style-type: none;
        }

        .option-item {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .option-item:hover {
            border-color: #d1d9e6;
        }

        .option-item.selected {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }

        .option-radio {
            display: none;
        }

        .option-custom-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d9e6;
            border-radius: 50%;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .option-item.selected .option-custom-radio {
            border-color: var(--primary);
        }

        .option-item.selected .option-custom-radio::after {
            content: "";
            width: 10px;
            height: 10px;
            background-color: var(--primary);
            border-radius: 50%;
        }

        .option-text {
            flex-grow: 1;
        }

        .navigation {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .hide {
            display: none;
        }

        .result-container {
            text-align: center;
            padding: 20px 0;
        }

        .score-display {
            margin: 30px 0;
        }

        .score-value {
            font-size: 64px;
            font-weight: bold;
            color: var(--primary);
        }

        .score-label {
            font-size: 18px;
            color: var(--gray);
        }

        .certificate {
            background-color: white;
            border: 2px solid var(--primary);
            border-radius: var(--border-radius);
            padding: 40px;
            margin: 40px auto;
            position: relative;
            max-width: 700px;
            box-shadow: var(--shadow);
        }

        .certificate-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .certificate-title {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .certificate-content {
            margin: 30px 0;
            text-align: center;
        }

        .recipient-name {
            font-size: 28px;
            font-weight: bold;
            margin: 15px 0;
            color: var(--dark);
        }

        .seal {
            position: absolute;
            right: 40px;
            bottom: 40px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            transform: rotate(-15deg);
            box-shadow: var(--shadow);
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 30px;
        }

        .fade-transition {
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray);
        }

        /* Certificate ID styles */
        .certificate-id {
            position: absolute;
            left: 40px;
            bottom: 40px;
            font-size: 12px;
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .card-header, .card-body {
                padding: 20px;
            }
            
            .certificate {
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
      /* Certificate Styles */
      .certificate {
        width: 100%;
        max-width: 800px;
        margin: 30px auto;
        padding: 20px;
        border: 15px solid #37474f;
        position: relative;
        background-color: #fff;
        box-shadow: 0 0 25px rgba(0,0,0,0.2);
        display: none; /* Hidden by default, shown only for passing students */
      }

      .certificate-header {
        text-align: center;
        border-bottom: 2px solid #37474f;
        padding-bottom: 10px;
        margin-bottom: 40px;
      }

      .certificate-title {
        color: #37474f;
        font-size: 28px;
        margin: 0;
      }

      .certificate-content {
        text-align: center;
        margin-bottom: 40px;
      }

      .recipient-name {
        font-size: 24px;
        color: #1976d2;
        margin: 20px 0;
      }

      .certificate-id {
        position: absolute;
        bottom: 15px;
        left: 20px;
        color: #666;
        font-size: 12px;
      }

      .seal {
        position: absolute;
        right: 40px;
        bottom: 40px;
        font-size: 18px;
        color: #4caf50;
        border: 2px dashed #4caf50;
        padding: 15px;
        border-radius: 50%;
        transform: rotate(-15deg);
        height: 60px;
        width: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      /* Success and Failure Messages */
      .alert {
        margin: 20px 0;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        align-items: center;
      }

      .alert i {
        margin-right: 10px;
        font-size: 20px;
      }

      .alert-success {
        background-color: #e8f5e9;
        color: #2e7d32;
        border-left: 5px solid #2e7d32;
      }

      .alert-warning {
        background-color: #fff8e1;
        color: #f57f17;
        border-left: 5px solid #f57f17;
      }

      /* Certificate Actions */
      .actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
        flex-wrap: wrap;
      }

      /* Incorrect Answers Section */
      .incorrect-section {
        margin-top: 30px;
        border-top: 1px solid #ddd;
        padding-top: 20px;
      }

      .incorrect-item {
        margin-bottom: 15px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
      }

      .incorrect-question {
        font-weight: bold;
        margin-bottom: 10px;
      }

      .incorrect-details {
        display: flex;
        flex-direction: column;
        gap: 5px;
      }

      .answer.wrong {
        color: #d32f2f;
      }

      .answer.correct {
        color: #2e7d32;
      }

      .answer span {
        font-weight: bold;
      }
      /* Add these styles to your existing CSS for radio button styling */
.radio-option {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
  padding: 12px 15px;
  border-radius: 8px;
  background-color: #f5f7fa;
  transition: all 0.3s ease;
  cursor: pointer;
}

.radio-option:hover {
  background-color: #e9edf2;
}

.option-radio {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.option-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  width: 100%;
}

.option-marker {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background-color: #e1e5eb;
  color: #37474f;
  font-weight: bold;
  margin-right: 12px;
  transition: all 0.3s ease;
}

.option-text {
  flex: 1;
}

/* Styling for selected radio option */
.option-radio:checked + .option-label .option-marker {
  background-color: #1976d2;
  color: white;
}

.option-radio:checked + .option-label {
  font-weight: 500;
}

.option-radio:checked + .option-label .option-text {
  color: #1976d2;
}

.option-radio:focus + .option-label .option-marker {
  box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.4);
}

/* Style for the radio option when selected */
.radio-option:has(.option-radio:checked) {
  background-color: #e3f2fd;
  border-left: 4px solid #1976d2;
}
/* Dashboard Button Styling */
.card-header {
    position: relative;  /* Makes this the positioning context */
}

.dashboard-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 8px 12px;
    background-color: #4285f4;
    color: white;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.dashboard-btn:hover {
    background-color: #3367d6;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.dashboard-btn i {
    margin-right: 6px;
}

/* Responsive adjustment */
@media (max-width: 576px) {
    .dashboard-btn {
        top: 10px;
        right: 10px;
        font-size: 12px;
        padding: 6px 10px;
    }
}
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Welcome Section -->
        <div id="intro" class="card fade-transition">
            <div class="card-header">
                <h1>Coding Practice Test</h1>
                <p>Test your programming knowledge and earn a certificate</p>
                <a href="student_dashboard.php" class="btn btn-primary dashboard-btn">
    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
</a>
            </div>

            
            <div class="card-body">
                <div class="input-group">
                    <label for="username">Your Name</label>
                    <input type="text" id="username" placeholder="Enter your full name" class="input-control">
                </div>
                <div class="input-group">
                    <label for="email">Your Email</label>
                    <input type="email" id="email" placeholder="Enter your email address" class="input-control">
                </div>
                <div class="input-group">
                    <label for="language">Programming Language</label>
                    <select id="language" class="input-control">
                        <option value="javascript">JavaScript</option>
                        <option value="python">Python</option>
                        <option value="java">Java</option>
                        <option value="csharp">C#</option>
                        <option value="c">C</option>
                        <option value="cpp">C++</option>
                        <option value="html">HTML</option>
                    </select>
                </div>
                <button id="start-test" class="btn btn-primary">
                    <i class="fas fa-play btn-icon"></i>Start Test
                </button>
            </div>
        </div>

        <!-- Test Section -->
        <div id="test" class="hide fade-transition">
            <div class="card">
                <div class="card-header">
                    <h1>Coding Practice Test</h1>
                    <p id="test-language">JavaScript Test</p>
                </div>
                <div class="card-body">
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 0%"></div>
                    </div>
                    <div id="question-container"></div>
                    <div class="navigation">
                        <button id="next-question" class="btn btn-primary">
                            Next Question <i class="fas fa-arrow-right btn-icon" style="margin-left: 8px; margin-right: 0;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Section -->
        <div id="result" class="hide fade-transition">
            <div class="card">
                <div class="card-header">
                    <h1>Test Complete!</h1>
                    <p>See your results below</p>
                </div>
                <div class="card-body">
                    <div class="result-container">
                        <div class="score-display">
                            <div class="score-value" id="score-value">0</div>
                            <div class="score-label">Your Score (%)</div>
                        </div>

                        <div class="summary-stats">
                            <div class="stat-item">
                                <div class="stat-value" id="correct-answers">0</div>
                                <div class="stat-label">Correct</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="total-questions">15</div>
                                <div class="stat-label">Questions</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="time-spent">0</div>
                                <div class="stat-label">Minutes</div>
                            </div>
                        </div>

                        <div id="certificate" class="certificate">
                            <div class="certificate-header">
                                <h2 class="certificate-title">Certificate of Completion</h2>
                            </div>
                            <div class="certificate-content">
                                <p>This certifies that</p>
                                <h3 class="recipient-name" id="cert-name">Student Name</h3>
                                <p>has successfully completed the <span id="cert-language">Programming Language</span> practice test</p>
                                <p>with a score of <span id="cert-score">0</span>%</p>
                                <p>Date: <span id="cert-date"></span></p>
                            </div>
                            <div class="certificate-id" id="cert-id">Certificate ID: ABC123456789</div>
                            <div class="seal">PASSED</div>
                        </div>

                        <div class="actions">
                            <button id="print-certificate" class="btn btn-success">
                                <i class="fas fa-print btn-icon"></i>Print Certificate
                            </button>
                            <button id="download-certificate" class="btn btn-primary">
                                <i class="fas fa-download btn-icon"></i>Download Certificate
                            </button>
                            <button id="take-new-test" class="btn btn-secondary">
                                <i class="fas fa-redo btn-icon"></i>Take Another Test
                            </button>
                        </div>
                        
                        <!-- Incorrect Answers Section -->
                        <div id="incorrect-section" class="incorrect-section" style="display: none;">
                            <h3>Review Incorrect Answers</h3>
                            <div id="incorrect-answers"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      // Questions database - 15 questions for each language
const questionsDatabase = {
  javascript: [
    {
      question: "What is the output of: console.log(typeof null);",
      options: ["undefined", "null", "object", "string"],
      correctAnswer: 2
    },
    {
      question: "How do you declare a JavaScript variable?",
      options: ["variable myVar;", "v myVar;", "var myVar;", "$myVar = "],
      correctAnswer: 2
    },
    {
      question: "Which method adds an element at the end of an array?",
      options: ["push()", "append()", "addToEnd()", "insert()"],
      correctAnswer: 0
    },
    {
      question: "What does DOM stand for?",
      options: ["Document Object Model", "Display Object Management", "Digital Ordinance Model", "Document Order Mode"],
      correctAnswer: 0
    },
    {
      question: "How do you create a function in JavaScript?",
      options: ["function:myFunction()", "function = myFunction()", "function myFunction()", "create myFunction()"],
      correctAnswer: 2
    },
    {
      question: "Which operator is used to assign a value to a variable?",
      options: ["*", "-", "=", "=="],
      correctAnswer: 2
    },
    {
      question: "What is the correct way to write a JavaScript array?",
      options: ["var colors = (1:'red', 2:'green', 3:'blue')", "var colors = ['red', 'green', 'blue']", "var colors = 'red', 'green', 'blue'", "var colors = {red, green, blue}"],
      correctAnswer: 1
    },
    {
      question: "How do you find the number with the highest value of x and y?",
      options: ["Math.ceil(x, y)", "top(x, y)", "Math.max(x, y)", "Math.highest(x, y)"],
      correctAnswer: 2
    },
    {
      question: "What is the correct JavaScript syntax to change the content of an HTML element with id='demo'?",
      options: ["document.getElement('demo').innerHTML = 'Hello';", "document.getElementById('demo').innerHTML = 'Hello';", "#demo.innerHTML = 'Hello';", "document.getElementByName('demo').innerHTML = 'Hello';"],
      correctAnswer: 1
    },
    {
      question: "How do you write an IF statement in JavaScript?",
      options: ["if i = 5 then", "if (i == 5)", "if i == 5", "if i = 5"],
      correctAnswer: 1
    },
    {
      question: "How does a WHILE loop start?",
      options: ["while (i <= 10; i++)", "while i = 1 to 10", "while (i <= 10)", "while (i++)"],
      correctAnswer: 2
    },
    {
      question: "How do you round the number 7.25, to the nearest integer?",
      options: ["round(7.25)", "Math.rnd(7.25)", "Math.round(7.25)", "rnd(7.25)"],
      correctAnswer: 2
    },
    {
      question: "Which event occurs when the user clicks on an HTML element?",
      options: ["onmouseclick", "onclick", "onchange", "onmouseover"],
      correctAnswer: 1
    },
    {
      question: "How do you declare a JavaScript constant?",
      options: ["const carName;", "var const = carName;", "const carName = 'Volvo';", "constant carName = 'Volvo';"],
      correctAnswer: 2
    },
    {
      question: "Which operator is used to compare both value and type?",
      options: ["==", "===", "=", "!="],
      correctAnswer: 1
    }
  ],
  python: [
    {
      question: "What is the correct way to create a function in Python?",
      options: ["function myFunction():", "def myFunction():", "create myFunction():", "func myFunction():"],
      correctAnswer: 1
    },
    {
      question: "How do you insert COMMENTS in Python code?",
      options: ["// This is a comment", "/* This is a comment */", "# This is a comment", "-- This is a comment"],
      correctAnswer: 2
    },
    {
      question: "Which one is NOT a legal variable name?",
      options: ["my_var", "_myvar", "my-var", "myVar"],
      correctAnswer: 2
    },
    {
      question: "How do you create a variable with the numeric value 5?",
      options: ["x = 5", "x = int(5)", "Both of these are correct", "None of these are correct"],
      correctAnswer: 2
    },
    {
      question: "What is the correct file extension for Python files?",
      options: [".pyt", ".pt", ".pyth", ".py"],
      correctAnswer: 3
    },
    {
      question: "How do you create a list in Python?",
      options: ["list = (1, 2, 3)", "list = [1, 2, 3]", "array(1, 2, 3)", "list = {1, 2, 3}"],
      correctAnswer: 1
    },
    {
      question: "Which method can be used to remove any whitespace from both the beginning and the end of a string?",
      options: ["strip()", "ptrim()", "trim()", "len()"],
      correctAnswer: 0
    },
    {
      question: "Which method can be used to return a string in upper case letters?",
      options: ["uppercase()", "upper()", "toUpperCase()", "upperCase()"],
      correctAnswer: 1
    },
    {
      question: "Which operator is used to multiply numbers?",
      options: ["%", "x", "*", "#"],
      correctAnswer: 2
    },
    {
      question: "Which operator can be used to compare two values?",
      options: ["=", "<>", "==", "><"],
      correctAnswer: 2
    },
    {
      question: "Which of these collections defines a LIST?",
      options: ["{'apple', 'banana', 'cherry'}", "('apple', 'banana', 'cherry')", "['apple', 'banana', 'cherry']", "None of the above"],
      correctAnswer: 2
    },
    {
      question: "How do you start writing an if statement in Python?",
      options: ["if (x > y)", "if x > y:", "if x > y then:", "if (x > y):"],
      correctAnswer: 1
    },
    {
      question: "How do you start writing a while loop in Python?",
      options: ["while x > y {", "while (x > y)", "while x > y:", "x > y while:"],
      correctAnswer: 2
    },
    {
      question: "What is the correct syntax to output the type of a variable or object?",
      options: ["print(typeof(x))", "print(typeOf(x))", "print(type(x))", "print(typeof x)"],
      correctAnswer: 2
    },
    {
      question: "In Python, 'Hello World' is the same as \"Hello World\"",
      options: ["True", "False", "Depends on context", "Neither are valid"],
      correctAnswer: 0
    }
  ],
  java: [
    // 15 Java questions
    {
      question: "What is a correct syntax to output \"Hello World\" in Java?",
      options: ["echo(\"Hello World\");", "System.out.println(\"Hello World\");", "print(\"Hello World\");", "Console.WriteLine(\"Hello World\");"],
      correctAnswer: 1
    },
    {
      question: "Java is short for \"JavaScript\".",
      options: ["True", "False", "Depends on version", "Partially true"],
      correctAnswer: 1
    },
    {
      question: "How do you insert COMMENTS in Java code?",
      options: ["# This is a comment", "// This is a comment", "/* This is a comment", "<!-- This is a comment -->"],
      correctAnswer: 1
    },
    {
      question: "Which data type is used to create a variable that should store text?",
      options: ["string", "String", "myString", "txt"],
      correctAnswer: 1
    },
    {
      question: "How do you create a variable with the numeric value 5?",
      options: ["x = 5;", "int x = 5;", "num x = 5;", "float x = 5;"],
      correctAnswer: 1
    },
    {
      question: "Which method can be used to find the length of a string?",
      options: ["len()", "length()", "getLength()", "getSize()"],
      correctAnswer: 1
    },
    {
      question: "Which operator is used to add together two values?",
      options: ["The & sign", "The * sign", "The + sign", "The - sign"],
      correctAnswer: 2
    },
    {
      question: "The value of a string variable can be surrounded by single quotes.",
      options: ["True", "False", "Depends on the string", "Only in special cases"],
      correctAnswer: 1
    },
    {
      question: "Which operator can be used to compare two values?",
      options: ["><", "<>", "==", "="],
      correctAnswer: 2
    },
    {
      question: "Which of these collections defines an array?",
      options: ["{a, b, c}", "[a, b, c]", "(a, b, c)", "None of these"],
      correctAnswer: 1
    },
    {
      question: "How do you create a function in Java?",
      options: ["(public void) myMethod()", "public myMethod()", "public static void myMethod()", "func myMethod()"],
      correctAnswer: 2
    },
    {
      question: "How do you call a method in Java?",
      options: ["methodName;", "methodName[];", "methodName();", "(methodName);"],
      correctAnswer: 2
    },
    {
      question: "Which keyword is used to create a class in Java?",
      options: ["class", "className", "MyClass", "new"],
      correctAnswer: 0
    },
    {
      question: "What is the correct way to create an object called myObj of MyClass?",
      options: ["MyClass myObj = new MyClass();", "new myObj = MyClass();", "class MyClass = new myObj();", "class myObj = new MyClass();"],
      correctAnswer: 0
    },
    {
      question: "Which keyword is used to import a package from the Java API library?",
      options: ["package", "import", "lib", "getlib"],
      correctAnswer: 1
    }
  ],
  csharp: [
    // 15 C# questions
    {
      question: "What is a correct syntax to output \"Hello World\" in C#?",
      options: ["Console.WriteLine(\"Hello World\");", "System.out.println(\"Hello World\");", "echo(\"Hello World\");", "print(\"Hello World\");"],
      correctAnswer: 0
    },
    {
      question: "C# is pronounced as \"C Sharp\".",
      options: ["True", "False", "It's pronounced C Hash", "It's pronounced C Plus Plus Plus"],
      correctAnswer: 0
    },
    {
      question: "How do you insert COMMENTS in C# code?",
      options: ["# This is a comment", "// This is a comment", "/* This is a comment", "<!-- This is a comment -->"],
      correctAnswer: 1
    },
    {
      question: "Which data type is used to create a variable that should store text?",
      options: ["Txt", "string", "myString", "str"],
      correctAnswer: 1
    },
    {
      question: "How do you create a variable with the numeric value 5?",
      options: ["int x = 5;", "x = 5;", "num x = 5;", "x = 5 int;"],
      correctAnswer: 0
    },
    {
      question: "Which method can be used to find the length of a string?",
      options: ["length()", "getLength()", "len()", "Length"],
      correctAnswer: 3
    },
    {
      question: "Which operator is used to add together two values?",
      options: ["The & sign", "The + sign", "The * sign", "The - sign"],
      correctAnswer: 1
    },
    {
      question: "The value of a string variable can be surrounded by single quotes.",
      options: ["True", "False", "Only in special cases", "Depends on the string"],
      correctAnswer: 1
    },
    {
      question: "Which operator can be used to compare two values?",
      options: ["==", "><", "<>", "="],
      correctAnswer: 0
    },
    {
      question: "Arrays in C# are zero indexed.",
      options: ["True", "False", "Depends on the array type", "Only string arrays"],
      correctAnswer: 0
    },
    {
      question: "How do you create a method in C#?",
      options: ["methodName()", "public void methodName()", "static void methodName()", "methodName[]"],
      correctAnswer: 1
    },
    {
      question: "How do you call a method in C#?",
      options: ["methodName;", "methodName();", "methodName[];", "(methodName);"],
      correctAnswer: 1
    },
    {
      question: "Which keyword is used to create a class in C#?",
      options: ["class", "className", "MyClass", "new"],
      correctAnswer: 0
    },
    {
      question: "What is the correct way to create an object called myObj of MyClass?",
      options: ["MyClass myObj = new MyClass();", "new myObj = MyClass();", "class myObj = new MyClass();", "MyClass myObj = MyClass();"],
      correctAnswer: 0
    },
    {
      question: "Which statement is used to stop the execution of a loop?",
      options: ["exit;", "return;", "break;", "stop;"],
      correctAnswer: 2
    }
  ],
  c: [
    // 15 C questions
    {
      question: "What is the correct way to declare an integer variable in C?",
      options: ["int x;", "x = int;", "integer x;", "var x;"],
      correctAnswer: 0
    },
    {
      question: "Which function is used to output text in C?",
      options: ["System.out.println()", "cout", "print()", "printf()"],
      correctAnswer: 3
    },
    {
      question: "Which header file should be included to use printf() function?",
      options: ["<math.h>", "<stdio.h>", "<string.h>", "<stdlib.h>"],
      correctAnswer: 1
    },
    {
      question: "How do you insert COMMENTS in C code?",
      options: ["# This is a comment", "// This is a comment", "/* This is a comment */", "<!-- This is a comment -->"],
      correctAnswer: 2
    },
    {
      question: "Which operator is used to get the memory address of a variable?",
      options: ["*", "&", "#", "@"],
      correctAnswer: 1
    },
    {
      question: "What is the correct way to create a function in C?",
      options: ["void myFunction()", "function myFunction()", "def myFunction()", "create myFunction()"],
      correctAnswer: 0
    },
    {
      question: "Which of the following is a correct way to declare a pointer?",
      options: ["int p;", "int *p;", "pointer p;", "int^p;"],
      correctAnswer: 1
    },
    {
      question: "How do you call a function in C?",
      options: ["myFunction;", "myFunction[];", "myFunction();", "(myFunction);"],
      correctAnswer: 2
    },
    {
      question: "Array index in C starts from:",
      options: ["1", "0", "-1", "Depends on the compiler"],
      correctAnswer: 1
    },
    {
      question: "Which keyword is used to create a structure in C?",
      options: ["str", "structure", "struct", "sturct"],
      correctAnswer: 2
    },
    {
      question: "Which operator is used to access the members of a structure through a pointer?",
      options: [".", "->", "::", "&"],
      correctAnswer: 1
    },
    {
      question: "Which function is used to allocate memory dynamically?",
      options: ["alloc()", "malloc()", "calloc()", "Both B and C"],
      correctAnswer: 3
    },
    {
      question: "The size of an int data type is:",
      options: ["2 bytes", "4 bytes", "Compiler dependent", "8 bytes"],
      correctAnswer: 2
    },
    {
      question: "What does the 'void' keyword mean?",
      options: ["Empty", "No return type", "Non-existent", "All of these"],
      correctAnswer: 1
    },
    {
      question: "Which is not a storage class specifier in C?",
      options: ["static", "register", "auto", "volatile"],
      correctAnswer: 3
    }
  ],
  cpp: [
    // 15 C++ questions
    {
      question: "What is the correct way to output \"Hello World\" in C++?",
      options: ["System.out.println(\"Hello World\");", "cout << \"Hello World\";", "printf(\"Hello World\");", "print(\"Hello World\");"],
      correctAnswer: 1
    },
    {
      question: "Which header file should be included to use cout?",
      options: ["<stdio.h>", "<iostream>", "<string>", "<ostream>"],
      correctAnswer: 1
    },
    {
      question: "How do you insert COMMENTS in C++ code?",
      options: ["# This is a comment", "// This is a comment", "/* This is a comment", "<!-- This is a comment -->"],
      correctAnswer: 1
    },
    {
      question: "Which data type is used to create a variable that should store text?",
      options: ["string", "Text", "str", "String"],
      correctAnswer: 0
    },
    {
      question: "How do you create a variable with the numeric value 5?",
      options: ["int x = 5;", "x = 5;", "num x = 5;", "x = 5 int;"],
      correctAnswer: 0
    },
    {
      question: "Which operator is used to add together two values?",
      options: ["The & sign", "The + sign", "The * sign", "The - sign"],
      correctAnswer: 1
    },
    {
      question: "What is the correct syntax for inheritance in C++?",
      options: ["class Child : public Parent", "class Child extends Parent", "class Child implements Parent", "class Child inherits Parent"],
      correctAnswer: 0
    },
    {
      question: "Which operator is used to access value at an address pointed by a pointer?",
      options: ["&", "*", "->", "#"],
      correctAnswer: 1
    },
    {
      question: "Which of the following is used to define a constant in C++?",
      options: ["#define", "const", "Both A and B", "constant"],
      correctAnswer: 2
    },
    {
      question: "Which keyword is used to define a class in C++?",
      options: ["class", "struct", "interface", "object"],
      correctAnswer: 0
    },
    {
      question: "How do you create an object in C++?",
      options: ["ClassName objectName = new ClassName();", "ClassName objectName();", "ClassName objectName;", "new ClassName objectName;"],
      correctAnswer: 2
    },
    {
      question: "Which concept allows you to implement multiple inheritance in C++?",
      options: ["Encapsulation", "Abstraction", "Polymorphism", "Multiple inheritance"],
      correctAnswer: 3
    },
    {
      question: "What is the purpose of 'virtual' keyword in C++?",
      options: ["To create virtual functions", "To declare a variable", "To define a constant", "To allocate memory"],
      correctAnswer: 0
    },
    // Complete the cut-off C++ question
    {
    question: "Which operator is used to overload functions in C++?",
      options: ["++", "&", "+", "None of these"],
      correctAnswer: 3
    },
    {
      question: "What does the scope resolution operator (::) do in C++?",
      options: ["Allocates memory", "Accesses global variables", "Accesses class members", "Resolves scope of variables and functions"],
      correctAnswer: 3
    }
  ],
  html: [
    // 15 HTML questions
    {
      question: "What does HTML stand for?",
      options: ["Hyper Text Markup Language", "Home Tool Markup Language", "Hyperlinks and Text Markup Language", "Hyper Technical Markup Logic"],
      correctAnswer: 0
    },
    {
      question: "Which tag is used to define an unordered list?",
      options: ["<ol>", "<ul>", "<li>", "<list>"],
      correctAnswer: 1
    },
    {
      question: "Which HTML attribute specifies an alternate text for an image, if the image cannot be displayed?",
      options: ["title", "alt", "src", "longdesc"],
      correctAnswer: 1
    },
    {
      question: "Which doctype is correct for HTML5?",
      options: ["<!DOCTYPE html>", "<!DOCTYPE HTML5>", "<!DOCTYPE HTML PUBLIC>", "<!DOCTYPE HTML-5>"],
      correctAnswer: 0
    },
    {
      question: "Which HTML element is used to specify a header for a document or section?",
      options: ["<head>", "<header>", "<top>", "<h1>"],
      correctAnswer: 1
    },
    {
      question: "How do you create a hyperlink in HTML?",
      options: ["<a url=\"http://example.com\">Example</a>", "<a href=\"http://example.com\">Example</a>", "<a>http://example.com</a>", "<hyperlink href=\"http://example.com\">Example</hyperlink>"],
      correctAnswer: 1
    },
    {
      question: "Which HTML element defines the title of a document?",
      options: ["<meta>", "<head>", "<title>", "<header>"],
      correctAnswer: 2
    },
    {
      question: "Which tag is used to create a table in HTML?",
      options: ["<table>", "<tb>", "<tr>", "<tab>"],
      correctAnswer: 0
    },
    {
      question: "Which element is used to create a line break?",
      options: ["<lb>", "<break>", "<br>", "<newline>"],
      correctAnswer: 2
    },
    {
      question: "Which HTML element defines navigation links?",
      options: ["<navigation>", "<nav>", "<navigate>", "<links>"],
      correctAnswer: 1
    },
    {
      question: "Which HTML element is used to specify a footer for a document or section?",
      options: ["<footer>", "<bottom>", "<section>", "<foot>"],
      correctAnswer: 0
    },
    {
      question: "Which character is used to indicate an end tag?",
      options: ["^", "/", "*", "<"],
      correctAnswer: 1
    },
    {
      question: "Which attribute is used to provide a unique name to an HTML element?",
      options: ["id", "class", "name", "unique"],
      correctAnswer: 0
    },
    {
      question: "Which HTML element is used to specify a main content area?",
      options: ["<section>", "<content>", "<main>", "<article>"],
      correctAnswer: 2
    },
    {
      question: "How do you make a numbered list in HTML?",
      options: ["<nl>", "<ol>", "<list>", "<dl>"],
      correctAnswer: 1
    }
  ]
};

// Questions database remains the same as before

// Global variables
let currentQuestionIndex = 0;
let selectedLanguage = "";
let selectedAnswers = [];
let startTime = 0;
let userName = "";
let userEmail = "";

// DOM elements
const introSection = document.getElementById("intro");
const testSection = document.getElementById("test");
const resultSection = document.getElementById("result");
const startTestBtn = document.getElementById("start-test");
const nextQuestionBtn = document.getElementById("next-question");
const questionContainer = document.getElementById("question-container");
const progressBar = document.querySelector(".progress-bar");
const testLanguageTitle = document.getElementById("test-language");
const certificateSection = document.getElementById("certificate");
const scoreValue = document.getElementById("score-value");
const correctAnswers = document.getElementById("correct-answers");
const totalQuestions = document.getElementById("total-questions");
const timeSpent = document.getElementById("time-spent");
const printCertificateBtn = document.getElementById("print-certificate");
const downloadCertificateBtn = document.getElementById("download-certificate");
const takeNewTestBtn = document.getElementById("take-new-test");
const incorrectSection = document.getElementById("incorrect-section");
const incorrectAnswers = document.getElementById("incorrect-answers");
const certName = document.getElementById("cert-name");
const certLanguage = document.getElementById("cert-language");
const certScore = document.getElementById("cert-score");
const certDate = document.getElementById("cert-date");
const certId = document.getElementById("cert-id");

// Event listeners
startTestBtn.addEventListener("click", startTest);
nextQuestionBtn.addEventListener("click", nextQuestion);
printCertificateBtn.addEventListener("click", printCertificate);
downloadCertificateBtn.addEventListener("click", downloadCertificate);
takeNewTestBtn.addEventListener("click", resetTest);

// Start test function
function startTest() {
  // Validate inputs
  userName = document.getElementById("username").value.trim();
  userEmail = document.getElementById("email").value.trim();
  selectedLanguage = document.getElementById("language").value;
  
  if (!userName || !userEmail) {
    alert("Please enter your name and email to start the test.");
    return;
  }
  
  if (!validateEmail(userEmail)) {
    alert("Please enter a valid email address.");
    return;
  }
  
  // Initialize test
  currentQuestionIndex = 0;
  selectedAnswers = Array(questionsDatabase[selectedLanguage].length).fill(-1);
  startTime = Date.now();
  
  // Update UI
  introSection.classList.add("hide");
  testSection.classList.remove("hide");
  testLanguageTitle.textContent = selectedLanguage.charAt(0).toUpperCase() + selectedLanguage.slice(1) + " Test";
  
  // Load first question
  loadQuestion();
}

// Load question function
function loadQuestion() {
  const questionData = questionsDatabase[selectedLanguage][currentQuestionIndex];
  const totalQuestions = questionsDatabase[selectedLanguage].length;
  
  // Update progress bar
  const progress = ((currentQuestionIndex + 1) / totalQuestions) * 100;
  progressBar.style.width = progress + "%";
  
  // Build question HTML
  let questionHTML = `
    <div class="question-header">
      <h3>Question ${currentQuestionIndex + 1} of ${totalQuestions}</h3>
    </div>
    <div class="question-content">
      <p class="question-text">${questionData.question}</p>
      <div class="options-container">
  `;
  
  // Add options with radio buttons
  questionData.options.forEach((option, index) => {
    const isSelected = selectedAnswers[currentQuestionIndex] === index;
    const optionId = `option-${index}`;
    questionHTML += `
      <div class="option radio-option">
        <input type="radio" id="${optionId}" name="question-option" value="${index}" 
          ${isSelected ? 'checked' : ''} class="option-radio">
        <label for="${optionId}" class="option-label">
          <span class="option-marker">${String.fromCharCode(65 + index)}</span>
          <span class="option-text">${option}</span>
        </label>
      </div>
    `;
  });
  
  questionHTML += `</div></div>`;
  
  questionContainer.innerHTML = questionHTML;
  
  // Add event listeners to radio buttons
  const radioButtons = document.querySelectorAll('input[name="question-option"]');
  radioButtons.forEach(radio => {
    radio.addEventListener("change", selectRadioOption);
  });
  
  // Update next button state
  nextQuestionBtn.disabled = selectedAnswers[currentQuestionIndex] === -1;
  
  // Update button text on last question
  if (currentQuestionIndex === totalQuestions - 1) {
    nextQuestionBtn.innerHTML = `Finish Test <i class="fas fa-flag-checkered btn-icon" style="margin-left: 8px; margin-right: 0;"></i>`;
  } else {
    nextQuestionBtn.innerHTML = `Next Question <i class="fas fa-arrow-right btn-icon" style="margin-left: 8px; margin-right: 0;"></i>`;
  }
}

// Select option function for radio buttons
function selectRadioOption(event) {
  const optionIndex = parseInt(event.target.value);
  
  // Update selected answers array
  selectedAnswers[currentQuestionIndex] = optionIndex;
  
  // Enable next button
  nextQuestionBtn.disabled = false;
}

// Next question function
function nextQuestion() {
  const totalQuestions = questionsDatabase[selectedLanguage].length;
  
  if (currentQuestionIndex < totalQuestions - 1) {
    // Move to next question
    currentQuestionIndex++;
    loadQuestion();
  } else {
    // End the test
    finishTest();
  }
}

// Finish test function
function finishTest() {
  const totalQuestions = questionsDatabase[selectedLanguage].length;
  let correctCount = 0;
  const incorrectQuestions = [];
  
  // Calculate score
  selectedAnswers.forEach((selectedAnswer, index) => {
    const question = questionsDatabase[selectedLanguage][index];
    if (selectedAnswer === question.correctAnswer) {
      correctCount++;
    } else {
      incorrectQuestions.push({
        question: question.question,
        selectedAnswer: selectedAnswer >= 0 ? question.options[selectedAnswer] : "Not answered",
        correctAnswer: question.options[question.correctAnswer]
      });
    }
  });
  
  // Calculate percentage
  const scorePercentage = Math.round((correctCount / totalQuestions) * 100);
  
  // Calculate time spent
  const timeInMinutes = Math.ceil((Date.now() - startTime) / 60000);
  
  // Update UI
  testSection.classList.add("hide");
  resultSection.classList.remove("hide");
  
  scoreValue.textContent = scorePercentage;
  correctAnswers.textContent = correctCount;
  totalQuestions.textContent = totalQuestions;
  timeSpent.textContent = timeInMinutes;
  
  // Show certificate for passing score (70% or higher)
  if (scorePercentage >= 70) {
    certificateSection.style.display = "block";
    
    // Update certificate details
    certName.textContent = userName;
    certLanguage.textContent = selectedLanguage.charAt(0).toUpperCase() + selectedLanguage.slice(1);
    certScore.textContent = scorePercentage;
    certDate.textContent = new Date().toLocaleDateString();
    
    // Generate certificate ID
    const certificateId = generateCertificateId();
    certId.textContent = "Certificate ID: " + certificateId;
    
    // Show certificate buttons
    printCertificateBtn.style.display = "inline-block";
    downloadCertificateBtn.style.display = "inline-block";
    
    // Save certificate to database
    saveCertificateToDatabase(certificateId, scorePercentage);
    
    // Show success message
    const successAlert = document.createElement("div");
    successAlert.className = "alert alert-success";
    successAlert.innerHTML = `<i class="fas fa-check-circle"></i> Congratulations! You've passed the test and earned a certificate.`;
    resultSection.querySelector(".card-body").insertBefore(successAlert, resultSection.querySelector(".result-container"));
  } else {
    // Hide certificate for failing score
    certificateSection.style.display = "none";
    
    // Hide certificate buttons
    printCertificateBtn.style.display = "none";
    downloadCertificateBtn.style.display = "none";
    
    // Show failure message
    const warningAlert = document.createElement("div");
    warningAlert.className = "alert alert-warning";
    warningAlert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> You need a score of 70% or higher to earn a certificate. Keep practicing!`;
    resultSection.querySelector(".card-body").insertBefore(warningAlert, resultSection.querySelector(".result-container"));
  }
  
  // Show incorrect answers section if there are any
  if (incorrectQuestions.length > 0) {
    incorrectSection.style.display = "block";
    
    // Build incorrect answers HTML
    let incorrectHTML = "";
    incorrectQuestions.forEach((item, index) => {
      incorrectHTML += `
        <div class="incorrect-item">
          <div class="incorrect-question">${index + 1}. ${item.question}</div>
          <div class="incorrect-details">
            <div class="answer wrong"><span>Your answer:</span> ${item.selectedAnswer}</div>
            <div class="answer correct"><span>Correct answer:</span> ${item.correctAnswer}</div>
          </div>
        </div>
      `;
    });
    
    incorrectAnswers.innerHTML = incorrectHTML;
  } else {
    incorrectSection.style.display = "none";
  }
}

// Generate certificate ID
function generateCertificateId() {
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
  let id = "";
  
  for (let i = 0; i < 12; i++) {
    id += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  
  return id;
}

// Save certificate to database
function saveCertificateToDatabase(certificateId, score) {
  // Create form data
  const formData = new FormData();
  formData.append("name", userName);
  formData.append("email", userEmail);
  formData.append("language", selectedLanguage);
  formData.append("score", score);
  formData.append("certificate_id", certificateId);
  
  // Send AJAX request
  fetch(window.location.href, {
    method: "POST",
    body: formData
  })
  .then(response => response.text())
  .then(data => {
    console.log("Certificate saved:", data);
  })
  .catch(error => {
    console.error("Error saving certificate:", error);
  });
}

// Print certificate function
function printCertificate() {
  const printWindow = window.open('', '', 'height=600,width=800');
  
  printWindow.document.write('<html><head><title>Certificate</title>');
  printWindow.document.write('<style>');
  printWindow.document.write(`
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    .certificate { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; border: 15px solid #37474f; position: relative; background-color: #fff; }
    .certificate-header { text-align: center; border-bottom: 2px solid #37474f; padding-bottom: 10px; margin-bottom: 40px; }
    .certificate-title { color: #37474f; font-size: 28px; margin: 0; }
    .certificate-content { text-align: center; margin-bottom: 40px; }
    .recipient-name { font-size: 24px; color: #1976d2; margin: 20px 0; }
    .certificate-id { position: absolute; bottom: 15px; left: 20px; color: #666; font-size: 12px; }
    .seal { position: absolute; right: 40px; bottom: 40px; font-size: 18px; color: #4caf50; border: 2px dashed #4caf50; padding: 15px; border-radius: 50%; transform: rotate(-15deg); height: 60px; width: 60px; display: flex; align-items: center; justify-content: center; }
  `);
  printWindow.document.write('</style></head><body>');
  printWindow.document.write(certificateSection.outerHTML);
  printWindow.document.write('</body></html>');
  
  printWindow.document.close();
  printWindow.focus();
  
  setTimeout(function() {
    printWindow.print();
    printWindow.close();
  }, 500);
}

// Download certificate function
function downloadCertificate() {
  html2canvas(certificateSection).then(canvas => {
    const image = canvas.toDataURL("image/png");
    const link = document.createElement("a");
    link.download = `${selectedLanguage}-certificate-${userName.replace(/\s+/g, '-')}.png`;
    link.href = image;
    link.click();
  });
}

// Reset test function
function resetTest() {
  // Reset to intro section
  resultSection.classList.add("hide");
  introSection.classList.remove("hide");
  
  // Clear inputs
  document.getElementById("username").value = "";
  document.getElementById("email").value = "";
  
  // Remove alerts
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach(alert => {
    alert.remove();
  });
}

// Email validation function
function validateEmail(email) {
  const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email.toLowerCase());
}

// Add html2canvas library for certificate download
document.addEventListener('DOMContentLoaded', function() {
  const script = document.createElement('script');
  script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
  document.head.appendChild(script);
});
</script>
</body>
</html>