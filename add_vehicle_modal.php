<!-- Include SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    margin-bottom: 5px;
}
</style>

<!-- Add Vehicle Modal -->
<div class="modal" id="addVehicleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Vehicle</h3>
        </div>

        <!-- Success/Error Message -->
        <div id="messageBox" style="color: red; margin-bottom: 15px;">
            <?php
            // Display the session message if it exists
            if (isset($_SESSION['message'])) {
                echo "<script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: '".$_SESSION['message']."',
                            showConfirmButton: true,
                        });
                      </script>";
                unset($_SESSION['message']); // Clear the message after displaying
            }
            // Display the error message for duplicate registration number or VIN
            if (isset($_SESSION['message2'])) {
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: '".$_SESSION['message2']."',
                            showConfirmButton: true,
                        });
                      </script>";
                unset($_SESSION['message2']); // Clear the message after displaying
            }
            ?>
        </div>

        <form action="add_vehicle.php" method="POST" id="addVehicleForm">
            <input type="text" name="registration_no" id="registration_no" placeholder="Registration Number" required>
            <div id="regNoError" class="error-message"></div>
            
            <input type="text" name="model" id="model" placeholder="Model" required>
            <div id="modelError" class="error-message"></div>

            <input type="text" name="make" id="make" placeholder="Make" required>
            <div id="makeError" class="error-message"></div>

            <input type="number" name="year" id="year" placeholder="Year" required>
            <div id="yearError" class="error-message"></div>

            <input type="text" name="vin" id="vin" placeholder="VIN" required>
            <div id="vinError" class="error-message"></div>

            <input type="number" name="mileage" id="mileage" placeholder="Mileage (in km)" required>
            <div id="mileageError" class="error-message"></div>

            <select name="fuel_type" id="fuel_type" required>
                <option value="diesel">Diesel</option>
                <option value="petrol">Petrol</option>
            </select>
            <div id="fuelError" class="error-message"></div>

            <button type="submit" id="submitBtn" disabled>Add Vehicle</button>
            <button type="button" class="cancel-btn" id="closeModalBtn">Cancel</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const registrationNo = document.getElementById('registration_no');
    const model = document.getElementById('model');
    const make = document.getElementById('make');
    const year = document.getElementById('year');
    const vin = document.getElementById('vin');
    const mileage = document.getElementById('mileage');
    const fuelType = document.getElementById('fuel_type');
    const submitBtn = document.getElementById('submitBtn');
    let vinTimeout;

    // Show modal when "Add Vehicle" button is clicked
    document.getElementById('addVehicleBtn').addEventListener('click', function () {
        document.getElementById('addVehicleModal').style.display = 'flex';
    });

    // Close modal when "Cancel" button is clicked
    document.getElementById('closeModalBtn').addEventListener('click', function () {
        document.getElementById('addVehicleModal').style.display = 'none';
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function (e) {
        if (e.target === document.getElementById('addVehicleModal')) {
            document.getElementById('addVehicleModal').style.display = 'none';
        }
    });

    // Real-time validation
    function validateRegistrationNo() {
        const regNoPattern = /^[A-Z]{3} [0-9]{3}[A-Z]$/;
        const regNoError = document.getElementById('regNoError');
        if (!regNoPattern.test(registrationNo.value)) {
            regNoError.textContent = "Invalid format (e.g., KDQ 111T)";
            regNoError.style.display = 'block';
            return false;
        } else {
            regNoError.textContent = "";
            regNoError.style.display = 'none';
            return true;
        }
    }

    function validateYear() {
        const currentYear = new Date().getFullYear();
        const yearError = document.getElementById('yearError');
        if (year.value > currentYear || year.value < 1980) {
            yearError.textContent = `Invalid year. Must be between 1980 and ${currentYear}`;
            yearError.style.display = 'block';
            return false;
        } else {
            yearError.textContent = "";
            yearError.style.display = 'none';
            return true;
        }
    }

    function validateVIN() {
        const vinPattern = /^[A-HJ-NPR-Z0-9]{17}$/;
        const vinError = document.getElementById('vinError');
        if (!vinPattern.test(vin.value)) {
            vinError.textContent = "VIN must be 17 characters long and valid.";
            vinError.style.display = 'block';
            return false;
        } else {
            vinError.textContent = "";
            vinError.style.display = 'none';
            return true;
        }
    }

    function validateMileage() {
        const mileageError = document.getElementById('mileageError');
        if (mileage.value < 0) {
            mileageError.textContent = "Mileage cannot be negative.";
            mileageError.style.display = 'block';
            return false;
        } else {
            mileageError.textContent = "";
            mileageError.style.display = 'none';
            return true;
        }
    }

    // Add event listeners to validate as the user types
    registrationNo.addEventListener('input', validateRegistrationNo);
    year.addEventListener('input', validateYear);
    vin.addEventListener('input', function() {
        clearTimeout(vinTimeout);
        const vinValue = this.value.trim();
        
        if (vinValue.length === 0) {
            document.getElementById('vinError').style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        if (!validateVIN()) {
            submitBtn.disabled = true;
            return;
        }

        vinTimeout = setTimeout(() => {
            fetch('check_vin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'vin=' + encodeURIComponent(vinValue)
            })
            .then(response => response.json())
            .then(data => {
                const vinError = document.getElementById('vinError');
                if (data.exists) {
                    vinError.textContent = 'This VIN number already exists';
                    vinError.style.display = 'block';
                    submitBtn.disabled = true;
                } else {
                    vinError.style.display = 'none';
                    // Enable submit button only if all validations pass
                    submitBtn.disabled = !(validateRegistrationNo() && validateYear() && validateMileage());
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('vinError').textContent = 'Error checking VIN availability';
                document.getElementById('vinError').style.display = 'block';
                submitBtn.disabled = true;
            });
        }, 500); // Debounce for 500ms
    });
    mileage.addEventListener('input', validateMileage);

    // Form submission validation
    document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
        let isValid = true;
        
        // Trigger validation for all fields
        isValid = validateRegistrationNo() && validateYear() && validateVIN() && validateMileage();

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please correct the errors in the form before submitting.'
            });
            return;
        }

        // Update message box
        document.getElementById('messageBox').innerText = "Processing your request...";
    });
});
</script>
