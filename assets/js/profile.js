$(function () {
  console.log('PROFILE JS LOADED v10');

  const token = localStorage.getItem('sessionToken');
  console.log('TOKEN FROM STORAGE:', token);

  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  function showAlert(type, message) {
    $('#profileAlert')
      .removeClass('d-none alert-success alert-danger')
      .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
      .text(message);
  }

  $(document).on('click', '#logoutBtn', function (e) {
    e.preventDefault();
    localStorage.clear();
    window.location.href = 'login.html';
  });

  function loadProfile() {
    $.ajax({
      url: 'php/profile.php?action=get&token=' + encodeURIComponent(token),
      method: 'GET',
      dataType: 'json',

      success: function (res) {
        console.log('PROFILE RESPONSE:', res);

        if (!res.success) {
          showAlert('error', res.error || 'Failed to load profile.');
          return;
        }

        const user = res.user || {};

        $('#name').val(user.name || '');
        $('#email').val(user.email || '');
        $('#age').val(user.age || '');
        $('#dob').val(user.dob || '');
        $('#contact').val(user.contact || '');
      },

      error: function (xhr) {
        console.log('PROFILE ERROR RAW:', xhr.responseText);
        showAlert('error', 'Profile load failed.');
      }
    });
  }

  $('#updateBtn').on('click', function (e) {
    e.preventDefault();

    const payload = {
      name: $('#name').val().trim(),
      age: $('#age').val() ? Number($('#age').val()) : null,
      dob: $('#dob').val() || '',
      contact: $('#contact').val().trim()
    };

    $.ajax({
      url: 'php/profile.php?action=update&token=' + encodeURIComponent(token),
      method: 'POST',
      dataType: 'json',
      contentType: 'application/json',
      data: JSON.stringify(payload),

      success: function (res) {
        console.log('UPDATE RESPONSE:', res);

        if (!res.success) {
          showAlert('error', res.error || 'Update failed.');
          return;
        }

        showAlert('success', res.message || 'Profile updated successfully.');
        loadProfile();
      },

      error: function (xhr) {
        console.log('UPDATE ERROR RAW:', xhr.responseText);

        let msg = 'Server error while updating.';
        if (xhr.responseJSON && xhr.responseJSON.error) {
          msg = xhr.responseJSON.error;
        }

        showAlert('error', msg);
      }
    });
  });

  loadProfile();
});
