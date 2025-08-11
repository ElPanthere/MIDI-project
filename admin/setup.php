<?php
/*
  Panthère Informatique — Admin de mise à jour
  - URL: /admin/setup.php
  - Protection par mot de passe simple (à changer ci-dessous)
  - Remplit/remplace toutes les infos variables dans les pages
  - S'auto-supprime après application
*/

$password = 'panthere'; // <<< À CHANGER AVANT MISE EN LIGNE

session_start();
if (!isset($_SESSION['ok'])) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
    if (hash('sha256', $_POST['pass']) === hash('sha256', $password)) {
      $_SESSION['ok'] = true;
      header('Location: setup.php'); exit;
    } else {
      $err = "Mot de passe incorrect.";
    }
  }
  ?>
  <!doctype html>
  <html lang="fr"><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin — Accès</title>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#0b0b0c;color:#e5e7eb;display:grid;place-items:center;height:100vh;margin:0}
    form{background:#111216;border:1px solid #2a3145;border-radius:14px;padding:22px;min-width:280px;box-shadow:0 10px 30px rgba(0,0,0,.35)}
    input{width:100%;padding:12px 14px;background:#101218;color:#e5e7eb;border:1px solid #2a3145;border-radius:10px}
    button{margin-top:10px;width:100%;padding:12px 14px;border-radius:12px;border:0;background:#facc15;color:#0a0a0a;font-weight:700;cursor:pointer}
    .err{color:#f7adad;background:#3d0f16;border:1px solid #5a1a23;padding:8px 10px;border-radius:10px;margin-bottom:10px}
  </style></head><body>
    <form method="post">
      <h3>Admin — Accès</h3>
      <?php if (!empty($err)) echo "<div class='err'>$err</div>"; ?>
      <input type="password" name="pass" placeholder="Mot de passe" autofocus/>
      <button type="submit">Entrer</button>
    </form>
  </body></html>
  <?php
  exit;
}

function val($k){ return trim($_POST[$k] ?? ''); }
function sanitize($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
  $root = realpath(__DIR__ . '/..');
  $fields = [
    'company','legal','siren','siret','tva','email','phone','domain','workshop','registered','hours','hosting','director','lat','lng','maps_embed','recaptcha_site','recaptcha_secret'
  ];
  $data = [];
  foreach ($fields as $f) { $data[$f] = val($f); }

  // Normalize phone for tel:
  $tel_href = preg_replace('/\s+/', '', $data['phone']);

  // Recursively scan files
  $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
  $changed = 0;
  foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['html','php','txt','xml','json','js','css'])) continue;

    $content = file_get_contents($path);
    $orig = $content;

    // Emails
    if ($data['email']) {
      $content = preg_replace('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $data['email'], $content);
      $content = preg_replace("/\\\$SMTP_USER\\s*=\\s*'[^']*';/i", "\$SMTP_USER = '{$data['email']}';", $content);
      $content = preg_replace("/\\\$TO_EMAIL\\s*=\\s*'[^']*';/i", "\$TO_EMAIL = '{$data['email']}';", $content);
    }

    // Téléphone
    if ($data['phone']) {
      $content = preg_replace('/\+?\d[\d\s]{6,}\d/', $data['phone'], $content);
      $content = preg_replace('/href="tel:[^"]+"/', 'href="tel:'.$tel_href.'"', $content);
    }

    // Domain + robots + sitemap
    if ($data['domain']) {
      $content = preg_replace('~https?://[a-z0-9\.-]+~i', $data['domain'], $content);
      $content = preg_replace('/("url":\s*")https?:\/\/[^"]+(")/', '$1'.$data['domain'].'$2', $content);
      if (preg_match('/robots\.txt$/', $path)) {
        $content = preg_replace('/Sitemap:\s*https?:\/\/[^\s]+/i', 'Sitemap: '.$data['domain'].'/sitemap.xml', $content);
      }
      if (preg_match('/sitemap\.xml$/', $path)) {
        $content = preg_replace('~<loc>https?://[^<]+</loc>~i', function($m) use($data){ 
          return preg_replace('~https?://[^/]+~i', $data['domain'], $m[0]);
        }, $content);
      }
    }

    // Adresses
    if ($data['workshop']) {
      $content = preg_replace('/"streetAddress":\s*".*?"/', '"streetAddress": "'.sanitize($data['workshop']).'"', $content);
      $content = str_replace('Adresse à compléter', sanitize($data['workshop']), $content);
    }
    if ($data['registered']) {
      $content = preg_replace('/Siège social.*?:<\/strong>\s*.*?<\/p>/', '<strong>Siège social (RNE) :</strong> '.sanitize($data['registered']).'</p>', $content);
    }

    // Horaires
    if ($data['hours']) {
      // Visible blocks
      $content = preg_replace('/<h2>Horaires<\/h2>.*?<div class="card">.*?<\/div>/s', '<h2>Horaires</h2><div class="card"><p><strong>'.sanitize($data['hours']).'</strong></p></div>', $content);
      // JSON-LD OpeningHoursSpecification (basic 7/7)
      $content = preg_replace('/"openingHoursSpecification":\s*\{.*?\}/s', '"openingHoursSpecification":{"@type":"OpeningHoursSpecification","dayOfWeek":["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],"opens":"08:00","closes":"20:00"}', $content);
    }

    // LocalBusiness JSON-LD common fields
    if ($data['company']) { $content = preg_replace('/"name":\s*".*?"/', '"name": "'.sanitize($data['company']).'"', $content); }
    if ($data['email'])   { $content = preg_replace('/"email":\s*".*?"/', '"email": "'.sanitize($data['email']).'"', $content); }
    if ($data['phone'])   { $content = preg_replace('/"telephone":\s*".*?"/', '"telephone": "'.sanitize($data['phone']).'"', $content); }
    if ($data['domain'])  { $content = preg_replace('/"url":\s*".*?"/', '"url": "'.sanitize($data['domain']).'"', $content); }

    // Geo
    if ($data['lat'] && $data['lng']) {
      $content = preg_replace('/"geo":\s*\{.*?\}/s', '"geo":{"@type":"GeoCoordinates","latitude":'.floatval($data['lat']).',"longitude":'.floatval($data['lng']).'}', $content);
    }

    // Mentions légales
    if ($data['legal'] || $data['siren'] || $data['siret'] || $data['tva'] || $data['hosting'] || $data['director']) {
      if (preg_match('/legal\.html$/', $path)) {
        if ($data['legal'])   $content = preg_replace('/Éditeur.*?<\/p>/', 'Éditeur :</strong> '.sanitize($data['legal']).' — Nom commercial : '.sanitize($data['company'] ?: 'Panthère Informatique').'</p>', $content);
        if ($data['siren'])   $content = preg_replace('/SIREN.*?—/', 'SIREN :</strong> '.sanitize($data['siren']).' —', $content);
        if ($data['siret'])   $content = preg_replace('/SIRET.*?—/', 'SIRET :</strong> '.sanitize($data['siret']).' —', $content);
        if ($data['tva'])     $content = preg_replace('/TVA intracom.*?<\/p>/', 'TVA intracom :</strong> '.sanitize($data['tva']).'</p>', $content);
        if ($data['hosting']) $content = preg_replace('/<strong>Hébergement :<\/strong>.*?<\/p>/', '<strong>Hébergement :</strong> '.sanitize($data['hosting']).'</p>', $content);
        if ($data['director'])$content = preg_replace('/<strong>Directeur de publication :<\/strong>.*?<\/p>/', '<strong>Directeur de publication :</strong> '.sanitize($data['director']).'</p>', $content);
      }
      // Footer legal line
      $footer_regex = '/SIREN\/SIRET\s*:\s*[^<]+·\s*TVA\s*:\s*[^<]+/';
      $footer_value = 'SIREN/SIRET : '.sanitize($data['siren']).' / '.sanitize($data['siret']).' · TVA : '.sanitize($data['tva']);
      if ($data['siren'] && $data['siret'] && $data['tva']) {
        $content = preg_replace($footer_regex, $footer_value, $content);
      }
    }

    // Maps embed replacement if provided
    if ($data['maps_embed']) {
      $content = preg_replace('~<iframe[^>]+google\.com/maps[^>]*>.*?</iframe>~s', $data['maps_embed'], $content);
    }

    // contact.php SMTP recipient (keep password empty)
    if (preg_match('/contact\.php$/', $path)) {
      if ($data['email']) {
        $content = preg_replace("/\\\$TO_EMAIL\\s*=\\s*'[^']*';/", "\$TO_EMAIL = '{$data['email']}';", $content);
        $content = preg_replace("/\\\$SMTP_USER\\s*=\\s*'[^']*';/", "\$SMTP_USER = '{$data['email']}';", $content);
      }
    }

    if ($content !== $orig) {
      file_put_contents($path, $content);
      $changed++;
    }
  }

  // Auto-delete this setup page after successful apply
  $self = __FILE__;
  @unlink($self);
  // Try to remove admin directory if empty
  @rmdir(__DIR__);

  echo "<!doctype html><meta charset='utf-8'><style>body{font-family:system-ui;background:#0b0b0c;color:#e5e7eb;display:grid;place-items:center;height:100vh}</style>";
  echo "<h2>✅ Mise à jour appliquée ($changed fichiers modifiés). La page d’admin s’est auto-supprimée.</h2>";
  echo "<p><a href='../index.html' style='color:#facc15'>Retour au site</a></p>";
  exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin — Mise à jour des informations</title>
  <style>
    :root{--bg:#0b0b0c;--fg:#e5e7eb;--muted:#9ca3af;--border:#2a3145;--card:#111216;--primary:#facc15}
    *{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif}
    .wrap{max-width:980px;margin:40px auto;padding:0 16px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:16px}
    label{display:block;margin:10px 0 6px} input,textarea{width:100%;padding:12px 14px;background:#101218;color:var(--fg);border:1px solid var(--border);border-radius:10px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px} .grid .full{grid-column:1/-1}
    .btn{display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#0a0a0a;border:0;padding:12px 16px;border-radius:12px;font-weight:700;cursor:pointer}
    small{color:var(--muted)}
  </style>
</head>
<body>
  <div class="wrap">
    <h2>Admin — Mise à jour des informations</h2>
    <form method="post" class="card">
      <div class="grid">
        <div><label>Nom commercial</label><input name="company" placeholder="Ex: Panthère Informatique"/></div>
        <div><label>Raison sociale (éditeur)</label><input name="legal" placeholder="Ex: DORNON JOFFREY (PANTHER PRODS.)"/></div>
        <div><label>SIREN</label><input name="siren" placeholder="Ex: 981 498 090"/></div>
        <div><label>SIRET</label><input name="siret" placeholder="Ex: 981 498 090 00019"/></div>
        <div><label>TVA intracom</label><input name="tva" placeholder="Ex: FR15981498090"/></div>
        <div><label>Email</label><input name="email" type="email" placeholder="Ex: contact@domaine.fr"/></div>
        <div><label>Téléphone</label><input name="phone" placeholder="Ex: +33 7 56 95 73 55"/></div>
        <div><label>Domaine (URL)</label><input name="domain" placeholder="Ex: https://panthere-informatique.fr"/></div>
        <div class="full"><label>Adresse établissement (atelier)</label><input name="workshop" placeholder="Ex: 4 A Rue Gustave Eiffel, 33380 Mios"/></div>
        <div class="full"><label>Siège social (RNE)</label><input name="registered" placeholder="Ex: 27 Allée des Acacias, 40410 Saugnac-et-Muret"/></div>
        <div><label>Latitude</label><input name="lat" placeholder="Ex: 44.6057"/></div>
        <div><label>Longitude</label><input name="lng" placeholder="Ex: -0.9319"/></div>
        <div class="full"><label>Horaires (visible)</label><input name="hours" placeholder="Ex: Tous les jours — 08:00 → 20:00"/></div>
        <div><label>Hébergeur</label><input name="hosting" placeholder="Ex: Hostmyservers"/></div>
        <div><label>Directeur de publication</label><input name="director" placeholder="Ex: Joffrey Dornon"/></div>
        <div class="full"><label>Iframe Google Maps (optionnel)</label><textarea name="maps_embed" rows="4" placeholder='&lt;iframe src="https://maps.google.com/maps?q=..." ...&gt;&lt;/iframe&gt;'></textarea></div>
        <div><label>reCAPTCHA site key (optionnel)</label><input name="recaptcha_site" placeholder=""/></div>
        <div><label>reCAPTCHA secret key (optionnel)</label><input name="recaptcha_secret" placeholder=""/></div>
      </div>
      <p><small>Astuce : laisse un champ vide pour ne pas le modifier.</small></p>
      <button class="btn" type="submit" name="apply" value="1">Appliquer et supprimer cette page</button>
    </form>
  </div>
</body>
</html>
