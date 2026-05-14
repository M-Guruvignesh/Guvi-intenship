$(function () {
  function showAlert(type, message) {
    const alert = $("#registerAlert");
    alert.removeClass("d-none alert-success alert-danger")
      .addClass(type === "success" ? "alert-success" : "alert-danger")
      .text(message);
  }

  function validateInput(data) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!data.name || data.name.length > 100) {
      return "Name is required and must be up to 100 characters.";
    }

    if (!emailPattern.test(data.email)) {
      return "Please provide a valid email address.";
    }

    if (!data.password || data.password.length < 8) {
      return "Password must be at least 8 characters.";
    }

    if (data.password !== data.confirmPassword) {
      return "Passwords do not match.";
    }

    return "";
  }

  $("#registerBtn").on("click", function () {
    const payload = {
      name: $("#name").val().trim(),
      email: $("#email").val().trim(),
      password: $("#password").val(),
      confirmPassword: $("#confirmPassword").val()
    };

    const validationMessage = validateInput(payload);
    if (validationMessage) {
      showAlert("error", validationMessage);
      return;
    }

    $.ajax({
      url: "php/register.php",
      method: "POST",
      contentType: "application/json",
      dataType: "json",
      data: JSON.stringify(payload),
      success: function (res) {
        if (res.success) {
          showAlert("success", res.message || "Registration successful.");
          setTimeout(function () {
            window.location.href = "login.html";
          }, 1000);
          return;
        }
        showAlert("error", res.error || "Registration failed.");
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        showAlert("error", response.error || "Server error while registering.");
      }
    });
  });
});
