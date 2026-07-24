# CLAUDE.md — Photothèque Comapik

> Fichier de contexte pour Claude Code. À lire avant toute génération de code.
> Il décrit le projet, la stack, les contraintes et les conventions à respecter.

## 1. Objectif du projet

Application web permettant à deux photographes de **partager les photos d'un
événement avec leurs clients**. Les clients se connectent, consultent les photos
de leur(s) événement(s) et les téléchargent en basse définition.

L'application doit être **très simple, épurée et pensée mobile-first**. Côté
client, les seules fonctionnalités sont : **visionner** et **télécharger** les
photos. Rien d'autre.

## 2. Stack technique

- **Framework** : **Symfony** (PHP 8.2+)
- **Rendu** : côté serveur avec **Twig** (PAS de SPA React/Vue)
- **ORM** : **Doctrine** (+ Doctrine Migrations)
- **Base de données** : **MySQL / MariaDB**
- **Sécurité / accès** : **composant Security de Symfony** + **Voters**
- **Interactivité légère** : vanilla JS ou Alpine.js uniquement si nécessaire
- **Traitement d'images** : Imagick (fallback GD si indisponible)
- **CSS** : approche légère et responsive, mobile-first
- **Environnement de dev** : **MAMP** (Apache + MySQL + PHP en local)
- **Hébergement cible** : **O2Switch (mutualisé)** — voir contraintes ci-dessous

