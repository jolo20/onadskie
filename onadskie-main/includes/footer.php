    </main>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modal fade text-center col-sm-4" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 d-flex flex-column">
          <img class="d-flex justify-content-center" src="../../assets/img/out.gif" alt="">
        
        </div>

        <div class="modal-body">
          <p class="mb-0 fw-500 mt-5 text-secondary">Are you sure you want to log out?</p>
        </div><hr>

        <div class="modal-footer border-0 text-center d-flex justify-content-center">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmLogoutBtn" class="btn btn-danger btn-sm">Logout</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Footer -->
  <footer class="footer text-center fixed bottom-0 inset-x-0" >© <span id="year"></span>&nbsp;Quezon City Local Government Unit 2 — All rights reserved</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/lgu-2-main-main/assets/js/script.js"></script>
  <script>
    document.getElementById('confirmLogoutBtn').addEventListener('click', function() {
      window.location.href = '/lgu-2-main-main/logout.php';
    });
  </script>
</body>
</html>
