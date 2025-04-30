<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programming Languages Tutorial</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --dark-color: #2b2d42;
            --light-color: #f8f9fa;
            --gray-color: #e9ecef;
            --text-color: #2b2d42;
            --text-light: #6c757d;
            --accent-color: #ff9f1c;
            --sidebar-width: 260px;
            --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --border-radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-primary);
            color: var(--text-color);
            line-height: 1.6;
            background-color: var(--light-color);
            overflow-x: hidden;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: white;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .sidebar-header h3 {
            color: var(--dark-color);
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .sidebar-menu {
            padding: 16px 0;
        }
        
        .menu-category {
            color: var(--text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            padding: 16px 24px 8px;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: var(--text-color);
            padding: 12px 24px;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            position: relative;
        }
        
        .sidebar a .icon {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
            color: var(--text-light);
            transition: all 0.2s ease;
        }
        
        .sidebar a:hover {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--primary-color);
        }
        
        .sidebar a:hover .icon {
            color: var(--primary-color);
        }
        
        .sidebar a.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .sidebar a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: var(--primary-color);
        }
        
        .sidebar a.active .icon {
            color: var(--primary-color);
        }
        
        /* Main Content Styles */
        .content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }
        
        .top-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: white;
            padding: 0 24px;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: var(--shadow-sm);
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 16px;
        }
        
        .search-bar {
            position: relative;
            margin-right: 24px;
        }
        
        .search-bar input {
            padding: 8px 16px 8px 40px;
            border: 1px solid var(--gray-color);
            border-radius: 20px;
            font-size: 0.9rem;
            width: 240px;
            transition: all 0.3s ease;
        }
        
        .search-bar input:focus {
            width: 300px;
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-bar i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .top-navbar-actions {
            display: flex;
            align-items: center;
        }
        
        .top-navbar-actions a {
            color: var(--text-color);
            text-decoration: none;
            margin-left: 20px;
            display: flex;
            align-items: center;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }
        
        .top-navbar-actions a:hover {
            color: var(--primary-color);
        }
        
        .top-navbar-actions a i {
            margin-right: 8px;
        }
        
        .main-content {
            padding: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 16px;
        }
        
        .page-description {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 700px;
        }
        
        /* Language Sections */
        .language-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .show {
            display: block;
        }
        
        /* Card Styles */
        .content-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 32px;
            margin-bottom: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .content-card h2 {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 16px;
            font-weight: 700;
        }
        
        .content-card h3 {
            color: var(--dark-color);
            margin-bottom: 12px;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .content-card p {
            margin-bottom: 12px;
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.6;
        }
        
        /* Code Examples */
        .code-example {
            background-color: #f8fafc;
            border-radius: var(--border-radius);
            padding: 24px;
            margin: 24px 0;
            position: relative;
            border: 1px solid #e2e8f0;
        }
        
        .code-example h3 {
            font-size: 1rem;
            margin-bottom: 16px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        pre.code {
            font-family: 'Fira Code', Consolas, 'Courier New', monospace;
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 16px;
            border-radius: var(--border-radius);
            overflow-x: auto;
            line-height: 1.5;
            font-size: 0.9rem;
        }
        
        .code-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .menu-toggle {
                display: block;
            }
            
            .search-bar input {
                width: 180px;
            }
            
            .search-bar input:focus {
                width: 220px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 24px 16px;
            }
            
            .content-card {
                padding: 24px;
            }
            
            .search-bar {
                display: none;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
        }
        
        /* Dark mode toggle */
        .dark-mode-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.2rem;
            cursor: pointer;
            margin-left: 20px;
            transition: color 0.2s ease;
        }
        
        .dark-mode-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Language badge for code */
        .language-badge {
            position: absolute;
            top: 24px;
            right: 24px;
            background-color: var(--accent-color);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Programming Hub</h3>
        </div>
        <div class="sidebar-menu">
            <div class="menu-category">Frontend</div>
            <a href="#" class="active" onclick="showLanguage('html')">
                <span class="icon"><i class="fab fa-html5"></i></span>
                HTML
            </a>
            <a href="#" onclick="showLanguage('css')">
                <span class="icon"><i class="fab fa-css3-alt"></i></span>
                CSS
            </a>
            <a href="#" onclick="showLanguage('javascript')">
                <span class="icon"><i class="fab fa-js"></i></span>
                JavaScript
            </a>
            
            <div class="menu-category">Backend</div>
            <a href="#" onclick="showLanguage('python')">
                <span class="icon"><i class="fab fa-python"></i></span>
                Python
            </a>
            <a href="#" onclick="showLanguage('java')">
                <span class="icon"><i class="fab fa-java"></i></span>
                Java
            </a>
            <a href="#" onclick="showLanguage('csharp')">
                <span class="icon"><i class="fab fa-microsoft"></i></span>
                C#
            </a>
            <a href="#" onclick="showLanguage('php')">
                <span class="icon"><i class="fab fa-php"></i></span>
                PHP
            </a>
            
            <div class="menu-category">Database</div>
            <a href="#" onclick="showLanguage('sql')">
                <span class="icon"><i class="fas fa-database"></i></span>
                SQL
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="content">
        <header class="top-navbar">
            <div class="nav-links">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search tutorials...">
                </div>
            </div>
            <div class="top-navbar-actions">
                <a href="student_dashboard.php">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </a>
                <button class="dark-mode-toggle" id="darkModeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Programming Languages</h1>
                <p class="page-description">Master the fundamentals of popular programming languages with interactive tutorials and examples.</p>
            </div>

            <!-- HTML Section -->
            <div id="html" class="language-section show">
                <div class="content-card">
                    <h2>HTML Tutorial</h2>
                    <p>HTML is the standard markup language for Web pages.</p>
                    <p>With HTML you can create your own Website.</p>
                    <p>HTML is easy to learn - You will enjoy it!</p>
                </div>
                
                <div class="content-card">
                    <h3>Example Explained</h3>
                    <p>The HTML document itself begins with &lt;html&gt; and ends with &lt;/html&gt;</p>
                    <p>The visible part of the HTML document is between &lt;body&gt; and &lt;/body&gt;</p>
                    
                    <div class="code-example">
                        <span class="language-badge">HTML</span>
                        <h3>Example</h3>
                        <pre class="code">&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;Page Title&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;

&lt;h1&gt;My First Heading&lt;/h1&gt;
&lt;p&gt;My first paragraph.&lt;/p&gt;

&lt;/body&gt;
&lt;/html&gt;</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('html-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>HTML Headings</h3>
                    <p>HTML headings are defined with the &lt;h1&gt; to &lt;h6&gt; tags.</p>
                    <p>&lt;h1&gt; defines the most important heading. &lt;h6&gt; defines the least important heading.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">HTML</span>
                        <h3>Example</h3>
                        <pre class="code">&lt;h1&gt;This is heading 1&lt;/h1&gt;
&lt;h2&gt;This is heading 2&lt;/h2&gt;
&lt;h3&gt;This is heading 3&lt;/h3&gt;</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('html-headings')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CSS Section -->
            <div id="css" class="language-section">
                <div class="content-card">
                    <h2>CSS Tutorial</h2>
                    <p>CSS is the language we use to style an HTML document.</p>
                    <p>CSS describes how HTML elements should be displayed.</p>
                </div>
                
                <div class="content-card">
                    <h3>CSS Syntax</h3>
                    <p>A CSS rule consists of a selector and a declaration block.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">CSS</span>
                        <h3>Example</h3>
                        <pre class="code">body {
  background-color: lightblue;
}

h1 {
  color: white;
  text-align: center;
}

p {
  font-family: verdana;
  font-size: 20px;
}</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('css-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>CSS Selectors</h3>
                    <p>CSS selectors are used to "find" (or select) the HTML elements you want to style.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">CSS</span>
                        <h3>Example</h3>
                        <pre class="code">/* Element Selector */
p {
  text-align: center;
  color: red;
}

/* ID Selector */
#para1 {
  text-align: center;
  color: red;
}

/* Class Selector */
.center {
  text-align: center;
  color: red;
}</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('css-selectors')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JavaScript Section -->
            <div id="javascript" class="language-section">
                <div class="content-card">
                    <h2>JavaScript Tutorial</h2>
                    <p>JavaScript is the world's most popular programming language.</p>
                    <p>JavaScript is the programming language of the Web.</p>
                </div>
                
                <div class="content-card">
                    <h3>What can JavaScript Do?</h3>
                    <p>JavaScript can change HTML content.</p>
                    <p>JavaScript can change HTML attribute values.</p>
                    <p>JavaScript can change HTML styles (CSS).</p>
                    <p>JavaScript can hide and show HTML elements.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">JavaScript</span>
                        <h3>Example</h3>
                        <pre class="code">document.getElementById("demo").innerHTML = "Hello JavaScript!";

document.getElementById("demo").style.fontSize = "25px";

document.getElementById("image").src = "picture.gif";</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('js-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>JavaScript Variables</h3>
                    <p>Variables are containers for storing data values.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">JavaScript</span>
                        <h3>Example</h3>
                        <pre class="code">// Using var (old way)
var x = 5;
var y = 6;
var z = x + y;

// Using let (new way, block scope)
let a = 5;
let b = 6;
let c = a + b;

// Using const (constant values)
const price1 = 5;
const price2 = 6;
const total = price1 + price2;</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('js-variables')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Python Section -->
            <div id="python" class="language-section">
                <div class="content-card">
                    <h2>Python Tutorial</h2>
                    <p>Python is a popular programming language.</p>
                    <p>Python can be used on a server to create web applications.</p>
                </div>
                
                <div class="content-card">
                    <h3>Python Syntax</h3>
                    <p>Python syntax can be executed by writing directly in the Command Line.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">Python</span>
                        <h3>Example</h3>
                        <pre class="code">print("Hello, World!")

# This is a comment
"""
This is a
multiline comment
"""

x = 5
y = "Hello, World!"</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('python-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>Python Variables</h3>
                    <p>Variables are containers for storing data values.</p>
                    <p>Python has no command for declaring a variable. A variable is created the moment you first assign a value to it.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">Python</span>
                        <h3>Example</h3>
                        <pre class="code">x = 5          # x is of type int
y = "John"     # y is of type str
print(x)
print(y)

# You can change the type of a variable
x = 4       # x is of type int
x = "Sally" # x is now of type str
print(x)</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('python-variables')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Java Section -->
            <div id="java" class="language-section">
                <div class="content-card">
                    <h2>Java Tutorial</h2>
                    <p>Java is a popular programming language.</p>
                    <p>Java is used to develop mobile apps, web apps, desktop apps, games and much more.</p>
                </div>
                
                <div class="content-card">
                    <h3>Java Syntax</h3>
                    <p>Every line of code that runs in Java must be inside a class.</p>
                    <p>In our example, we named the class MyClass. A class should always start with an uppercase first letter.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">Java</span>
                        <h3>Example</h3>
                        <pre class="code">public class MyClass {
  public static void main(String[] args) {
    System.out.println("Hello World");
  }
}</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('java-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>Java Variables</h3>
                    <p>Variables are containers for storing data values.</p>
                    <p>In Java, there are different types of variables, for example:</p>
                    
                    <div class="code-example">
                        <span class="language-badge">Java</span>
                        <h3>Example</h3>
                        <pre class="code">String name = "John";              // String
int myNum = 15;                    // Integer
float myFloatNum = 5.99f;          // Floating point number
char myLetter = 'D';               // Character
boolean myBool = true;             // Boolean</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('java-variables')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- C# Section -->
            <div id="csharp" class="language-section">
                <div class="content-card">
                    <h2>C# Tutorial</h2>
                    <p>C# (C-Sharp) is a programming language developed by Microsoft.</p>
                    <p>C# is used to develop web apps, desktop apps, mobile apps, games and much more.</p>
                </div>
                
                <div class="content-card">
                    <h3>C# Syntax</h3>
                    <p>In C#, every line of code must be inside a class.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">C#</span>
                        <h3>Example</h3>
                        <pre class="code">using System;

namespace HelloWorld
{
  class Program
  {
    static void Main(string[] args)
    {
      Console.WriteLine("Hello World!");
    }
  }
}</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('csharp-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>C# Variables</h3>
                    <p>Variables are containers for storing data values.</p>
                    <p>In C#, there are different types of variables, for example:</p>
                    
                    <div class="code-example">
                        <span class="language-badge">C#</span>
                        <h3>Example</h3>
                        <pre class="code">int myNum = 5;               // Integer (whole number)
double myDoubleNum = 5.99D;  // Floating point number
char myLetter = 'D';         // Character
bool myBool = true;          // Boolean
string myText = "Hello";     // String</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('csharp-variables')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PHP Section -->
            <div id="php" class="language-section">
                <div class="content-card">
                    <h2>PHP Tutorial</h2>
                    <p>PHP is a server scripting language, and a powerful tool for making dynamic and interactive Web pages.</p>
                    <p>PHP is a widely-used, free, and efficient alternative to competitors such as Microsoft's ASP.</p>
                </div>
                
                <div class="content-card">
                    <h3>PHP Syntax</h3>
                    <p>A PHP script starts with &lt;?php and ends with ?&gt;</p>
                    
                    <div class="code-example">
                    <span class="language-badge">PHP</span>
                        <h3>Example</h3>
                        <pre class="code">&lt;?php
echo "Hello World!";
?&gt;

// PHP variables
$txt = "Hello World!";
$x = 5;
$y = 10.5;</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('php-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>PHP Echo and Print</h3>
                    <p>With PHP, there are two basic ways to get output: echo and print.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">PHP</span>
                        <h3>Example</h3>
                        <pre class="code">&lt;?php
echo "PHP is Fun!";
echo "Hello world!";
echo "I'm about to learn PHP!";

print "PHP is Fun!";
print "Hello world!";
?&gt;</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('php-output')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SQL Section -->
            <div id="sql" class="language-section">
                <div class="content-card">
                    <h2>SQL Tutorial</h2>
                    <p>SQL is a standard language for storing, manipulating and retrieving data in databases.</p>
                    <p>Our SQL tutorial will teach you how to use SQL in MySQL, SQL Server, MS Access, Oracle, Sybase, Informix, Postgres, and other database systems.</p>
                </div>
                
                <div class="content-card">
                    <h3>SQL Syntax</h3>
                    <p>SQL follows a simple syntax, which resembles the English language.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">SQL</span>
                        <h3>Example</h3>
                        <pre class="code">SELECT * FROM Customers;

SELECT CustomerName, City FROM Customers;

SELECT * FROM Customers
WHERE Country='Germany';</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('sql-editor')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3>SQL Database Tables</h3>
                    <p>A database most often contains one or more tables. Each table is identified by a name and contains records (rows) with data.</p>
                    
                    <div class="code-example">
                        <span class="language-badge">SQL</span>
                        <h3>Example</h3>
                        <pre class="code">CREATE TABLE Customers (
    CustomerID int,
    CustomerName varchar(255),
    ContactName varchar(255),
    Address varchar(255),
    City varchar(255),
    PostalCode varchar(255),
    Country varchar(255)
);

INSERT INTO Customers (CustomerID, CustomerName, ContactName)
VALUES (1, 'Alfreds Futterkiste', 'Maria Anders');</pre>
                        <div class="code-actions">
                            <a href="#" class="btn" onclick="redirectToDashboard('sql-tables')">
                                <i class="fas fa-play"></i> Try it Yourself
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Toggle sidebar on mobile
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Show different language sections
    function showLanguage(language) {
        // Hide all language sections
        const sections = document.querySelectorAll('.language-section');
        sections.forEach(section => {
            section.classList.remove('show');
        });
        
        // Remove active class from all sidebar links
        const links = document.querySelectorAll('.sidebar a');
        links.forEach(link => {
            link.classList.remove('active');
        });
        
        // Show selected language section
        document.getElementById(language).classList.add('show');
        
        // Add active class to selected sidebar link
        event.currentTarget.classList.add('active');
    }
    
    // Redirect to dashboard with specific section
    function redirectToDashboard(section) {
        // In a real application, this would redirect to the dashboard
        // For this example, we'll just show an alert
        alert('In a real application, this would take you to the ' + section + ' section of the dashboard');
    }
    
    // Dark mode toggle
    let darkMode = false;
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    darkModeToggle.addEventListener('click', function() {
        darkMode = !darkMode;
        
        if (darkMode) {
            document.documentElement.style.setProperty('--primary-color', '#7f5af0');
            document.documentElement.style.setProperty('--primary-hover', '#6f4de0');
            document.documentElement.style.setProperty('--dark-color', '#fffffe');
            document.documentElement.style.setProperty('--light-color', '#16161a');
            document.documentElement.style.setProperty('--gray-color', '#242629');
            document.documentElement.style.setProperty('--text-color', '#fffffe');
            document.documentElement.style.setProperty('--text-light', '#94a1b2');
            document.documentElement.style.setProperty('--accent-color', '#ff8906');
            
            this.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            document.documentElement.style.setProperty('--primary-color', '#4361ee');
            document.documentElement.style.setProperty('--primary-hover', '#3a56d4');
            document.documentElement.style.setProperty('--dark-color', '#2b2d42');
            document.documentElement.style.setProperty('--light-color', '#f8f9fa');
            document.documentElement.style.setProperty('--gray-color', '#e9ecef');
            document.documentElement.style.setProperty('--text-color', '#2b2d42');
            document.documentElement.style.setProperty('--text-light', '#6c757d');
            document.documentElement.style.setProperty('--accent-color', '#ff9f1c');
            
            this.innerHTML = '<i class="fas fa-moon"></i>';
        }
    });
</script>

</body>
</html>