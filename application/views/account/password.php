<div class="row justify-content-center">
  <div class="col-12 col-lg-7 col-xl-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-1">Ubah Kata Sandi</h5>
        <p class="text-muted mb-0">Gunakan minimal 12 karakter dan jangan gunakan ulang kata sandi lama.</p>
      </div>
      <div class="card-body">
        <?php if (validation_errors()): ?>
          <div class="alert alert-danger"><?= validation_errors() ?></div>
        <?php endif; ?>
        <form action="<?= site_url('account/password') ?>" method="post">
          <?php foreach (array(
            'current_password' => 'Kata sandi saat ini',
            'new_password' => 'Kata sandi baru',
            'confirm_password' => 'Ulangi kata sandi baru',
          ) as $field => $label): ?>
            <div class="mb-3">
              <label class="form-label" for="<?= $field ?>"><?= html_escape($label) ?></label>
              <div class="input-group">
                <input type="password" class="form-control" id="<?= $field ?>" name="<?= $field ?>"
                       autocomplete="<?= $field === 'current_password' ? 'current-password' : 'new-password' ?>" required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle="<?= $field ?>"
                        aria-label="Tampilkan kata sandi" aria-pressed="false"><i class="fa-solid fa-eye"></i></button>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-end gap-2">
            <a class="btn btn-label-secondary" href="<?= site_url('dashboard') ?>">Batal</a>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-key me-1"></i>Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
