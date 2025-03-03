# Guide d'optimisation des performances pour Symfony

Ce guide vous aidera à résoudre les problèmes de performance et de lag sur votre serveur Symfony.

## Problèmes identifiés

1. **Timeout PHP** : Le serveur rencontrait des erreurs "Maximum execution time of 30 seconds exceeded"
2. **Problèmes de mapping Doctrine** : Relation incorrecte entre les entités User et Article
3. **Configuration SSL** : Problèmes avec les certificats SSL pour les requêtes curl
4. **Extension fileinfo manquante** : Erreur "Unable to guess the MIME type as no guessers are available"

## Solutions mises en place

### 1. Fichiers de configuration optimisés

- `php.ini` : Configuration PHP optimisée avec des limites de mémoire et de temps d'exécution augmentées
- `.symfony.local.yaml` : Configuration du serveur Symfony local optimisée
- `php-local.ini` : Configuration PHP locale avec l'extension fileinfo activée

### 2. Extensions PHP requises

- **fileinfo** : Nécessaire pour la détection des types MIME des fichiers téléchargés
  - Activée dans le fichier `php.ini` avec la ligne `extension=fileinfo`
  - Vérifiée au démarrage des scripts d'optimisation et de serveur
  - **Important** : Si vous rencontrez l'erreur "Unable to guess the MIME type", utilisez le script `enable_fileinfo.bat`

### 3. Solution alternative pour l'extension fileinfo

Si vous ne pouvez pas activer l'extension fileinfo dans votre environnement, nous avons implémenté une solution alternative :

- **ArticleController.php** : Modification pour utiliser l'extension du fichier au lieu de `guessExtension()`
- **ArticleType.php** : Remplacement de la contrainte `File` par une contrainte `Callback` personnalisée

Ces modifications permettent de contourner le besoin de l'extension fileinfo pour le téléchargement d'images.

### 4. Scripts d'optimisation

- `symfony_optimize.bat` : Script pour nettoyer le cache et optimiser la base de données
- `start_server.bat` : Script pour démarrer le serveur avec les optimisations
- `stop_server.bat` : Script pour arrêter proprement le serveur
- `check_extensions.bat` : Script pour vérifier si les extensions PHP requises sont activées
- `enable_fileinfo.bat` : Script pour activer l'extension fileinfo dans le fichier php.ini système
- `start_server_with_local_php.bat` : Script pour démarrer le serveur avec le fichier php.ini local
- `install_fileinfo.bat` : Script pour guider l'installation de l'extension fileinfo

## Comment utiliser ces scripts

### Pour activer l'extension fileinfo (nécessaire pour les téléchargements de fichiers)

```bash
.\enable_fileinfo.bat
```

Ce script va :
- Vous guider pour activer l'extension fileinfo dans votre fichier php.ini système
- Ouvrir le fichier php.ini en tant qu'administrateur si vous le souhaitez

### Pour installer l'extension fileinfo

```bash
.\install_fileinfo.bat
```

Ce script va :
- Vous guider pour activer l'extension fileinfo dans votre fichier php.ini système
- Vous proposer une solution alternative si vous ne pouvez pas modifier le fichier php.ini

### Pour vérifier les extensions PHP

```bash
.\check_extensions.bat
```

Ce script va :
- Afficher toutes les extensions PHP chargées
- Vérifier spécifiquement si l'extension fileinfo est active
- Afficher le chemin du fichier php.ini utilisé

### Pour optimiser votre projet

```bash
.\symfony_optimize.bat
```

Ce script va :
- Vérifier les extensions PHP requises
- Nettoyer le cache Symfony
- Réchauffer le cache
- Valider le schéma de la base de données

### Pour démarrer le serveur optimisé

```bash
.\start_server.bat
```

Ce script va :
- Vérifier les extensions PHP requises
- Nettoyer et réchauffer le cache
- Démarrer le serveur sans TLS pour éviter les problèmes de certificat

### Pour démarrer le serveur avec le fichier php.ini local (si l'extension fileinfo n'est pas activée dans le système)

```bash
.\start_server_with_local_php.bat
```

Ce script va :
- Utiliser le fichier php-local.ini qui a l'extension fileinfo activée
- Nettoyer et réchauffer le cache
- Vérifier que l'extension fileinfo est active
- Démarrer le serveur sans TLS

### Pour arrêter le serveur

```bash
.\stop_server.bat
```

## Recommandations supplémentaires

1. **Vider régulièrement le cache** : Si vous rencontrez des problèmes de performance, essayez de vider le cache
2. **Optimiser les requêtes Doctrine** : Utilisez les bonnes pratiques pour les requêtes Doctrine
3. **Mettre à jour Symfony** : Assurez-vous d'utiliser la dernière version stable de Symfony
4. **Vérifier les extensions PHP** : Assurez-vous que toutes les extensions PHP requises sont activées

## Dépannage

Si vous rencontrez toujours des problèmes :

1. Vérifiez les logs dans `var/log/`
2. Augmentez encore les limites de mémoire et de temps d'exécution dans `php.ini`
3. Désactivez temporairement les extensions ou bundles non essentiels
4. Vérifiez que l'extension fileinfo est correctement activée avec `php -m | findstr fileinfo`
5. Si vous ne pouvez pas modifier le fichier php.ini système, utilisez le script `start_server_with_local_php.bat`
6. Si rien ne fonctionne, utilisez la solution alternative implémentée dans le code 