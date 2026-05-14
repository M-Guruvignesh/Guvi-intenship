console.log("LOGIN JS LOADED");

$(document).ready(function () {

  $("#loginBtn").on("click", function (e) {
    e.preventDefault();

    const payload = {
      email: $("#email").val().trim(),
      password: $("#password").val().trim()
    };

    $.ajax({
      url: "php/login.php",
      method: "POST",
      contentType: "application/json",
      dataType: "json",
      data: JSON.stringify(payload),
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },

      success: function (res) {

  console.log("LOGIN RESPONSE:", res);

  if (!res.success) {
    alert(res.error || "Login failed");
    return;
  }

  const token = res.sessionToken;  // MUST MATCH PHP

  if (!token) {
    alert("No token received from server");
    return;
  }

  // 🔥 THIS IS CRITICAL LINE (YOU WERE MISSING / WRONG)
  localStorage.setItem("sessionToken", token);

  console.log("TOKEN SAVED:", localStorage.getItem("sessionToken"));

  window.location.href = "profile.html";
},

      error: function (xhr) {
        console.log(xhr.responseText);
        alert("Login failed");
      }
    });
  });

});
