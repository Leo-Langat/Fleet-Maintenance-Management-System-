<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message</title>
    <style>
        /* Reset modal styles to prevent conflicts */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
            margin: 0;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2C3E50;
            text-align: left;
            margin-bottom: 1rem;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            float: right;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2C3E50;
            font-size: 0.95rem;
        }

        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.2s;
            background-color: #fff;
            box-sizing: border-box;
        }

        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #3498DB;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .submit-button {
            background-color: #3498DB;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 0;
        }

        .submit-button:hover {
            background-color: #2980B9;
            transform: translateY(-1px);
        }

        .submit-button:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: none;
            padding: 0.5rem;
            border-radius: 4px;
            background-color: #fde8e8;
        }

        .success-message {
            color: #27ae60;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: none;
            padding: 0.5rem;
            border-radius: 4px;
            background-color: #e8f8e8;
        }

        /* Loading spinner */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .modal-content {
                width: 95%;
                padding: 1.5rem;
                margin: 0;
            }

            .modal-title {
                font-size: 1.25rem;
            }

            .form-select,
            .form-textarea {
                font-size: 0.95rem;
            }
        }

        /* Ensure modal appears above other elements */
        #messageModal {
            z-index: 9999;
        }

        /* Reset any inherited styles */
        #messageModal * {
            box-sizing: border-box;
            font-family: inherit;
        }

        /* Ensure form elements don't inherit unwanted styles */
        #messageForm {
            margin: 0;
            padding: 0;
        }

        #messageForm input,
        #messageForm select,
        #messageForm textarea {
            font-family: inherit;
            font-size: inherit;
            line-height: inherit;
        }
    </style>
</head>
<body>
    <!-- Message Modal -->
    <div class="modal-overlay" id="messageModal">
        <div class="modal-content">
            <button class="close-button" onclick="closeModal()" style="float:right; margin-bottom: 1rem;">&times;</button>
            <h2 class="modal-title" style="margin-bottom: 1rem;">Send Message</h2>
            <form id="messageForm" onsubmit="sendMessage(event)">
                <div class="form-group">
                    <label class="form-label" for="recipient">Recipient</label>
                    <select class="form-select" id="recipient" name="recipient" required>
                        <option value="">Select a recipient</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="message">Message</label>
                    <textarea class="form-textarea" id="message" name="message" required 
                              placeholder="Type your message here..."></textarea>
                </div>
                <div class="error-message" id="errorMessage"></div>
                <div class="success-message" id="successMessage"></div>
                <button type="submit" class="submit-button" id="submitButton">
                    <span>Send Message</span>
                    <div class="loading-spinner" id="loadingSpinner"></div>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Function to open the modal
        function openMessageModal() {
            document.getElementById('messageModal').style.display = 'flex';
            loadUsers();
        }

        // Function to close the modal
        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
            document.getElementById('messageForm').reset();
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('successMessage').style.display = 'none';
        }

        // Function to load users into the dropdown
        function loadUsers() {
            fetch('get_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const select = document.getElementById('recipient');
                        select.innerHTML = '<option value="">Select a recipient</option>';
                        
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = `${user.name} (${user.role})`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    showError('Failed to load users. Please try again.');
                });
        }

        // Function to send message
        function sendMessage(event) {
            event.preventDefault();
            
            const recipient = document.getElementById('recipient').value;
            const message = document.getElementById('message').value;
            const submitButton = document.getElementById('submitButton');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            if (!recipient || !message) {
                showError('Please fill in all fields');
                return;
            }

            // Show loading state
            submitButton.disabled = true;
            loadingSpinner.style.display = 'block';
            submitButton.querySelector('span').textContent = 'Sending...';
            
            const formData = new FormData();
            formData.append('receiver_id', recipient);
            formData.append('message', message);

            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccess(data.message);
                    document.getElementById('messageForm').reset();
                    setTimeout(closeModal, 2000);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showError('Failed to send message. Please try again.');
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
                submitButton.querySelector('span').textContent = 'Send Message';
            });
        }

        // Function to show error message
        function showError(message) {
            const errorElement = document.getElementById('errorMessage');
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';
        }

        // Function to show success message
        function showSuccess(message) {
            const successElement = document.getElementById('successMessage');
            successElement.textContent = message;
            successElement.style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('messageModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });

        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html> 