<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion — LabManager</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/auth/login.css">
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon"><i class="bi bi-pc-display-horizontal"></i></div>
      <h1>LabManager</h1>
      <p>Identification requise</p>
    </div>

    <form action="verify.php" method="POST">
      <div style="margin-bottom:18px;">
        <label class="form-label-custom">Adresse Email</label>
        <input type="email" name="email" class="form-control-custom" placeholder="votre@email.com" required>
      </div>
      <div style="margin-bottom:22px;">
        <label class="form-label-custom">Mot de passe</label>
        <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:11px;">
        <i class="bi bi-box-arrow-in-right"></i> Se connecter
      </button>
    </form>

    <div class="divider"></div>
    <p class="text-center text-muted-sm">
      Première connexion ?
      <a href="../chef/vacataires.php" style="color:var(--primary-mid);font-weight:500;">Demander un compte</a>
    </p>
  </div>
</div>
</body>
</html>