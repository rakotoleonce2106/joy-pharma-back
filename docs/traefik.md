# 🚦 Configuration Traefik pour Joy Pharma Backend

Ce guide explique comment configurer et utiliser Traefik comme reverse proxy pour Joy Pharma Backend en production.

## 📋 Prérequis

- Traefik déjà installé et configuré sur le serveur
- Traefik accessible via le réseau Docker
- Nom de domaine configuré pointant vers le serveur

## 🏗️ Architecture

```
Internet → Traefik (Port 80/443) → Joy Pharma Backend (Port 80 interne)
```

Traefik gère :
- ✅ Terminaison SSL/TLS (Let's Encrypt)
- ✅ Routage HTTP/HTTPS
- ✅ Load balancing
- ✅ Headers de sécurité
- ✅ Compression

## 🔧 Configuration Traefik

### Labels Docker

Le fichier `compose.prod.yaml` configure automatiquement les labels Traefik :

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.docker.network=${TRAEFIK_NETWORK}"
  - "traefik.http.routers.joypharma-https.rule=Host(`${SERVER_NAME}`)"
  - "traefik.http.routers.joypharma-https.entrypoints=websecure"
  - "traefik.http.routers.joypharma-https.tls=true"
  - "traefik.http.routers.joypharma-https.tls.certresolver=letsencrypt"
```

### Variables d'Environnement Requises

Dans Infisical (environnement `prod`), configurez :

```bash
SERVER_NAME=api.votre-domaine.com
TRAEFIK_NETWORK=traefik_default  # ou le nom de votre réseau Traefik
```

## 🌐 Détection Automatique du Réseau Traefik

Le workflow GitHub Actions détecte automatiquement le réseau Traefik en utilisant trois méthodes :

1. **Via le conteneur Traefik** : Cherche le réseau du conteneur `traefik`
2. **Via la liste des réseaux** : Cherche les réseaux contenant "traefik"
3. **Réseau par défaut** : Utilise `traefik_default` si disponible

Si aucun réseau n'est trouvé, le workflow crée automatiquement `traefik_default`.

## 📝 Configuration Manuelle

### 1. Vérifier le Réseau Traefik

```bash
# Lister les réseaux Docker
docker network ls

# Inspecter le réseau Traefik
docker network inspect traefik_default

# Ou trouver le réseau du conteneur Traefik
docker inspect traefik --format '{{range $net, $conf := .NetworkSettings.Networks}}{{$net}}{{end}}'
```

### 2. Configurer le Réseau dans Infisical

Ajoutez dans Infisical (environnement `prod`) :

```bash
TRAEFIK_NETWORK=traefik_default  # Remplacez par le nom réel de votre réseau
```

### 3. Vérifier la Configuration Traefik

Le fichier `compose.prod.yaml` configure automatiquement :

- **Routage HTTP** : Redirection vers HTTPS
- **Routage HTTPS** : Routage vers le conteneur PHP
- **Certificats SSL** : Let's Encrypt automatique
- **Headers de sécurité** : X-Forwarded-Proto, X-Real-Ip
- **Compression** : Gzip activé

## 🚀 Déploiement

### Déploiement Automatique

Le déploiement via GitHub Actions configure automatiquement Traefik :

1. Détecte le réseau Traefik
2. Génère le fichier `.env` avec Infisical
3. Configure les labels Traefik
4. Démarre les conteneurs sur le réseau Traefik

### Déploiement Manuel

```bash
# Sur le serveur
cd joypharma

# Exporter les secrets depuis Infisical
infisical export --env=prod --format=dotenv > .env

# Ajouter les variables Docker
echo "TRAEFIK_NETWORK=traefik_default" >> .env
echo "SERVER_NAME=api.votre-domaine.com" >> .env
echo "IMAGES_PREFIX=votreuser/" >> .env
echo "IMAGE_TAG=latest" >> .env

# Déployer
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env up -d
```

## 🔍 Vérification

### Vérifier le Routage Traefik

```bash
# Vérifier les routes Traefik
docker exec traefik traefik api --help

# Ou via l'interface web Traefik (si activée)
# http://votre-serveur:8080
```

### Vérifier les Labels

```bash
# Inspecter le conteneur
docker inspect joypharma_php | grep -A 20 Labels
```

### Tester l'Application

```bash
# Test HTTP (devrait rediriger vers HTTPS)
curl -I http://api.votre-domaine.com

# Test HTTPS
curl -I https://api.votre-domaine.com

# Test health check
curl https://api.votre-domaine.com/health.php
```

## 🛠️ Dépannage

### Le Conteneur n'est pas Accessible via Traefik

**Problème** : Traefik ne route pas vers l'application

**Solutions** :

1. Vérifier que le conteneur est sur le bon réseau :
```bash
docker network inspect traefik_default | grep joypharma
```

2. Vérifier les labels Traefik :
```bash
docker inspect joypharma_php | grep -i traefik
```

3. Vérifier les logs Traefik :
```bash
docker logs traefik
```

### Erreur de Certificat SSL

**Problème** : Certificat Let's Encrypt non généré

**Solutions** :

1. Vérifier que le domaine pointe vers le serveur :
```bash
dig api.votre-domaine.com
```

2. Vérifier la configuration Let's Encrypt dans Traefik
3. Vérifier les logs Traefik pour les erreurs ACME

### Le Réseau Traefik n'est pas Trouvé

**Problème** : Le workflow ne trouve pas le réseau Traefik

**Solutions** :

1. Créer manuellement le réseau :
```bash
docker network create traefik_default
```

2. Configurer le nom dans Infisical :
```bash
TRAEFIK_NETWORK=votre_reseau_traefik
```

3. Vérifier que Traefik utilise ce réseau :
```bash
docker inspect traefik | grep NetworkMode
```

## 📚 Références

- [Documentation Traefik](https://doc.traefik.io/traefik/)
- [Traefik Docker Provider](https://doc.traefik.io/traefik/providers/docker/)
- [Traefik Labels](https://doc.traefik.io/traefik/routing/providers/docker/#labels)

## 🔐 Sécurité

### Headers de Sécurité

Traefik ajoute automatiquement :
- `X-Forwarded-Proto: https`
- `X-Forwarded-Port: 443`
- `X-Real-Ip: <client-ip>`

### Recommandations

1. **Ne pas exposer les ports directement** : Traefik gère le routage
2. **Utiliser HTTPS uniquement** : Redirection HTTP → HTTPS
3. **Limiter l'accès** : Utiliser les middlewares Traefik pour l'authentification
4. **Surveiller les logs** : Activer les logs Traefik pour la sécurité

## ✅ Checklist de Déploiement

- [ ] Traefik installé et configuré
- [ ] Réseau Traefik créé et accessible
- [ ] Nom de domaine configuré (DNS)
- [ ] Variables Infisical configurées (`SERVER_NAME`, `TRAEFIK_NETWORK`)
- [ ] Labels Traefik configurés dans `compose.prod.yaml`
- [ ] Certificats SSL générés (Let's Encrypt)
- [ ] Application accessible via HTTPS
- [ ] Health check fonctionnel (`/health.php`)

---

**🎉 Votre application est maintenant accessible via Traefik !**

