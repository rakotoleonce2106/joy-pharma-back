# Troubleshooting - Erreur Docker Buildx Deploy

## ❌ Erreur rencontrée

```
ERROR: Error response from daemon: Head "https://registry-1.docker.io/v2/moby/buildkit/manifests/buildx-stable-1": 
received unexpected HTTP status: 500 Internal Server Error
```

## 🔍 Causes possibles

1. **Docker Hub temporairement indisponible** (erreur 500 côté serveur)
2. **Rate limiting Docker Hub** (trop de pulls sans authentification)
3. **Problème réseau** entre GitHub Actions et Docker Hub
4. **Cache Docker corrompu**

## ✅ Solutions

### Solution 1 : Re-déclencher le workflow (Recommandé)

L'erreur 500 de Docker Hub est souvent temporaire.

**Via l'interface GitHub :**
1. Aller sur l'onglet **Actions** de votre repo
2. Cliquer sur le workflow en erreur
3. Cliquer sur **Re-run jobs** → **Re-run all jobs**

**Via la ligne de commande :**
```bash
# Utiliser GitHub CLI
gh run rerun <run-id>

# Ou forcer un nouveau push
git commit --allow-empty -m "Trigger deploy"
git push
```

### Solution 2 : Authentification Docker Hub

Ajouter l'authentification Docker Hub pour éviter les rate limits.

**1. Créer un token Docker Hub :**
- Aller sur [hub.docker.com](https://hub.docker.com)
- Settings → Security → New Access Token
- Copier le token généré

**2. Ajouter les secrets GitHub :**
- Repo → Settings → Secrets and variables → Actions
- Ajouter deux secrets :
  - `DOCKERHUB_USERNAME` : votre username Docker Hub
  - `DOCKERHUB_TOKEN` : le token créé

**3. Modifier le workflow GitHub Actions :**

```yaml
# Dans .github/workflows/deploy-backend.yml

jobs:
  deploy-backend:
    steps:
      # Ajouter AVANT l'étape de build
      - name: Login to Docker Hub
        uses: docker/login-action@v3
        with:
          username: ${{ secrets.DOCKERHUB_USERNAME }}
          token: ${{ secrets.DOCKERHUB_TOKEN }}
      
      # Puis continuer avec le build existant
      - name: Build and push
        uses: docker/build-push-action@v5
        # ... reste du code
```

### Solution 3 : Utiliser un miroir Docker Hub

Configurer un miroir Docker Hub alternatif.

```yaml
- name: Set up Docker Buildx
  uses: docker/setup-buildx-action@v3
  with:
    config-inline: |
      [registry."docker.io"]
        mirrors = ["https://mirror.gcr.io"]
```

### Solution 4 : Modifier la version de buildkit

Au lieu de `buildx-stable-1`, utiliser une version spécifique.

```yaml
- name: Set up Docker Buildx
  uses: docker/setup-buildx-action@v3
  with:
    driver-opts: |
      image=moby/buildkit:v0.12.0
```

### Solution 5 : Nettoyer le cache builder

Si le problème persiste, nettoyer le cache.

```yaml
- name: Set up Docker Buildx
  uses: docker/setup-buildx-action@v3
  with:
    driver-opts: network=host
    buildkitd-flags: --allow-insecure-entitlement network.host
    
- name: Clean Docker cache
  run: docker buildx prune -af
```

## 🚀 Configuration recommandée complète

Voici une configuration optimale pour éviter ces problèmes :

```yaml
name: Deploy Backend

on:
  push:
    branches: [main, master]
  workflow_dispatch:

jobs:
  deploy-backend:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      # NOUVEAU : Login Docker Hub pour éviter rate limits
      - name: Login to Docker Hub
        uses: docker/login-action@v3
        with:
          username: ${{ secrets.DOCKERHUB_USERNAME }}
          token: ${{ secrets.DOCKERHUB_TOKEN }}
        continue-on-error: true  # Ne pas bloquer si pas de credentials
      
      # Setup Docker Buildx avec retry
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3
        with:
          version: latest
          driver-opts: network=host
        timeout-minutes: 5
      
      # Nettoyer le cache si nécessaire
      - name: Clean Docker cache
        run: docker buildx prune -f --filter "until=24h"
        continue-on-error: true
      
      # Build avec retry automatique
      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: your-registry/your-image:latest
          cache-from: type=gha
          cache-to: type=gha,mode=max
        timeout-minutes: 30
```

## 🔄 Actions immédiates

### 1. Vérifier l'état de Docker Hub

```bash
# Vérifier si Docker Hub est accessible
curl -I https://hub.docker.com

# Vérifier l'API
curl -I https://registry-1.docker.io/v2/
```

### 2. Re-déclencher le déploiement

```bash
# Option 1 : Commit vide pour re-trigger
git commit --allow-empty -m "chore: retry docker build"
git push

# Option 2 : Via GitHub CLI
gh run rerun --failed

# Option 3 : Via l'interface GitHub
# Actions → Workflow en erreur → Re-run jobs
```

### 3. Vérifier les logs

Sur GitHub Actions, vérifier :
- Le timestamp de l'erreur
- Si c'était lors d'un pic de traffic
- Les autres runs récents (problème global ou isolé ?)

## 📊 Prévention future

### 1. Activer le cache GitHub Actions

```yaml
- name: Build and push
  uses: docker/build-push-action@v5
  with:
    cache-from: type=gha
    cache-to: type=gha,mode=max
```

### 2. Utiliser des retry automatiques

```yaml
- name: Build with retry
  uses: nick-fields/retry@v2
  with:
    timeout_minutes: 30
    max_attempts: 3
    retry_wait_seconds: 60
    command: |
      docker buildx build --push \
        --tag your-image:latest \
        .
```

### 3. Monitorer Docker Hub status

Ajouter dans votre workflow :

```yaml
- name: Check Docker Hub status
  run: |
    if ! curl -s https://registry-1.docker.io/v2/ > /dev/null 2>&1; then
      echo "⚠️  Docker Hub seems unreachable, waiting 60s..."
      sleep 60
    fi
```

## 📝 Notes importantes

1. **Erreur 500 de Docker Hub** : Généralement temporaire (5-30 minutes)
2. **Rate limits** : 100 pulls/6h sans auth, 200 pulls/6h avec compte gratuit
3. **Authentification recommandée** : Même avec compte gratuit Docker Hub
4. **Cache GitHub Actions** : Réduit considérablement les pulls Docker Hub

## 🔗 Ressources

- [Docker Hub Status](https://status.docker.com/)
- [Docker Hub Rate Limits](https://docs.docker.com/docker-hub/download-rate-limit/)
- [GitHub Actions Docker Build](https://github.com/docker/build-push-action)
- [Docker Buildx Documentation](https://docs.docker.com/buildx/working-with-buildx/)

## ❓ Si le problème persiste

1. Vérifier [status.docker.com](https://status.docker.com/)
2. Attendre 15-30 minutes
3. Configurer l'authentification Docker Hub
4. Essayer à une heure différente (moins de traffic)
5. Considérer un registry alternatif (GitHub Container Registry, AWS ECR, etc.)

---

**Dernière mise à jour** : Décembre 2024

