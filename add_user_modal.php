<!-- Include SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
if (isset($_SESSION['message2'])) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '" . $_SESSION['message2'] . "'
                });
            });
          </script>";
    unset($_SESSION['message2']); 
}
?>

<style>
    /* Modal styling */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 200;
    }

    .modal-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 450px;
        box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.2);
    }

    .modal-header h3 {
        margin-bottom: 20px;
    }

    .modal-content input,
    .modal-content select {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .modal-content input:invalid,
    .modal-content select:invalid {
        border-color: red;
    }

    .modal-content button {
        padding: 10px 20px;
        background-color: #3498DB;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .modal-content button:hover {
        background-color: #2980B9;
    }

    .modal-content .cancel-btn {
        background-color: #E74C3C;
    }

    .modal-content .cancel-btn:hover {
        background-color: #C0392B;
    }

    .error-message {
        color: red;
        font-size: 12px;
        margin-top: -10px;
        margin-bottom: 10px;
    }
</style>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New User</h3>
        </div>

        <form action="add_user.php" method="POST" id="addUserForm">
            <input type="text" name="name" id="name" placeholder="Full Name" required>
            <div class="error-message" id="nameError"></div>

            <input type="text" name="username" id="username" placeholder="Username" required>
            <div class="error-message" id="usernameError"></div>

            <input type="email" name="email" id="email" placeholder="Email" required>
            <div class="error-message" id="emailError"></div>

            <select name="role" id="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="driver">Driver</option>
                <option value="mechanic">Mechanic</option>
            </select>
            <div class="error-message" id="roleError"></div>

            <input type="text" name="phone" id="phone" placeholder="Phone Number" required>
            <div class="error-message" id="phoneError"></div>

            <input type="date" name="dob" id="dob" placeholder="Date of Birth" required>
            <div class="error-message" id="dobError"></div>

            <button type="submit" id="submitBtn">Add User</button>
            <button type="button" class="cancel-btn" id="closeModalBtn">Cancel</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Show modal when "Add User" button is clicked
    document.getElementById('addUserBtn').addEventListener('click', function () {
        document.getElementById('addUserModal').style.display = 'flex';
    });

    // Close modal when "Cancel" button is clicked
    document.getElementById('closeModalBtn').addEventListener('click', function () {
        document.getElementById('addUserModal').style.display = 'none';
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function (e) {
        if (e.target === document.getElementById('addUserModal')) {
            document.getElementById('addUserModal').style.display = 'none';
        }
    });

    // Real-time validation functions
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const dobInput = document.getElementById('dob');
    const roleSelect = document.getElementById('role');
    const usernameInput = document.getElementById('username');

    // Validate name
    nameInput.addEventListener('input', function () {
        const nameError = document.getElementById('nameError');
        if (nameInput.value.trim() === '') {
            nameError.textContent = 'Name is required.';
            nameError.style.display = 'block';
        } else if (!/^[a-zA-Z\s]+$/.test(nameInput.value)) {
            nameError.textContent = 'Name can only contain letters and spaces.';
            nameError.style.display = 'block';
        } else if (nameInput.value.trim().length < 3) {
            nameError.textContent = 'Name must be at least 3 letters long.';
            nameError.style.display = 'block';
        } else {
            nameError.textContent = '';
            nameError.style.display = 'none';
        }
    });

    // Validate email
    emailInput.addEventListener('input', function () {
        const emailError = document.getElementById('emailError');
        const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailPattern.test(emailInput.value)) {
            emailError.textContent = 'Please enter a valid email address.';
            emailError.style.display = 'block';
        } else {
            emailError.textContent = '';
            emailError.style.display = 'none';
        }
    });

    // Real-time phone uniqueness check
    phoneInput.addEventListener('input', function () {
        const phoneError = document.getElementById('phoneError');
        const phone = phoneInput.value;
        if (!/^\d{10}$/.test(phone)) {
            phoneError.textContent = 'Phone number must be 10 digits.';
            phoneError.style.display = 'block';
            return;
        }
        fetch('get_user.php?phone_check=' + encodeURIComponent(phone))
            .then(response => response.json())
            .then(data => {
                if (data.taken) {
                    phoneError.textContent = 'This phone number is already registered.';
            phoneError.style.display = 'block';
        } else {
            phoneError.textContent = '';
            phoneError.style.display = 'none';
        }
            });
    });

    // Validate date of birth
    dobInput.addEventListener('change', function () {
        const dobError = document.getElementById('dobError');
        const today = new Date();
        const dob = new Date(dobInput.value);
        const age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();

        if (dobInput.value >= today.toISOString().split('T')[0]) {
            dobError.textContent = 'Date of birth cannot be in the future.';
            dobError.style.display = 'block';
        } else if (age < 18 || (age === 18 && monthDiff < 0)) {
            dobError.textContent = 'You must be at least 18 years old.';
            dobError.style.display = 'block';
        } else {
            dobError.textContent = '';
            dobError.style.display = 'none';
        }
    });

    // Validate role selection
    roleSelect.addEventListener('change', function () {
        const roleError = document.getElementById('roleError');
        if (roleSelect.value === '') {
            roleError.textContent = 'Please select a role.';
            roleError.style.display = 'block';
        } else {
            roleError.textContent = '';
            roleError.style.display = 'none';
        }
    });

    // Validate username
    usernameInput.addEventListener('input', function () {
        const usernameError = document.getElementById('usernameError');
        if (usernameInput.value.trim() === '') {
            usernameError.textContent = 'Username is required.';
            usernameError.style.display = 'block';
        } else if (!/^[a-zA-Z0-9_]{3,20}$/.test(usernameInput.value)) {
            usernameError.textContent = 'Username must be 3-20 characters, letters, numbers, or underscores.';
            usernameError.style.display = 'block';
        } else {
            usernameError.textContent = '';
            usernameError.style.display = 'none';
        }
    });

    // Form submission validation
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        let isValid = true;
        
        // Trigger validation for all fields
        nameInput.dispatchEvent(new Event('input'));
        emailInput.dispatchEvent(new Event('input'));
        phoneInput.dispatchEvent(new Event('input'));
        dobInput.dispatchEvent(new Event('change'));
        roleSelect.dispatchEvent(new Event('change'));
        usernameInput.dispatchEvent(new Event('input'));

        // Check if any error messages are displayed
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(error => {
            if (error.textContent !== '') {
                isValid = false;
            }
        });

        const phoneError = document.getElementById('phoneError');
        if (phoneError.textContent !== '') {
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            alert('Please correct the errors in the form before submitting.');
        }
    });
});
</script>
