document.getElementById('loginForm').addEventListener('submit', function(event) {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('errorMessage');
    
    if (username === "" || password === "") {
        errorMessage.innerText = "Please fill in all fields.";
        event.preventDefault(); // Prevent form from submitting
    } else {
        errorMessage.innerText = "";
    }
});
