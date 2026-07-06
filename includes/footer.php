    </main>
  </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<div class="toast-container" id="toastContainer"></div>
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <div class="confirm-icon" id="confirmIcon">⚠️</div>
    <div class="confirm-title" id="confirmTitle">Konfirmasi</div>
    <div class="confirm-msg" id="confirmMsg">Apakah kamu yakin?</div>
    <div class="confirm-actions">
      <button class="btn btn-outline" onclick="closeConfirm()">Batal</button>
      <button class="btn btn-danger" id="confirmBtn" onclick="doConfirm()">Ya, Lanjutkan</button>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
