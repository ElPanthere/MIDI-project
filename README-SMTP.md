# Panthère Informatique — SMTP (PHPMailer) Setup

Ce site envoie maintenant les formulaires via **SMTP Gmail** avec **PHPMailer**.

## Étapes rapides

1. **Se connecter en SSH** (ou localement) dans le dossier du site puis installer PHPMailer :
   ```bash
   composer install
   ```

2. **Ouvrir `contact.php`** et renseigner :
   - `$SMTP_PASSWORD` : **mot de passe d'application Gmail** (obligatoire)
     - Google > Sécurité > Validation en 2 étapes > **Mots de passe d'application**
   - (Optionnel) `$TO_EMAIL` si vous souhaitez recevoir ailleurs que sur `montagespanthere@gmail.com`

3. **Tester** le formulaire sur `contact.html`.

## Paramètres SMTP par défaut (Gmail)
- Hôte : `smtp.gmail.com`
- Port : `587` (TLS)
- Sécurité : `STARTTLS`
- Utilisateur : `montagespanthere@gmail.com`
- Mot de passe : *(à remplir : mot de passe d'application)*

## Dépannage
- Assurez-vous que l'extension PHP **openssl** est active.
- Si votre hébergeur bloque le port 587, essayez 465 (SSL) et mettez :
  ```php
  $SMTP_PORT = 465;
  $SMTP_ENCRYPTION = PHPMailer::ENCRYPTION_SMTPS;
  ```
- En cas d'échec, décommentez le log dans `contact.php` (ligne avec `mail-error.log`) pour voir l'erreur.