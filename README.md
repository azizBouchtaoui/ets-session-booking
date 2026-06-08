# ETS GLOBAL EMEA — Test Technique

Application de réservation de sessions d'examens de langues.

**Backend** : API REST Symfony 6.4 · **Frontend** : Next.js 16 · **Base de données** : MongoDB 6 · **Auth** : JWT RS256 · **Infra** : Docker Compose

---

## Choix techniques

| Décision | Choix | Justification |
|---|---|---|
| Framework API | Symfony 6.4 LTS | Version supportée jusqu'en 2027, écosystème mature, DI et sécurité robustes |
| ORM | Doctrine ODM | Mapping natif MongoDB, UnitOfWork identique à l'ORM Doctrine |
| Authentification | JWT RS256 + HttpOnly cookie | RS256 : clé publique séparable du signing. Cookie HttpOnly : pas d'accès JS, protection XSS automatique |
| Base de données | MongoDB 6 | Exigence du cahier des charges ; schéma souple adapté à un modèle de réservation |
| Frontend | Next.js 16 (App Router) | TypeScript first, rendu côté serveur optionnel, build standalone pour Docker |
| CORS | nelmio/cors-bundle | Configuration déclarative, `allow_credentials: true` requis pour les cookies cross-origin |
| Validation | `#[MapRequestPayload]` + Symfony Validator | Désérialisation + validation en une seule annotation, format d'erreur contrôlé |

---

## Architecture globale

```
Browser
  │
  ├─► :3000  Next.js (standalone)
  │
  └─► :8080  nginx (reverse proxy)
                │
                └─► PHP-FPM :9000 (Symfony 6.4)
                        │
                        └─► MongoDB :27017
```

Flux d'authentification :
1. `POST /api/auth/login` → `json_login` Symfony → Lexik JWT génère un token RS256
2. Le token est placé dans un cookie `jwt` (HttpOnly, SameSite=strict, TTL 1h)
3. Chaque requête suivante : le `JWTAuthenticator` de Lexik lit le cookie et valide la signature

Les contrôleurs sont intentionnellement fins : toute logique métier se trouve dans les Services.

---

## Structure du repository

```
.
├── docker-compose.yml
├── .env                         # variables d'environnement (voir section dédiée)
├── docker/
│   ├── mongo/init.js            # création user app + index MongoDB au démarrage
│   └── nginx/default.conf       # reverse proxy vers PHP-FPM
├── backend/                     # Symfony 6.4
│   ├── src/
│   │   ├── Controller/          # AuthController, SessionController, ReservationController, UserController
│   │   ├── Document/            # User, Session, Reservation (Doctrine ODM)
│   │   ├── DTO/                 # objets de requête validés par Symfony Validator
│   │   ├── Service/             # UserService, SessionService, ReservationService
│   │   ├── Repository/          # requêtes MongoDB custom + pagination
│   │   ├── Security/            # UserProvider (bridge Symfony Security ↔ ODM)
│   │   ├── Exception/           # ApiException (code HTTP + message)
│   │   └── EventListener/       # ExceptionListener → toutes les erreurs en JSON
│   ├── tests/
│   │   ├── ApiTestCase.php      # WebTestCase + helpers register/login/purge
│   │   ├── AuthTest.php
│   │   ├── SessionTest.php
│   │   └── ReservationTest.php
│   └── docker-entrypoint.sh    # composer install + génération clés JWT au démarrage
└── frontend/                    # Next.js 16
    └── src/
        ├── app/                 # pages : /, /login, /register, /sessions, /profile
        ├── contexts/            # AuthContext (état utilisateur global)
        ├── hooks/               # useSessions, useReservations
        └── lib/                 # api.ts (fetch wrapper), types.ts
```

---

## Installation

### Prérequis

- Docker 24+ et Docker Compose v2
- Ports **8080** et **3000** libres

### Démarrage

```bash
git clone <repo-url>
cd testTechnique
docker compose up -d --build
```

Au premier démarrage :
- MongoDB exécute `docker/mongo/init.js` : création de `app_user` et des index
- Le backend génère automatiquement la paire de clés JWT RS256 dans `backend/config/jwt/`
- Le frontend est construit en image multi-stage (build + runner `node server.js`)

**Frontend** : http://localhost:3000  
**API** : http://localhost:8080

---

## Variables d'environnement

Fichier `.env` à la racine du projet (fourni, ne contient aucun secret de production) :

```dotenv
MONGO_ROOT_USERNAME=root
MONGO_ROOT_PASSWORD=rootpassword
MONGO_DATABASE=ets_reservations
MONGO_APP_USERNAME=app_user
MONGO_APP_PASSWORD=app_password

JWT_PASSPHRASE=<passphrase pour chiffrer la clé privée RS256>

NGINX_PORT=8080
FRONTEND_PORT=3000
```

---

## Endpoints API

Base URL : `http://localhost:8080`

### Authentification

| Méthode | Route | Accès | Description |
|---|---|---|---|
| `POST` | `/api/auth/register` | Public | Inscription |
| `POST` | `/api/auth/login` | Public | Connexion → cookie `jwt` |
| `POST` | `/api/auth/logout` | Connecté | Déconnexion → efface le cookie |

### Utilisateur