> Ne pas introduire de dépendance nécessitant un process Node persistant, Docker,
> ou une architecture non compatible avec un hébergement mutualisé.
>
> **Environnement local MAMP** : le développement se fait sous MAMP, qui reproduit
> la stack O2Switch (Apache, MySQL/MariaDB, PHP). Ne pas supposer la présence de
> Docker ni du Symfony CLI web server obligatoire (utilisable, mais l'app doit
> tourner sous l'Apache de MAMP). Configurer `DATABASE_URL` dans `.env.local`
> pour pointer vers la base MySQL de MAMP (souvent hôte `127.0.0.1`, port `8889`,
> utilisateur/mot de passe `root` par défaut — à vérifier). Vérifier que
> l'extension **Imagick** est activée dans le PHP de MAMP ; sinon prévoir le
> fallback GD.

## 3. Contraintes impératives

Ces règles priment sur toute autre considération :

1. **Les fichiers HD ne sont JAMAIS présents sur le serveur.** Seules les
   versions basse définition et les miniatures sont uploadées. Les originaux HD
   restent dans les archives locales des photographes (hors serveur).
2. **Le nom des fichiers basse def = le nom du fichier d'origine.** Indispensable
   pour retrouver l'original dans les archives hors serveur.
3. **Les photos ne sont pas servies en URL publique directe.** Elles sont
   stockées **hors du dossier `public/`** et servies par un **contrôleur qui
   vérifie l'accès** du client à l'événement.
4. **Mobile-first et rapide.** Lazy-loading des miniatures, chargement progressif,
   cache HTTP sur les images. Pas de bundle JS lourd.
5. **Interface épurée.** Aucune fonctionnalité superflue côté client.

## 4. Architecture : monolithe modulaire

Le projet est un **monolithe modulaire** organisé en deux domaines distincts :

- **Galerie** (périmètre V1) : événements, photos, accès clients.
- **Boutique** (à venir — voir §7) : commandes de produits imprimés.

Ces deux domaines doivent rester **découplés**. La galerie ne doit dépendre
d'aucun code de la boutique. La boutique viendra se brancher **sur** la galerie
plus tard, sans refonte.

### Rôles utilisateurs

- **Admin / Photographe** (`ROLE_ADMIN`) : gère les événements, uploade les
  photos, gère les accès clients. Back-office protégé, réservé aux deux
  photographes.
- **Client** (`ROLE_CLIENT`) : consulte et télécharge les photos des événements
  auxquels il a accès. Aucun autre droit.

## 5. Schéma de base de données (V1 — Galerie)

> Modélisé via des **entités Doctrine**. Les tables de la boutique NE SONT PAS
> créées en V1 (voir §7, cadrage uniquement).

- **User** : comptes admin/photographe et clients. Champ `roles` (JSON Symfony),
  implémente `UserInterface` + `PasswordAuthenticatedUserInterface`.
- **Evenement** : `id`, `nom`, `date`, `description`, `slug`, timestamps.
- **Photo** : `id`, `evenement` (ManyToOne), `nomOriginal` (nom du fichier
  d'origine), `cheminBasseDef`, `cheminMiniature`, `largeur`, `hauteur`,
  `taille`, `ordre`, timestamps.
- **AccesClient** : association ManyToMany (ou entité pivot) entre `User` (client)
  et `Evenement`. Un client ne voit QUE les événements qui lui sont associés ici.

## 6. Pipeline d'upload des images

À l'upload d'une photo (côté admin), générer automatiquement :

1. **Basse définition** : redimensionnée (ex. max 2000 px sur le grand côté),
   JPEG qualité ~80. **Nom conservé = nom d'origine.** C'est le fichier
   téléchargeable par le client.
2. **Miniature** : ~400 px sur le grand côté, pour l'affichage en grille.

Stockage sous un dossier **hors `public/`** (ex. `var/photos/` ou un dossier
dédié hors racine web). Enregistrer en base le `nomOriginal` et les chemins.
Servir les fichiers via un contrôleur protégé avec `BinaryFileResponse` (jamais
de chemin direct exposé au client). Encapsuler le traitement d'image dans un
**service dédié** (ex. `ImageProcessor`).

## 7. Boutique — HORS PÉRIMÈTRE V1 (cadrage futur)

> ⚠️ **NE PAS développer la boutique tant qu'elle n'est pas explicitement
> demandée.** Cette section existe uniquement pour que les choix d'architecture
> de la V1 restent compatibles avec son ajout ultérieur.

Plus tard, l'application permettra aux clients de **commander des photos en
produits physiques** (tirages, magnets, mugs, carreaux de céramique, posters…).

Principes déjà arrêtés pour cette future phase :

- **Fabrication en atelier** par les photographes. Pas d'intégration
  print-on-demand, pas d'API de fournisseur externe.
- **Paiement** via Stripe (SDK / bundle Stripe côté Symfony, Stripe Checkout).
- **Back-office de production** pour l'atelier : liste des commandes avec statut
  de fabrication (à produire → en production → prête → récupérée/expédiée).
- Comme les HD restent dans les archives locales, chaque ligne de commande
  enregistrera le **nom d'origine** de la photo : l'atelier retrouve le HD par ce
  nom pour fabriquer. Le serveur ne manipule aucun fichier lourd.
- Entités prévues (à créer le moment venu) : `Produit`, `Format`, `Commande`,
  `LigneCommande` (chaque ligne = `photo` + `format` + `nomOriginal` recopié).

**Conséquence pour la V1** : garder le domaine Galerie autonome, conserver
systématiquement le `nomOriginal`, et servir les fichiers via contrôleur protégé
(cette logique resservira pour la production des commandes).

## 8. Structure des dossiers (Symfony)

```
src/
  Controller/        # Contrôleurs (Galerie/, Admin/ ; Boutique/ plus tard)
  Entity/            # Entités Doctrine (User, Evenement, Photo, AccesClient)
  Repository/        # Repositories Doctrine
  Security/Voter/    # Voters (ex. EvenementVoter : accès client à un événement)
  Service/           # Services métier (ex. ImageProcessor)
  Form/              # Types de formulaires (upload, événement)
templates/           # Vues Twig (galerie/, admin/, base.html.twig)
migrations/          # Doctrine Migrations
config/              # Configuration (security.yaml, services.yaml…)
public/              # Racine web (AUCUNE photo ici)
var/                 # Cache, logs, stockage photos hors public
```

## 9. Conventions de code

- Suivre les **conventions Symfony standard** : contrôleurs fins, logique métier
  dans des **services**, entités/repositories Doctrine, formulaires via le
  composant Form.
- **Autorisations d'accès** : passer par des **Voters** Symfony (ex. vérifier
  qu'un client a bien accès à un événement), pas par des `if` dispersés.
- **Sécurité** : configuration dans `security.yaml`, hiérarchie de rôles
  (`ROLE_ADMIN`, `ROLE_CLIENT`), pare-feu approprié pour le back-office.
- Nommage des entités/champs en **français** (comme ci-dessus) ; garder les
  mots-clés techniques Symfony/Doctrine en anglais.
- Vues Twig légères, composants/partials réutilisables pour la grille de photos.

## 10. Méthode de travail

- Avancer **par petites tâches vérifiables**, une fonctionnalité à la fois.
- **Commits Git fréquents** avec messages clairs (filet de sécurité pour revenir
  en arrière).
- **Écrire des tests** (PHPUnit) pour les parties sensibles : accès clients
  (Voters), upload/pipeline d'images, et plus tard le paiement.
- Pour une grosse fonctionnalité, **proposer un plan** avant de coder.

## 11. Ordre de développement conseillé

1. **Fondations** : init Symfony, Git, ce `CLAUDE.md`, configuration MAMP/MySQL,
   premières entités + migrations.
2. **Auth & accès** : `security.yaml`, connexion, rôles admin/client,
   `EvenementVoter` pour l'accès par événement.
3. **Événements & upload** (admin) : CRUD événements, service `ImageProcessor`,
   stockage protégé hors `public/`.
4. **Galerie client** (V1 livrable) : consultation + téléchargement, mobile-first,
   lazy-loading, téléchargement via contrôleur protégé.
5. **Déploiement O2Switch** : mise en prod, mesure de performance réelle.
6. **Boutique** : uniquement après validation de la V1 en production (voir §7).
