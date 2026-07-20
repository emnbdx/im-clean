# Clarté — site vitrine

Site promotionnel (landing page) de l'application mobile **Clarté**, un tracker
de sevrage calme et privé pour l'arrêt de **l'alcool** et du **tabac**.

## Le concept

On ne coche pas chaque jour. L'utilisateur définit une date de début par
habitude, puis chaque jour est « réussi » par défaut (sans alcool / sans
cigarette). On ne note que les jours d'écart. Le quotidien ne demande donc
aucune action tant que tout va bien.

## Le site

- Page unique statique : `index.html` (aucune dépendance serveur).
- HTML + CSS + un peu de JS vanille, tout est auto-contenu.
- Polices via Google Fonts (Bricolage Grotesque + Inter).
- Français, responsive, respecte « réduire les animations ».

### Sections

Hero · Concept · Trackers (alcool / tabac) · Fonctionnalités · Mon corps
(timeline santé sourcée, OMS pour le tabac) · Statistiques · Confidentialité ·
FAQ · Téléchargement.

## Développement local

Aucune étape de build. Ouvrez `index.html` dans un navigateur, ou servez le
dossier :

```bash
python3 -m http.server 8000
# puis http://localhost:8000
```

## Avertissement

Clarté n'est pas un dispositif médical et ne remplace pas un avis
professionnel. Les paliers de la timeline « Mon corps » sont des moyennes de
groupe et proviennent de sources publiques citées dans la page (OMS, NIAAA,
BMJ Open, JAMA…). En cas de forte consommation d'alcool, un arrêt brutal peut
être dangereux : consultez un médecin.
