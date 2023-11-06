import AbstractView from "./AbstractView.js";

export default class extends AbstractView {
    constructor(params) {
        super(params);
        this.setTitle("Login");
    }

    async getHtml() {
        return `
            <h1>Login</h1>
            <p>Enter your username and password:</p>
            <form id="loginForm">
                <label htmlFor="username">Username:</label><br>
                <input type="text" id="username" name="username"><br><br>
                    <label htmlFor="password">Password:</label><br>
                    <input type="password" id="password" name="password"><br><br>
                        <input type="button" value="Submit" onClick="submitForm()">
            </form>
    
            <script>
                function submitForm() {
                var username = document.getElementById('username').value;
                var password = document.getElementById('password').value;
    
                // Perform actions with username and password here
                console.log("Username: ", username);
                console.log("Password: ", password);
            }
            </script>
            
            <br><hr>
            
            <p>Alternatively, enter a new username and password to register:</p>
            <form id="registrationForm">
                <label htmlFor="username">Username:</label><br>
                <input type="text" id="username" name="username"><br><br>
                    <label htmlFor="password">Password:</label><br>
                    <input type="password" id="password" name="password"><br><br>
                        <input type="button" value="Register" onClick="submitRegistration()">
            </form>
            
            <script>
                function submitRegistration() {
                var username = document.getElementById('username').value;
                var password = document.getElementById('password').value;
    
                // Perform actions with username and password here
                console.log("Username: ", username);
                console.log("Password: ", password);
            }
            </script>
        `;
    }
}