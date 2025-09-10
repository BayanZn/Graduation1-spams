</div>


  <!-- General JS Scripts -->
<script src="../assets/js/app.min.js"></script>
<!-- JS Libraies -->
<script src="../assets/bundles/apexcharts/apexcharts.min.js"></script>
<script src="../assets/bundles/chocolat/dist/js/jquery.chocolat.min.js"></script>
<script src="../assets/bundles/datatables/datatables.min.js"></script>
<script src="../assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/bundles/jquery-ui/jquery-ui.min.js"></script>
<script src="../assets/bundles/sweetalert/sweetalert.min.js"></script>
<script src="../assets/bundles/summernote/summernote-bs4.js"></script>

<!-- Page Specific JS File -->
<script src="../assets/js/page/index.js"></script>
<script src="../assets/js/page/gallery1.js"></script>
<script src="../assets/js/page/datatables.js"></script>
<script src="../assets/js/page/sweetalert.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Template JS File -->
<script src="../assets/js/scripts.js"></script>
<!-- Custom JS File -->
<script src="../assets/js/custom.js"></script>

<script>
function confirmLogout() {
  Swal.fire({
    title: 'Are you sure?',
    text: "You will be logged out of the system",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, logout!'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = '../logout.php';
    }
  });
  return false;
}

// Activate menu based on current URL
$(document).ready(function() {
    const path = window.location.pathname.split('/').pop();
    
    $('.sidebar-menu li a').each(function() {
        const href = $(this).attr('href').split('/').pop();
        
        if (path === href) {
            $(this).parent().addClass('active');
            $(this).closest('.has-treeview').addClass('menu-open');
        }
    });
});

// AJAX error handler
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    if (jqxhr.status === 401) {
        window.location.href = 'login.php?session_expired=1';
    } else if (jqxhr.status === 403) {
        showError('You are not authorized to perform this action');
    } else if (jqxhr.status === 404) {
        showError('Requested resource not found');
    } else if (jqxhr.status === 500) {
        showError('Server error occurred. Please try again later.');
    } else {
        showError('An error occurred. Please try again.');
    }
});

// Delete confirmation handler
$(document).on('click', '.confirm-delete', function(e) {
    e.preventDefault();
    const url = $(this).attr('href');
    const message = $(this).data('confirm') || 'Are you sure you want to delete this item?';
    
    Swal.fire({
        title: 'Confirm',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.status === 'success') {
                        showSuccess(response.message);
                        
                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1500);
                        } else {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showError(response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showError('Failed to delete item. Please try again.');
                }
            });
        }
    });
});
</script>
</body>




</html>