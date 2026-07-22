# I'm clean — site vitrine

Site promotionnel (landing page) de l'application mobile **I'm clean**, un tracker
de sevrage calme et privé pour l'arrêt de **l'alcool** et du **tabac**.

## Le concept

On ne coche pas chaque jour. L'utilisateur définit une date de début par
habitude, puis chaque jour est « réussi » par défaut (sans alcool / sans
cigarette). On ne note que les jours d'écart. Le quotidien ne demande donc
aucune action tant que tout va bien.

## Le site

- Pages :
  - `/` — vitrine (`index.html`)
  - `/support/` — formulaire contact (PHP → Brevo)
  - `/privacy/` — politique de confidentialité
- `i18n.js`, `site.css`, `config.php` (secrets Brevo, gitignored).
- Logo : `logo.jpeg`.
- Polices via Google Fonts (Bricolage Grotesque + Inter).
- Localisation EN / FR / ES : langue système d'abord, fallback EN si non supportée ; sélecteur dans la nav, choix mémorisé.
- Responsive, respecte « réduire les animations ».

### Sections (vitrine)

Hero · Concept · Trackers (alcool / tabac) · Fonctionnalités · Mon corps
(timeline santé sourcée, OMS pour le tabac) · Statistiques · Confidentialité ·
FAQ · Téléchargement.

### Support / Brevo

1. Copier `config.example.php` → `config.php`
2. Renseigner la clé API Brevo et les e-mails expéditeur / destinataire
   (l'expéditeur doit être vérifié dans Brevo)
3. Servir le site avec PHP (`php -S` ou Apache/Nginx + PHP)

## Développement local

Vitrine seule :

```bash
python3 -m http.server 8000
```

Avec envoi support (PHP) :

```bash
cp config.example.php config.php
# éditer config.php
php -S localhost:8000
# puis http://localhost:8000/support/
```

## Avertissement

I'm clean n'est pas un dispositif médical et ne remplace pas un avis
professionnel. Les paliers de la timeline « Mon corps » sont des moyennes de
groupe et proviennent de sources publiques citées dans la page (OMS, NIAAA,
BMJ Open, JAMA…). En cas de forte consommation d'alcool, un arrêt brutal peut
être dangereux : consultez un médecin.