| Méthode | Route | Accès | Description |
|---|---|---|---|
| `GET` | `/api/users/me` | Connecté | Profil courant |
| `PUT` | `/api/users/me` | Connecté | Modifier nom / email |

### Sessions

| Méthode | Route | Accès | Description |
|---|---|---|---|
| `GET` | `/api/sessions` | Connecté | Liste paginée (`?page=1&limit=10`) |
| `GET` | `/api/sessions/{id}` | Connecté | Détail d'une session |
| `POST` | `/api/sessions` | Admin | Créer une session |
| `PUT` | `/api/sessions/{id}` | Admin | Modifier une session |
| `DELETE` | `/api/sessions/{id}` | Admin | Supprimer (bloqué si réservations actives) |

### Réservations

| Méthode | Route | Accès | Description |
|---|---|---|---|
| `GET` | `/api/reservations` | Connecté | Réservations de l'utilisateur courant |
| `POST` | `/api/reservations` | Connecté | Réserver une session |
| `DELETE` | `/api/reservations/{id}` | Propriétaire | Annuler sa réservation |

**Format de réponse pageinée :**
```json
{ "items": [...], "total": 42, "page": 1, "limit": 10, "pages": 5 }
```

**Format d'erreur :**
```json
{ "message": "This session is fully booked." }
```

**Erreurs de validation (422) :**
```json
{ "errors": { "email": "This value is not a valid email address." } }
```

---

## Gestion des rôles

Deux rôles : `ROLE_USER` (défaut) et `ROLE_ADMIN`.

Les utilisateurs reçoivent `ROLE_USER` à l'inscription. La promotion admin se fait directement en base :

```bash
docker compose exec mongo mongosh \
  --username root --password rootpassword --authenticationDatabase admin \
  ets_reservations \
  --eval 'db.users.updateOne({email:"admin@example.com"},{$set:{roles:["ROLE_ADMIN"]}})'
```

Ensuite, se reconnecter pour obtenir un JWT avec le nouveau rôle dans les claims.

---

## Gestion des réservations

- **Décrémentation atomique** : `availableSpots` est décrémenté via `findAndUpdate()` avec le filtre `availableSpots > 0`. Aucune race condition possible entre la vérification et la mise à jour.
- **Annulation** : incrémente `availableSpots` puis supprime la réservation. L'opération est rejetée si l'utilisateur n'est pas propriétaire (403).
- **Suppression de session** : bloquée tant que des réservations existent (409).
- **Double réservation** : rejetée par index composé unique `{sessionId, userId}` sur la collection `reservations`.

---

## Exécution des tests

```bash
docker compose exec backend php vendor/bin/phpunit --no-coverage
```

Résultat attendu : **17 tests, 84 assertions**.

Les tests utilisent une base de données séparée (`ets_reservations_test`) purgée avant chaque test. Scénarios couverts :

- Inscription, connexion, cookie JWT, mauvais mot de passe, endpoint protégé sans token
- Création de session par admin, refus pour `ROLE_USER`, liste et mise à jour
- Réservation, double réservation (409), session complète (409)
- Annulation, re-incrément des places, refus d'annulation sur réservation d'un autre utilisateur
- Suppression de session bloquée par réservations actives

---

## Décisions techniques importantes

**Cookie HttpOnly vs Bearer token**  
Le token JWT est transmis uniquement via cookie HttpOnly. Le frontend n'a jamais accès au token en JavaScript, ce qui supprime la surface d'attaque XSS la plus courante. La contrepartie est la nécessité de configurer CORS avec `allow_credentials: true`.

**RS256 vs HS256**  
RS256 est utilisé par défaut par `lexik/jwt-authentication-bundle`. La clé privée signe, la clé publique vérifie — ce qui permettrait à un service tiers de valider les tokens sans exposer le secret.

**ObjectId stocké en string pour les relations**  
Les documents `Reservation` stockent `userId` et `sessionId` comme strings (ObjectId MongoDB). Ce choix évite la complexité des `ReferenceOne` Doctrine ODM tout en conservant la capacité à faire des jointures applicatives ou des agrégations MongoDB directes.

**Séparation stricte Controller / Service**  
Les contrôleurs se limitent à : désérialiser le payload, valider, appeler le service, retourner la réponse. Aucune logique conditionnelle métier ne se trouve dans un contrôleur.

---

## Limites connues

- **Pas de blacklist JWT** : à la déconnexion, le cookie est effacé côté client mais le token reste techniquement valide jusqu'à son expiration (1h). En production, une blacklist Redis ou un TTL court serait nécessaire.
- **Pas de refresh token** : l'utilisateur est déconnecté après 1h. Un mécanisme de rotation de token n'a pas été implémenté.
- **Pagination côté serveur uniquement** : le frontend récupère les sessions page par page ; il n'y a pas de cursor-based pagination pour les grandes collections.
- **Pas de gestion de fuseau horaire** : `scheduledAt` est stocké tel que fourni. L'affichage dépend du navigateur.

---

## Pistes d'amélioration

- Refresh token avec rotation et blacklist Redis
- Emails transactionnels (confirmation de réservation) via Symfony Mailer
- Interface d'administration (création de sessions depuis le frontend)
- Pagination cursor-based pour les collections volumineuses
- CI/CD : pipeline GitHub Actions (lint, tests PHPUnit, build Docker, push registry)
- Observabilité : structured logging (Monolog JSON), métriques Prometheus
