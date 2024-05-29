<!DOCTYPE html>
<html>
<head>
  <title>Form Processing</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }

    h1 {
      text-align: center;
    }

    form {
      max-width: 400px;
      margin: 0 auto;
    }

    label {
      display: block;
      margin-bottom: 10px;
    }

    input[type="text"],
    textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
      margin-bottom: 10px;
      resize: vertical;
    }

    input[type="submit"] {
      background-color: #4CAF50;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    input[type="submit"]:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>
  <h1>Form Processing</h1>

  <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label for="message">Message:</label>
    <textarea id="message" name="message" rows="4" required></textarea>

    <input type="submit" value="Send">
  </form>

  <?php
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = $_POST["message"];

    // Process the message or perform any necessary operations
    // Here, you can add your PHP code to process the submitted message

    // For example, let's display the submitted message
    echo "<h2>Submitted Message:</h2>";
    echo "<p>" . htmlspecialchars($message) . "</p>";
  }
  ?>

<script type="module">
  // Import the functions you need from the SDKs you need
  import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.1/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/9.22.1/firebase-analytics.js";
  // TODO: Add SDKs for Firebase products that you want to use
  // https://firebase.google.com/docs/web/setup#available-libraries

  // Your web app's Firebase configuration
  // For Firebase JS SDK v7.20.0 and later, measurementId is optional
  const firebaseConfig = {
    apiKey: "AIzaSyCFL9ZvNabCMJSySuoU5PGHDCO6nU7N2xE",
    authDomain: "snapshot-snapshot.firebaseapp.com",
    projectId: "snapshot-snapshot",
    storageBucket: "snapshot-snapshot.appspot.com",
    messagingSenderId: "217999047428",
    appId: "1:217999047428:web:6ed55effb2ca2cb93aee51",
    measurementId: "G-DE6FMSV0W5"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const analytics = getAnalytics(app);
</script>
</body>
</html>